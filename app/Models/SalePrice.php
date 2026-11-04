<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalePrice extends Model
{
    protected $fillable = [
        'price_per_liter',
        'effective_date',
        'is_active',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'is_active' => 'boolean',
    ];

    public static function hasDuplicateEffectiveDate(?string $date, ?int $ignoreId = null): bool
    {
        if (! $date) {
            return false;
        }

        return static::query()
            ->whereDate('effective_date', $date)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    public static function getPriceOnDate(string $date): float
    {
        $price = static::query()
            ->where('effective_date', '<=', $date)
            ->where('is_active', true)
            ->orderByDesc('effective_date')
            ->first();

        return (float) ($price?->price_per_liter ?? 0.0);
    }
}
