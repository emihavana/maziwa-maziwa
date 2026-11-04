<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Farmer;
use App\Models\User;
use App\Models\Sale;
use App\Models\Route;
use App\Models\Farmer_debt_transaction;
use App\Models\MilkCollection;
use App\Models\MilkPrice;

class StatsOverview extends BaseWidget
{
    // Auto-refresh every 30 seconds (replaces your old AJAX polling)
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        // We intentionally do the total in SQL instead of loading all collections into PHP.
        // This keeps the dashboard fast and avoids the N+1/whole-table-load pattern.
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

        $totalRevenueThisMonth = Sale::query()
            ->whereBetween('sale_date', [$monthStart, $monthEnd])
            ->selectRaw('SUM(qty * sale_price_per_liter) as total_revenue')
            ->value('total_revenue') ?? 0;

        $netFarmerEarnings = $farmerEarnings - $totalDebtThisMonth;

        return [
            Stat::make('Total Farmers', Farmer::count())
                ->description('Active in system')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Total users', User::count())
                ->description('Active in system')
                ->icon('heroicon-o-users')
                ->color('info'),

            Stat::make('Today’s Milk Collection', MilkCollection::whereDate('collection_date', today())->sum('qty') . ' L')
                ->description(now()->format('d M Y'))
                ->icon('heroicon-o-beaker')
                ->color('success'),

            Stat::make('This Month\'s Milk Collection', MilkCollection::whereBetween('collection_date', [$monthStart, $monthEnd])->sum('qty') . ' L')
                ->description(now()->format('M Y'))
                ->icon('heroicon-o-beaker')
                ->color('success'),

            Stat::make('Today’s Milk sold', number_format(Sale::whereDate('sale_date', today())->sum('qty'), 2) . ' L')
                ->description(now()->format('d M Y'))
                ->icon('heroicon-o-beaker')
                ->color('success'),

            Stat::make('This month\'s Milk sold', round(Sale::whereBetween('sale_date', [$monthStart, $monthEnd])->sum('qty'), 1) . ' L')
                ->description(now()->format('d M Y'))
                ->icon('heroicon-o-beaker')
                ->color('success'),

            Stat::make('This Month\'s TOTAL debt', 'KES ' . number_format($totalDebtThisMonth, 2))
                ->description(now()->format('M Y'))
                ->icon('heroicon-o-users')
                ->color('danger'),

            Stat::make('This Month\'s farmers with debt', Farmer_debt_transaction::where('transaction_type', 'debt')
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->distinct()
                ->count('farmer_id'))
                ->description(now()->format('M Y'))
                ->icon('heroicon-o-users')
                ->color('warning'),

            Stat::make('Total Routes', Route::count())
                ->description('Active in system')
                ->icon('heroicon-o-users')
                ->color('success'),

            Stat::make('Farmer Earnings', 'KES ' . number_format($farmerEarnings, 2))
                ->description('Milk collections this month')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Total Revenue', 'KES ' . number_format($totalRevenueThisMonth, 2))
                ->description('From milk sold this month - farmers earnings')
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
