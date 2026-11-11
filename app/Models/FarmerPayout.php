<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmerPayout extends Model
{
    // Fields that can be mass-assigned securely
    protected $fillable = [
        'farmer_id',
        'amount',
        'phone_number',
        'conversation_id',
        'originator_conversation_id',
        'receipt_number',
        'status',
        'result_desc',
    ];

    /**
     * Relationship: A payout belongs to a specific farmer.
     */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class, 'farmer_id', 'farmer_id');
    }
}