<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'sale_date',
        'qty',
        'sale_price_per_liter',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'qty' => 'decimal:2',
        'sale_price_per_liter' => 'decimal:2',
    ];

    public static function getRevenueForPeriod(string $startDate, string $endDate): float
    {
        return (float) static::query()
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->selectRaw('SUM(qty * sale_price_per_liter) as total_revenue')
            ->value('total_revenue') ?? 0.0;
    }
}
