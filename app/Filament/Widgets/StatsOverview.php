<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use App\Models\Farmer;
use App\Models\Farmer_debt_transaction;
use App\Models\FarmerPayout;
use App\Models\MilkCollection;
use App\Models\Route;
use App\Models\Sale;
use App\Models\User;

class StatsOverview extends BaseWidget
{
    // Auto-refresh every 60 seconds
    protected ?string $pollingInterval = '60s';
    protected int | string | array $columnSpan = 'full';

    protected function getColumns(): int | array
    {
        return [
            'default' => 2,
            'md' => 3,
            'xl' => 4,
        ];
    }

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        // Farmer earnings = total milk collected for the month valued at each route's effective price.
        $farmerEarnings = MilkCollection::query()
            ->whereBetween('collection_date', [$monthStart, $monthEnd])
            ->join('farmers as f', 'f.farmer_id', '=', 'milk_collections.farmer_id')
            ->selectRaw('COALESCE(SUM(milk_collections.qty * (
                SELECT mp.price_per_liter
                FROM milk_prices mp
                WHERE mp.route_id = f.route_id
                  AND mp.is_active = 1
                  AND mp.effective_date <= milk_collections.collection_date
                ORDER BY mp.effective_date DESC, mp.id DESC
                LIMIT 1
            )), 0) as total_earnings')
            ->value('total_earnings') ?? 0;

        $totalDebtThisMonth = Farmer_debt_transaction::query()
            ->where('transaction_type', 'debt')
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->sum('amount');

        // --- TODAY'S REVENUE (Sales minus Farmer Earnings) ---
        $todayGrossRevenue = Sale::whereDate('sale_date', today())
            ->selectRaw('COALESCE(SUM(qty * sale_price_per_liter), 0) as total_revenue')
            ->value('total_revenue') ?? 0;

        $todayFarmerEarnings = MilkCollection::query()
            ->whereDate('collection_date', today())
            ->join('farmers as f', 'f.farmer_id', '=', 'milk_collections.farmer_id')
            ->selectRaw('COALESCE(SUM(milk_collections.qty * (
                SELECT mp.price_per_liter
                FROM milk_prices mp
                WHERE mp.route_id = f.route_id
                  AND mp.is_active = 1
                  AND mp.effective_date <= milk_collections.collection_date
                ORDER BY mp.effective_date DESC, mp.id DESC
                LIMIT 1
            )), 0) as total_earnings')
            ->value('total_earnings') ?? 0;

        $todayRevenue = $todayGrossRevenue - $todayFarmerEarnings;

        // --- THIS MONTH'S REVENUE (Sales minus Farmer Earnings) ---
        $monthGrossRevenue = Sale::query()
            ->whereBetween('sale_date', [$monthStart, $monthEnd])
            ->selectRaw('COALESCE(SUM(qty * sale_price_per_liter), 0) as total_revenue')
            ->value('total_revenue') ?? 0;

        $monthFarmerEarnings = MilkCollection::query()
            ->whereYear('collection_date', now()->year)
            ->whereMonth('collection_date', now()->month)
            ->join('farmers as f', 'f.farmer_id', '=', 'milk_collections.farmer_id')
            ->selectRaw('COALESCE(SUM(milk_collections.qty * (
                SELECT mp.price_per_liter
                FROM milk_prices mp
                WHERE mp.route_id = f.route_id
                  AND mp.is_active = 1
                  AND mp.effective_date <= milk_collections.collection_date
                ORDER BY mp.effective_date DESC, mp.id DESC
                LIMIT 1
            )), 0) as total_earnings')
            ->value('total_earnings') ?? 0;

        $monthRevenue = $monthGrossRevenue - $monthFarmerEarnings;

        $totalRevenueThisMonth = $monthRevenue;
        $netFarmerEarnings = $farmerEarnings - $totalDebtThisMonth;

        $todayCollectionQty = MilkCollection::whereDate('collection_date', today())->sum('qty');
        $monthCollectionQty = MilkCollection::whereBetween('collection_date', [$monthStart, $monthEnd])->sum('qty');
        $todaySaleQty = Sale::whereDate('sale_date', today())->sum('qty');
        $monthSaleQty = Sale::whereBetween('sale_date', [$monthStart, $monthEnd])->sum('qty');

        // ------------------------------------------------------------------
        // M-PESA B2C PAYOUT CARDS
        // ------------------------------------------------------------------

        // 1. Pick the payout cycle to display: the month of the most recent
        //    payout attempt. Payouts run on the last day of a month, so this
        //    keeps last month's results visible during the new month, and it
        //    also works for sandbox tests done on any day.
        $latestPayoutAt = FarmerPayout::query()->latest('created_at')->value('created_at');
        $cycle = $latestPayoutAt ? Carbon::parse($latestPayoutAt) : now();
        $cycleStart = $cycle->copy()->startOfMonth();
        $cycleEnd = $cycle->copy()->endOfMonth();
        $cycleLabel = $cycle->format('M Y');

        // 2. One query: count and total amount per status for that cycle.
        $payoutStats = FarmerPayout::query()
            ->whereBetween('created_at', [$cycleStart, $cycleEnd])
            ->selectRaw('status, COUNT(*) as total_count, COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // SENT & CONFIRMED: M-Pesa callback said success.
        $sentCount = (int) ($payoutStats->get('success')?->total_count ?? 0);
        $sentAmount = (float) ($payoutStats->get('success')?->total_amount ?? 0);

        // PENDING: accepted by Safaricom, final result not received yet.
        $pendingCount = (int) ($payoutStats->get('pending')?->total_count ?? 0);
        $pendingAmount = (float) ($payoutStats->get('pending')?->total_amount ?? 0);

        // STUCK PENDING: still pending after 30 minutes, so the callback
        // probably never arrived and someone must check the M-Pesa statement.
        $stuckPendingCount = FarmerPayout::query()
            ->where('status', 'pending')
            ->whereBetween('created_at', [$cycleStart, $cycleEnd])
            ->where('updated_at', '<', now()->subMinutes(30))
            ->count();

        // FAILED (STILL UNPAID): a retry creates a new row, so old failed rows
        // of farmers who were later paid (or are pending) must not be counted.
        $resolvedFarmerIds = FarmerPayout::query()
            ->whereBetween('created_at', [$cycleStart, $cycleEnd])
            ->whereIn('status', ['success', 'pending'])
            ->pluck('farmer_id');

        // One entry per farmer (largest failed amount), so several failed
        // retries for the same farmer are not added up twice.
        $unpaidFailed = FarmerPayout::query()
            ->where('status', 'failed')
            ->whereBetween('created_at', [$cycleStart, $cycleEnd])
            ->whereNotIn('farmer_id', $resolvedFarmerIds)
            ->selectRaw('farmer_id, MAX(amount) as amount')
            ->groupBy('farmer_id')
            ->get();

        $failedCount = $unpaidFailed->count();
        $failedAmount = (float) $unpaidFailed->sum('amount');

        return [
            Stat::make('Total Farmers', Farmer::count())
                ->description('Active in system')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Total Users', User::count())
                ->description('Active in system')
                ->icon('heroicon-o-users')
                ->color('info'),

            Stat::make('Milk Collected', number_format($todayCollectionQty, 2) . ' L')
                ->description('This month: ' . number_format($monthCollectionQty, 2) . ' L')
                ->icon('heroicon-o-beaker')
                ->color('success'),

            Stat::make('Milk Sold', number_format($todaySaleQty, 2) . ' L')
                ->description('This month: ' . number_format($monthSaleQty, 2) . ' L')
                ->icon('heroicon-o-beaker')
                ->color('success'),

            Stat::make('This Month\'s TOTAL Debt', 'KES ' . number_format($totalDebtThisMonth, 2))
                ->description('Current debt balance')
                ->icon('heroicon-o-users')
                ->color('danger'),

            Stat::make('Total Routes', Route::count())
                ->description('Active in system')
                ->icon('heroicon-o-users')
                ->color('success'),

            Stat::make('Farmer Earnings', 'KES ' . number_format($todayFarmerEarnings, 2))
                ->description('This month: KES ' . number_format($farmerEarnings, 2))
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Net Revenue', 'KES ' . number_format($todayRevenue, 2))
                ->description('This month: KES ' . number_format($monthRevenue, 2))
                ->icon('heroicon-o-banknotes')
                ->color('info'),

            Stat::make('Net Farmer Earnings', 'KES ' . number_format($netFarmerEarnings, 2))
                ->description('Farmers earnings minus this month debt')
                ->icon('heroicon-o-arrow-trending-down')
                ->color($netFarmerEarnings < 0 ? 'danger' : 'success'),

            Stat::make('Average per Collection', round(MilkCollection::avg('qty'), 1) . ' L')
                ->description('Per transaction')
                ->icon('heroicon-o-chart-bar')
                ->color('info'),

            // --- M-PESA B2C PAYOUT CARDS ---

            // Green: money M-Pesa has confirmed as delivered.
            Stat::make("Sent & Confirmed ({$cycleLabel})", 'KES ' . number_format($sentAmount, 2))
                ->description("{$sentCount} payment(s) confirmed by M-Pesa")
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            // Yellow: waiting for Safaricom. Warns when some look stuck.
            Stat::make("Pending Confirmation ({$cycleLabel})", 'KES ' . number_format($pendingAmount, 2))
                ->description(
                    "{$pendingCount} awaiting M-Pesa result"
                    . ($stuckPendingCount > 0 ? " - {$stuckPendingCount} stuck over 30 min, check statement" : '')
                )
                ->icon('heroicon-o-clock')
                ->color($stuckPendingCount > 0 ? 'danger' : ($pendingCount > 0 ? 'warning' : 'gray')),

            // Red: farmers still unpaid after a failure. They are retried on the next run.
            Stat::make("Failed, Still Unpaid ({$cycleLabel})", 'KES ' . number_format($failedAmount, 2))
                ->description("{$failedCount} farmer(s) need a retry")
                ->icon('heroicon-o-exclamation-triangle')
                ->color($failedCount > 0 ? 'danger' : 'success'),
        ];
    }
}