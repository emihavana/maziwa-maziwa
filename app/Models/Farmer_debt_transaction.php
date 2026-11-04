<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Farmer_debt_transaction extends Model
{
    protected static function booted()
    {
        static::created(function ($transaction) {
            $transaction->farmer->increment('debt_balance', $transaction->amount);
        });
    }

    public $timestamps = false;

    protected $fillable = [
        'farmer_id',
        'amount',
        'transaction_type',
        'reason',
        'transaction_date',
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function farmer()
    {
        return $this->belongsTo(\App\Models\Farmer::class, 'farmer_id', 'farmer_id');
    }
}
