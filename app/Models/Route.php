<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = [
        'name',
    ];

    public function farmers(): HasMany
    {
        return $this->hasMany(Farmer::class);
    }

    public function milkPrices(): HasMany
    {
        return $this->hasMany(MilkPrice::class, 'route_id');
    }

    public function milkCollections(): HasMany
    {
        return $this->hasMany(MilkCollection::class, 'route_id');
    }

    public function currentMilkPrice(): ?MilkPrice
    {
        return $this->milkPrices()
            ->where('is_active', true)
            ->orderByDesc('effective_date')
            ->first();
    }
}
