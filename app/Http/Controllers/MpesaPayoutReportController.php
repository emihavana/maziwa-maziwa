<?php

namespace App\Http\Controllers;

use App\Models\FarmerPayout;
use App\Models\Route as RouteModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Builds the "M-Pesa B2C Payout Audit & Transaction Report" PDF.
 *
 * Every payout ATTEMPT is listed (a retry is a new row), so the table is an
 * audit log. The summary separates confirmed money from pending and failed.
 */
class MpesaPayoutReportController extends Controller
{
    /** Longest date range allowed in one report. */
    private const MAX_DAYS = 62;

    public function download(Request $request)
    {
        // 1. Validate the filters from the form.
        $data = $request->validate([
            'from'     => ['required', 'date'],
            'to'       => ['required', 'date', 'after_or_equal:from'],
            'status'   => ['nullable', 'in:success,failed,pending'],
            'routes'   => ['nullable', 'array'],
            'routes.*' => ['integer', 'exists:routes,id'],
        ]);

        $from     = Carbon::parse($data['from'])->toDateString();
        $to       = Carbon::parse($data['to'])->toDateString();
        $status   = $data['status'] ?? null;
        $routeIds = $data['routes'] ?? [];

        // 2. Keep the report small enough to render.
        if (Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1 > self::MAX_DAYS) {
            throw ValidationException::withMessages([
                'to' => 'Please choose a range of ' . self::MAX_DAYS . ' days or less.',
            ]);
        }

        // 3. Reusable base query: date range + optional route filter.
        //    The status filter is applied separately, because the summary
        //    must cover ALL statuses even when the table shows only one.
        $base = fn () => FarmerPayout::query()
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($routeIds, function ($q) use ($routeIds) {
                $q->whereHas('farmer', fn ($query) => $query->whereIn('route_id', $routeIds));
            });

        // 4. Rows shown in the table (honours the status filter).
        $payouts = $base()
            ->with(['farmer.route'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->get();

        // 5. Summary: one grouped query, covering every status.
        $totals = $base()
            ->selectRaw('status, COUNT(*) as total_count, COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // Confirmed by M-Pesa: this is the ONLY money that counts as disbursed.
        $successfulCount = (int) ($totals->get('success')?->total_count ?? 0);
        $totalAmount     = (float) ($totals->get('success')?->total_amount ?? 0);

        // Sent to Safaricom, result not received yet.
        $pendingCount  = (int) ($totals->get('pending')?->total_count ?? 0);
        $pendingAmount = (float) ($totals->get('pending')?->total_amount ?? 0);

        // Every failed ATTEMPT (includes ones that were retried and paid later).
        $failedCount  = (int) ($totals->get('failed')?->total_count ?? 0);
        $failedAmount = (float) ($totals->get('failed')?->total_amount ?? 0);

        // 6. Farmers whose payout failed and who have NOT been paid or queued
        //    since. This is the money actually still owed.
        $resolvedFarmerIds = $base()
            ->whereIn('status', ['success', 'pending'])
            ->pluck('farmer_id');

        $unpaid = $base()
            ->where('status', 'failed')
            ->whereNotIn('farmer_id', $resolvedFarmerIds)
            ->selectRaw('farmer_id, MAX(amount) as amount')
            ->groupBy('farmer_id')
            ->get();

        $unpaidCount  = $unpaid->count();
        $unpaidAmount = (float) $unpaid->sum('amount');

        // 7. Labels printed in the report header.
        $routeLabel = $routeIds
            ? RouteModel::whereIn('id', $routeIds)->pluck('name')->implode(', ')
            : 'All Routes';

        $statusLabel = $status ? ucfirst($status) . ' Only' : 'All Statuses';

        $fileName = "mpesa-payout-report-{$from}-to-{$to}.pdf";

        return Pdf::loadView('reports.mpesa-payouts', compact(
            'payouts',
            'from',
            'to',
            'statusLabel',
            'routeLabel',
            'totalAmount',
            'pendingAmount',
            'failedAmount',
            'unpaidCount',
            'unpaidAmount',
            'successfulCount',
            'failedCount',
            'pendingCount'
        ))
            ->setPaper('a4', 'landscape')
            ->download($fileName);
    }
}