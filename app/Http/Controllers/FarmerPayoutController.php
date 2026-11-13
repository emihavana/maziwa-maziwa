<?php

namespace App\Http\Controllers;

use App\Models\Farmer;
use App\Models\FarmerPayout;
use Iankumu\Mpesa\Facades\Mpesa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FarmerPayoutController extends Controller
{
    /* ---------------------------------------------------------------
     |  1. TRIGGER: send end-of-month payouts
     | --------------------------------------------------------------- */

    /**
     * Send each farmer's positive net balance via M-Pesa B2C.
     *
     * Safaricom only *queues* the payment here. The final outcome
     * (success/failure) arrives later at handleResult().
     */
    public function disburseEndMonthBalances(): JsonResponse
    {
        // Only allow payouts on the last day of the month.
    // In sandbox the check is skipped, so you can still test any day.
    $isLastDay = now()->day === now()->daysInMonth;

    if (config('mpesa.environment') !== 'sandbox' && ! $isLastDay) {
        return response()->json([
            'status'  => 'info',
            'message' => 'Payouts can only be run on the last day of the month ('
                         . now()->endOfMonth()->format('d M Y') . ').',
        ], 403);
    }
        // Only farmers owed money (net_payable = milk earnings - debts).
        $farmers = Farmer::all()->filter(fn ($farmer) => $farmer->net_payable > 0);

        if ($farmers->isEmpty()) {
            return response()->json([
                'status'  => 'info',
                'message' => 'No eligible farmers found with a positive balance.',
            ]);
        }

        // A long farmer list can exceed PHP's default time limit.
        set_time_limit(0);

        $summary = ['dispatched' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($farmers as $farmer) {

            // Guard: never pay the same farmer twice in one month.
            // Failed payouts are not counted, so they can be retried.
            $alreadyPaid = FarmerPayout::where('farmer_id', $farmer->farmer_id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->whereIn('status', ['pending', 'success'])
                ->exists();

            if ($alreadyPaid) {
                $summary['skipped']++;
                continue;
            }

            // B2C only accepts whole shillings.
            $amount = (int) floor($farmer->net_payable);
            $phone  = $this->normalizePhone($farmer->phone);

            if ($amount < 1 || $phone === null) {
                $summary['skipped']++;
                Log::warning("Payout skipped for {$farmer->farmer_id}: invalid amount or phone.");
                continue;
            }

            // Write the audit record BEFORE calling M-Pesa so there is always
            // a trace, even if the request crashes. No DB transaction here:
            // an HTTP call to Safaricom cannot be rolled back.
            $payout = FarmerPayout::create([
                'farmer_id'    => $farmer->farmer_id,
                'amount'       => $amount,
                'phone_number' => $phone,
                'status'       => 'pending',
            ]);

            try {
                // Signature: b2c($phone, $commandId, $amount, $remarks)
                $response = Mpesa::b2c($phone, 'BusinessPayment', $amount, 'End Month Milk Payout');
                $result   = $response->json();

                if (($result['ResponseCode'] ?? null) == '0') {
                    // Accepted into Safaricom's queue. Save the tracking IDs
                    // so the callback can find this record later.
                    $payout->update([
                        'conversation_id'            => $result['ConversationID'] ?? null,
                        'originator_conversation_id' => $result['OriginatorConversationID'] ?? null,
                    ]);
                    $summary['dispatched']++;
                } else {
                    // Rejected immediately (e.g. low float, bad credentials).
                    // No callback will come, so mark it failed now.
                    $payout->update([
                        'status'      => 'failed',
                        'result_desc' => json_encode($result),
                    ]);
                    $summary['failed']++;
                }
            } catch (\Throwable $e) {
                // Network error, bad config, etc.
                $payout->update([
                    'status'      => 'failed',
                    'result_desc' => $e->getMessage(),
                ]);
                $summary['failed']++;
                Log::error("B2C exception for {$farmer->farmer_id}: {$e->getMessage()}");
            }
        }

        return response()->json([
            'status'  => 'success',
            'summary' => $summary,
        ]);
    }

    /* ---------------------------------------------------------------
     |  2. CALLBACK: Safaricom reports the final result
     | --------------------------------------------------------------- */

    /**
     * Result URL. Safaricom POSTs the final outcome of a B2C payment here.
     */
    public function handleResult(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('M-Pesa B2C result received', $payload);

        $result                   = $payload['Result'] ?? [];
        $originatorConversationId = $result['OriginatorConversationID'] ?? null;
        $resultCode               = $result['ResultCode'] ?? null;  // 0 = success
        $resultDesc               = $result['ResultDesc'] ?? null;

        $payout = $originatorConversationId
            ? FarmerPayout::where('originator_conversation_id', $originatorConversationId)->first()
            : null;

        // Unknown transaction: log it and still acknowledge, so Safaricom stops retrying.
        if (!$payout) {
            Log::warning("B2C result for unknown payout: {$originatorConversationId}");
            return $this->acknowledge();
        }

        // Idempotency: Safaricom may send the same callback more than once.
        if ($payout->status !== 'pending') {
            return $this->acknowledge();
        }

        if ((int) $resultCode === 0) {
            $payout->update([
                'status'         => 'success',
                'receipt_number' => $this->extractParameter($result, 'TransactionReceipt'),
                'result_desc'    => $resultDesc,
            ]);
        } else {
            // Failed (e.g. subscriber not registered, limit exceeded).
            // The farmer stays unpaid; failed payouts don't block a retry.
            $payout->update([
                'status'      => 'failed',
                'result_desc' => "[{$resultCode}] {$resultDesc}",
            ]);
        }

        return $this->acknowledge();
    }

    /**
     * Timeout URL. Safaricom calls this if it could not process the request
     * in time. The outcome is unknown, so we flag it for manual checking
     * rather than assuming success or failure.
     */
    public function handleTimeout(Request $request): JsonResponse
    {
        Log::warning('M-Pesa B2C timeout received', $request->all());

        $originatorConversationId = $request->input('Result.OriginatorConversationID');

        if ($originatorConversationId) {
            FarmerPayout::where('originator_conversation_id', $originatorConversationId)
                ->where('status', 'pending')
                ->update(['result_desc' => 'Timed out. Verify manually in the M-Pesa portal.']);
        }

        return $this->acknowledge();
    }

    /* ---------------------------------------------------------------
     |  3. HELPERS
     | --------------------------------------------------------------- */

    /**
     * Standard reply Safaricom expects from every callback.
     */
    private function acknowledge(): JsonResponse
    {
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * Pull a named value (e.g. "TransactionReceipt") from Safaricom's
     * ResultParameters list.
     */
    private function extractParameter(array $result, string $key): mixed
    {
        $parameters = $result['ResultParameters']['ResultParameter'] ?? [];

        foreach ($parameters as $parameter) {
            if (($parameter['Key'] ?? null) === $key) {
                return $parameter['Value'] ?? null;
            }
        }

        return null;
    }

    /**
     * Convert 07XXXXXXXX, 01XXXXXXXX, 7XXXXXXXX, +2547XXXXXXXX etc. into
     * 2547XXXXXXXX. Returns null if the number isn't a valid Kenyan mobile.
     */
    private function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if (preg_match('/^0([71]\d{8})$/', $digits, $m)) return '254' . $m[1];
        if (preg_match('/^([71]\d{8})$/', $digits, $m))  return '254' . $m[1];
        if (preg_match('/^254[71]\d{8}$/', $digits))     return $digits;

        return null;
    }
}