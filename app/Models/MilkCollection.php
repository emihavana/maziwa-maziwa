<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilkCollection extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'farmer_id',
        'collector_id',
        'route_id',
        'qty',
        'collection_date',
        'is_correction',
        'correction_reason',
        'corrected_by',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'collection_date' => 'date',
        'is_correction' => 'boolean',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class, 'farmer_id', 'farmer_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }
    
public function route(): BelongsTo
{
    return $this->belongsTo(Route::class);
}
protected static function booted(): void
{
    static::creating(function ($collection) {
        if (empty($collection->route_id)) {
            $collection->route_id = Farmer::where('farmer_id', $collection->farmer_id)
                ->value('route_id');
        }
    });
}
}