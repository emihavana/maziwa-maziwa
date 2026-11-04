<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MilkPrice extends Model
{
    protected $fillable = [
        'route_id',
        'price_per_liter',
        'effective_date',
        'is_active',
    ];

    // The method name MUST be 'route' to match 'route.name'
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public static function getPriceOnDate(int $routeId, string $date): float
    {
        $price = static::query()
            ->where('route_id', $routeId)
            ->where('effective_date', '<=', $date)
            ->where('is_active', true)
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();

        return (float) ($price?->price_per_liter ?? 0.0);
    }

    public static function getPriceMapForRoute(int $routeId, array $dates): array
    {
        // This helper is still allowed to work in PHP because it maps a small set of dates
        // for a single route, while the actual total computation remains in the database.
        $normalizedDates = collect($dates)
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values()
            ->all();

        if ($normalizedDates === []) {
            return [];
        }

        $prices = static::query()
            ->where('route_id', $routeId)
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', max($normalizedDates))
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->get();

        $priceMap = [];

        foreach ($normalizedDates as $dateString) {
            $selectedPrice = $prices->first(function ($price) use ($dateString) {
                return Carbon::parse($price->effective_date)->toDateString() <= $dateString;
            });

            $priceMap[$dateString] = (float) ($selectedPrice?->price_per_liter ?? 0.0);
        }

        return $priceMap;
    }
}
