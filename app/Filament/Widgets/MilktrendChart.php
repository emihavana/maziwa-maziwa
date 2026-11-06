<?php

namespace App\Filament\Widgets;

use App\Models\MilkCollection;
use App\Models\Sale;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;

class MilkTrendChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return 'Milk trends (last 3 months)';
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        // From the 1st of the month two months ago, up to today.
        // startOfMonth() first, so subMonths() can't overflow (e.g. 31 Dec -> 31 Nov).
        $start = now()->startOfMonth()->subMonths(2);
        $end   = now()->endOfDay();

        // Daily totals computed in SQL
        $collected = MilkCollection::query()
            ->whereBetween('collection_date', [$start, $end])
            ->selectRaw('DATE(collection_date) as d, SUM(qty) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $sold = Sale::query()
            ->whereBetween('sale_date', [$start, $end])
            ->selectRaw('DATE(sale_date) as d, SUM(qty) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $days = collect(CarbonPeriod::create($start->toDateString(), now()->toDateString()));

        return [
            'datasets' => [
                [
                    'label'           => 'Collected (L)',
                    'data'            => $days->map(fn($d) => (float) ($collected[$d->toDateString()] ?? 0))->all(),
                    'borderColor'     => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.15)',
                    'tension'         => 0.3,
                    'pointRadius'     => 0,
                    'fill'            => false,
                ],
                [
                    'label'           => 'Sold (L)',
                    'data'            => $days->map(fn($d) => (float) ($sold[$d->toDateString()] ?? 0))->all(),
                    'borderColor'     => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
                    'tension'         => 0.3,
                    'pointRadius'     => 0,
                    'fill'            => false,
                ],
            ],
            'labels' => $days->map(fn($d) => $d->format('d M'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => [
                    'ticks' => ['maxTicksLimit' => 12],
                ],
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
