<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesReportController extends Controller
{
    private const MAX_DAYS = 366;

    public function download(Request $request)
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($data['from'])->toDateString();
        $to   = Carbon::parse($data['to'])->toDateString();

        if (Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1 > self::MAX_DAYS) {
            throw ValidationException::withMessages([
                'to' => 'Please choose a range of ' . self::MAX_DAYS . ' days or less.',
            ]);
        }

        // Sales per day: litres and revenue (price is stored on each sale row)
        $sales = Sale::query()
            ->whereDate('sale_date', '>=', $from)
            ->whereDate('sale_date', '<=', $to)
            ->selectRaw('DATE(sale_date) as d, SUM(qty) as qty, SUM(qty * sale_price_per_liter) as revenue')
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        // Farmer earnings per day, using the same price lookup as the farmer report
        $earnings = DB::table('milk_collections as mc')
            ->join('farmers as f', 'f.farmer_id', '=', 'mc.farmer_id')
            ->whereDate('mc.collection_date', '>=', $from)
            ->whereDate('mc.collection_date', '<=', $to)
            ->selectRaw('DATE(mc.collection_date) as d, SUM(mc.qty * (
                SELECT mp.price_per_liter FROM milk_prices mp
                WHERE mp.route_id = f.route_id
                  AND mp.is_active = 1
                  AND mp.effective_date <= mc.collection_date
                ORDER BY mp.effective_date DESC, mp.id DESC
                LIMIT 1
            )) as earnings')
            ->groupBy('d')
            ->pluck('earnings', 'd');

        $rows = collect(CarbonPeriod::create($from, $to))->map(function ($date) use ($sales, $earnings) {
            $d       = $date->toDateString();
            $qty     = (float) ($sales[$d]->qty ?? 0);
            $revenue = (float) ($sales[$d]->revenue ?? 0);
            $farmer  = (float) ($earnings[$d] ?? 0);

            return [
                'date'     => $date,
                'qty'      => $qty,
                // Weighted average, in case several sales on one day used different prices
                'price'    => $qty > 0 ? $revenue / $qty : 0,
                'revenue'  => $revenue,
                'earnings' => $farmer,
                'net'      => $revenue - $farmer,
            ];
        });

        $totals = [
            'qty'      => $rows->sum('qty'),
            'revenue'  => $rows->sum('revenue'),
            'earnings' => $rows->sum('earnings'),
            'net'      => $rows->sum('net'),
        ];
        $totals['price'] = $totals['qty'] > 0 ? $totals['revenue'] / $totals['qty'] : 0;

        return Pdf::loadView('reports.sales', compact('rows', 'totals', 'from', 'to'))
            ->setPaper('a4', 'portrait')
            ->download("sales-report-{$from}-to-{$to}.pdf");
    }
}
