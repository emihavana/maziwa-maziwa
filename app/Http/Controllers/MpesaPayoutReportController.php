<?php

namespace App\Http\Controllers;

use App\Models\FarmerPayout;
use App\Models\Route as RouteModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MpesaPayoutReportController extends Controller
{
    private const MAX_DAYS = 62;

    public function download(Request $request)
    {
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

        if (Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1 > self::MAX_DAYS) {
            throw ValidationException::withMessages([
                'to' => 'Please choose a range of ' . self::MAX_DAYS . ' days or less.',
            ]);
        }

        // Query payouts with farmer and route relationships, date boundaries, and optional filters
        $payouts = FarmerPayout::with(['farmer.route'])
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($routeIds, function ($q) use ($routeIds) {
                $q->whereHas('farmer', fn($query) => $query->whereIn('route_id', $routeIds));
            })
            ->latest()
            ->get();

        // Calculate metrics for summary display
        $totalAmount     = $payouts->sum('amount');
        $successfulCount = $payouts->where('status', 'success')->count();
        $failedCount     = $payouts->where('status', 'failed')->count();
        $pendingCount    = $payouts->where('status', 'pending')->count();

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
            'successfulCount',
            'failedCount',
            'pendingCount'
        ))
            ->setPaper('a4', 'landscape')
            ->download($fileName);
    }
}