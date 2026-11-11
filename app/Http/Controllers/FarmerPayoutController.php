<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Farmer;
use App\Models\FarmerPayout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Iankumu\Mpesa\Facades\Mpesa;

class FarmerPayoutController extends Controller
{
    /**
     * Trigger end-of-month disbursements for all farmers with a net payable balance > 0.
     */
    public function disburseEndMonthBalances()
    {
        // 1. Fetch all farmers and filter those whose computed 'net_payable' attribute is greater than zero.
        // net_payable = (milk collections * active route price) - current month debts.
        $farmers = Farmer::all()->filter(function ($farmer) {
            return $farmer->net_payable > 0;
        });

        // 2. Safety check: If no farmers have money to collect, exit early and inform the admin.
        if ($farmers->isEmpty()) {
            return response()->json([
                'status' => 'info',
                'message' => 'No eligible farmers found with a positive balance for payout.'
            ], 200);
        }

        // 3. Loop through every eligible farmer to process their individual payout.
        foreach ($farmers as $farmer) {
            $amountToPay = $farmer->net_payable;
            $phoneNumber = $farmer->phone; // Assuming 'phone' column stores the format 2547XXXXXXXX

            // 4. Wrap financial operations in a Database Transaction. 
            // If anything breaks halfway, DB::rollBack() prevents half-saved or corrupted records.
            DB::beginTransaction();
            try {
                // 5. Create a local ledger record marked as 'pending' BEFORE calling M-Pesa.
                // This gives you an audit trail even if network issues occur.
                $payout = FarmerPayout::create([
                    'farmer_id' => $farmer->farmer_id,
                    'amount' => $amountToPay,
                    'phone_number' => $phoneNumber,
                    'status' => 'pending'
                ]);

                // 6. Invoke Ian Kumu's package facade to hit Safaricom's B2C endpoint.
                // Parameters: (Amount, Phone Number, Remarks/Description)
                $response = Mpesa::b2c($amountToPay, $phoneNumber, 'End Month Milk Payout');

                // 7. Check if Daraja accepted the request into its processing queue (ResponseCode '0').
                if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
                    
                    // 8. Save Safaricom's tracking IDs so we can match the callback later.
                    $payout->update([
                        'conversation_id' => $response['ConversationID'] ?? null,
                        'originator_conversation_id' => $response['OriginatorConversationID'] ?? null,
                    ]);

                    // 9. Commit the database transaction since the API queue request succeeded.
                    DB::commit();

                } else {
                    // 10. If Safaricom rejected the request immediately (e.g., Paybill has insufficient utility funds),
                    // mark the audit log as failed and roll back.
                    $payout->update([
                        'status' => 'failed',
                        'result_desc' => json_encode($response)
                    ]);

                    DB::rollBack();
                }

            } catch (\Exception $e) {
                // 11. Catch any unexpected server crashes, roll back the transaction, 
                // and write the error to storage/logs/laravel.log for debugging.
                DB::rollBack();
                Log::error("B2C Payout Critical Exception for Farmer {$farmer->farmer_id}: " . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'End-month payout queue has been successfully dispatched.'
        ], 200);
    }

    /**
     * Handles Safaricom's asynchronous B2C result callback (Webhook).
     * This runs automatically in the background when Safaricom finishes processing the payment.
     */
    public function handleCallback(Request $request)
    {
        // 1. Capture everything Safaricom posts back to your server and write to logs for safety audits.
        $content = $request->all();
        Log::info('Mpesa B2C Callback Received:', $content);

        // 2. Extract key response indicators from the payload structure.
        $result = $content['Result'] ?? [];
        $originatorConversationId = $result['OriginatorConversationID'] ?? null;
        $resultCode = $result['ResultCode'] ?? null; // '0' means absolute success; non-zero means failure.
        $resultDesc = $result['ResultDesc'] ?? null;

        // 3. Find the pending payout in your database using the OriginatorConversationID tracking code.
        $payout = FarmerPayout::where('originator_conversation_id', $originatorConversationId)->first();

        // 4. Process only if the transaction exists in your local ledger.
        if ($payout) {
            
            // 5. Check if the payment transaction succeeded (ResultCode 0).
            if ($resultCode == 0) {
                
                // 6. Loop through Safaricom's result parameters to extract the official M-Pesa receipt number (e.g., QHG8...).
                $receiptNumber = null;
                if (isset($result['ResultParameters']['ResultParameter'])) {
                    foreach ($result['ResultParameters']['ResultParameter'] as $param) {
                        if ($param['Key'] === 'TransactionReceipt') {
                            $receiptNumber = $param['Value'];
                        }
                    }
                }

                // 7. Update the audit log to 'success' and save the official M-Pesa receipt code.
                $payout->update([
                    'status' => 'success',
                    'receipt_number' => $receiptNumber,
                    'result_desc' => $resultDesc
                ]);

            } else {
                
                // 8. Handle Failures: Safaricom returned an error code (e.g., M-Pesa full, subscriber barred, phone off).
                DB::beginTransaction();
                try {
                    // Mark the payout record as failed with the reason given by Safaricom.
                    $payout->update([
                        'status' => 'failed',
                        'result_desc' => $resultDesc
                    ]);

                    /* 
                     * NOTE ON BALANCES:
                     * Because your system computes account balances dynamically via active milk collections 
                     * minus debts, a failed transaction means no money actually left the Paybill utility account.
                     * However, since the payout failed, your admin dashboard should flag this specific farmer 
                     * so they can be re-queued or paid via manual cash/float once they clear their M-Pesa space.
                     */

                    DB::commit();

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Failed to handle callback failure state for Payout ID {$payout->id}: " . $e->getMessage());
                }
            }
        }

        // 9. MANDATORY: Always respond to Safaricom with ResultCode 0. 
        // This acknowledges that your server successfully received and processed their callback.
        return response()->json([
            'ResultCode' => 0, 
            'ResultDesc' => 'Accepted'
        ], 200);
    }
}