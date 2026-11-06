<?php

namespace App\Http\Controllers;

use App\Models\Farmer;
use App\Models\Farmer_debt_transaction;
use App\Models\MilkCollection;
use App\Models\Route as RouteModel; // aliased to avoid clashing with the Route facade
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FarmerReportController extends Controller
{
    private const MAX_DAYS = 62;

    public function download(Request $request)
    {
        $data = $request->validate([
            'from'      => ['required', 'date'],
            'to'        => ['required', 'date', 'after_or_equal:from'],
            'routes'    => ['nullable', 'array'],
            'routes.*'  => ['integer', 'exists:routes,id'],
            'sort_by'   => ['nullable', 'in:name,route,total_qty,debt,net'],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);

        $from      = Carbon::parse($data['from'])->toDateString();
        $to        = Carbon::parse($data['to'])->toDateString();
        $routeIds  = $data['routes'] ?? [];      // empty = all routes
        $sortBy    = $data['sort_by'] ?? 'name';
        $direction = $data['direction'] ?? 'asc';

        if (Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1 > self::MAX_DAYS) {
            throw ValidationException::withMessages([
                'to' => 'Please choose a range of ' . self::MAX_DAYS . ' days or less.',
            ]);
        }

        // Column headers: one per date in range
        $dates = collect(CarbonPeriod::create($from, $to))
            ->map(fn($d) => $d->toDateString());

        // Daily litres per farmer: [farmer_id => [date => qty]]
        $daily = MilkCollection::query()
            ->whereBetween('collection_date', [$from, $to])
            ->selectRaw('farmer_id, DATE(collection_date) as d, SUM(qty) as qty')
            ->groupBy('farmer_id', 'd')
            ->get()
            ->groupBy('farmer_id')
            ->map(fn($rows) => $rows->pluck('qty', 'd'));

        // Earnings in range, using the same price lookup as the Farmer accessor
        $earnings = DB::table('milk_collections as mc')
            ->join('farmers as f', 'f.farmer_id', '=', 'mc.farmer_id')
            ->whereBetween('mc.collection_date', [$from, $to])
            ->selectRaw('mc.farmer_id, SUM(mc.qty * (
                SELECT mp.price_per_liter FROM milk_prices mp
                WHERE mp.route_id = f.route_id
                  AND mp.is_active = 1
                  AND mp.effective_date <= mc.collection_date
                ORDER BY mp.effective_date DESC, mp.id DESC
                LIMIT 1
            )) as earnings')
            ->groupBy('mc.farmer_id')
            ->pluck('earnings', 'farmer_id');

        // Debt in range
        $debts = Farmer_debt_transaction::query()
            ->where('transaction_type', 'debt')
            ->whereBetween('transaction_date', [$from, $to])
            ->selectRaw('farmer_id, SUM(amount) as total')
            ->groupBy('farmer_id')
            ->pluck('total', 'farmer_id');

        $farmers = Farmer::with('route')
            ->when($routeIds, fn($q) => $q->whereIn('route_id', $routeIds))
            ->get()
            ->map(function ($f) use ($daily, $earnings, $debts) {
                $f->daily     = $daily[$f->farmer_id] ?? collect();
                $f->total_qty = (float) $f->daily->sum();
                $f->debt      = (float) ($debts[$f->farmer_id] ?? 0);
                $f->net       = (float) ($earnings[$f->farmer_id] ?? 0) - $f->debt;

                return $f;
            });

        // Sorted in PHP because the totals are computed after the queries
        $farmers = $farmers->sortBy(
            fn($f) => match ($sortBy) {
                'route'     => [strtolower((string) $f->route?->name), strtolower($f->name)],
                'total_qty' => $f->total_qty,
                'debt'      => $f->debt,
                'net'       => $f->net,
                default     => strtolower($f->name),
            },
            SORT_REGULAR,
            $direction === 'desc'
        )->values();

        $routeLabel = $routeIds
            ? RouteModel::whereIn('id', $routeIds)->pluck('name')->implode(', ')
            : 'All routes';

        $fileName = "farmers-report-{$from}-to-{$to}.pdf";

        return Pdf::loadView('reports.farmers', compact('farmers', 'dates', 'from', 'to', 'routeLabel'))
            ->setPaper('a4', 'landscape')
            ->download($fileName);
    }
}
