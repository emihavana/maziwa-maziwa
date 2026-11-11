<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Farmer;
use App\Models\Farmer_debt_transaction;
use App\Models\MilkCollection;
use App\Models\Route;
use App\Models\Sale;
use App\Models\User;

class StatsOverview extends BaseWidget
{
    // Auto-refresh every 30 seconds (replaces your old AJAX polling)
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

        // We intentionally do the total in SQL instead of loading all collections into PHP.
        // This keeps the dashboard fast and avoids the N+1/whole-table-load pattern.
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
        ];
    }
}