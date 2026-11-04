<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
//use Illuminate\Database\Eloquent\SoftDeletes;

class Farmer extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_id', // <-- Added to allow the system to save the auto-generated ID
        'name',
        'national_id',
        'phone',
        'route_id',
    ];

    /**
     * Intercept the creation of a farmer to auto-generate the alphanumeric ID.
     */


    protected static function booted(): void
    {
        static::creating(function ($farmer) {
            if (empty($farmer->farmer_id)) {
                // 1. Grab the highest alphanumeric ID present in the DB (e.g., "F0003")
                $lastFarmerId = self::max('farmer_id');

                if ($lastFarmerId) {
                    // 2. Strip out the letter 'F' to get the number "0003"
                    $numericPart = substr($lastFarmerId, 1);

                    // 3. Turn it into an integer and add 1 (gives us 4)
                    $nextNumber = (int)$numericPart + 1;

                    // 4. Pad it back out with left-side zeros and prepended 'F' -> "F0004"
                    $farmer->farmer_id = 'F' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                } else {
                    // Start here if it's the very first row in your database
                    $farmer->farmer_id = 'F0001';
                }
            }
        });
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function milkCollections(): HasMany
    {
        return $this->hasMany(MilkCollection::class, 'farmer_id', 'farmer_id');
    }

    public function debtTransactions(): HasMany
    {
        return $this->hasMany(Farmer_debt_transaction::class, 'farmer_id', 'farmer_id');
    }

    public function getCurrentMonthDebtAttribute(): float
    {
        return (float) $this->debtTransactions()
            ->where('transaction_type', 'debt')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount');
    }

    public function getNetPayableAttribute(): float
    {
        // Keep the total in SQL. We only need one number for the farmer balance,
        // so we avoid loading the month collection rows into PHP and summing them there.
        $currentMonthEarnings = MilkCollection::query()
            ->where('farmer_id', $this->farmer_id)
            ->whereMonth('collection_date', now()->month)
            ->whereYear('collection_date', now()->year)
            ->selectRaw('COALESCE(SUM(qty * (
                SELECT mp.price_per_liter
                FROM milk_prices mp
                WHERE mp.route_id = ?
                  AND mp.is_active = 1
                  AND mp.effective_date <= milk_collections.collection_date
                ORDER BY mp.effective_date DESC, mp.id DESC
                LIMIT 1
            )), 0) as total_earnings', [$this->route_id])
            ->value('total_earnings');

        return (float) (($currentMonthEarnings ?? 0.0) - $this->current_month_debt);
    }

    public function getAccountBalanceAttribute(): float
    {
        return $this->net_payable;
    }

    public function getLast30DaysDebtHistoryAttribute(): string
    {
        // This is a small, row-based detail list for the farmer view, so fetching the
        // last 30 days of rows is intentional and remains within a tight limit.
        $history = $this->debtTransactions()
            ->whereDate('transaction_date', '>=', now()->subDays(30)->toDateString())
            ->orderByDesc('transaction_date')
            ->get();

        if ($history->isEmpty()) {
            return 'No debt activity in the last 30 days.';
        }

        return $history
            ->map(fn ($transaction) => sprintf(
                '%s — KES %s%s',
                $transaction->transaction_date ? $transaction->transaction_date->format('d M Y') : '--',
                number_format((float) $transaction->amount, 2),
                $transaction->reason ? ' (' . $transaction->reason . ')' : ''
            ))
            ->implode(PHP_EOL);
    }

    public function getLast30DaysDebtHistoryTableAttribute(): string
    {
        // This remains a row query because the farmer detail page needs the actual debt entries
        // rendered in a compact table; we keep the result bounded to 30 days.
        $history = $this->debtTransactions()
            ->whereDate('transaction_date', '>=', now()->subDays(30)->toDateString())
            ->orderByDesc('transaction_date')
            ->get();

        if ($history->isEmpty()) {
            return '<div style="color:#6b7280; font-size: 0.875rem;">No debt activity in the last 30 days.</div>';
        }

        $rows = $history->map(function ($transaction) {
            $date = $transaction->transaction_date ? $transaction->transaction_date->format('d M Y') : '--';
            $amount = 'KES ' . number_format((float) $transaction->amount, 2);
            $reason = $transaction->reason ?: '—';

            return "<tr><td style='padding:6px 8px;border-bottom:1px solid #e5e7eb;'>$date</td><td style='padding:6px 8px;border-bottom:1px solid #e5e7eb;'>$amount</td><td style='padding:6px 8px;border-bottom:1px solid #e5e7eb;'>$reason</td></tr>";
        })->implode('');

        return '<table style="width:100%; border-collapse:collapse; font-size:0.875rem;">'
            . '<thead><tr><th style="text-align:left; padding:6px 8px; border-bottom:1px solid #d1d5db; font-weight:600;">Date</th>'
            . '<th style="text-align:left; padding:6px 8px; border-bottom:1px solid #d1d5db; font-weight:600;">Amount</th>'
            . '<th style="text-align:left; padding:6px 8px; border-bottom:1px solid #d1d5db; font-weight:600;">Reason</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table>';
    }

    public function getLast30DaysMilkCollectionHistoryAttribute(): string
    {
        // This also stays as a row query because the page needs the real collection entries,
        // not just a total. The date range keeps it efficient and readable.
        $history = $this->milkCollections()
            ->whereDate('collection_date', '>=', now()->subDays(30)->toDateString())
            ->orderByDesc('collection_date')
            ->get();

        if ($history->isEmpty()) {
            return 'No milk collections in the last 30 days.';
        }

        return $history
            ->map(fn ($collection) => sprintf(
                '%s — %s L',
                $collection->collection_date ? $collection->collection_date->format('d M Y') : '--',
                number_format((float) $collection->qty, 2)
            ))
            ->implode(PHP_EOL);
    }

    public function getLast30DaysMilkCollectionHistoryTableAttribute(): string
    {
        // Same rule as above: row-based display remains a limited result set,
        // while numeric totals continue to be computed in SQL at the database layer.
        $history = $this->milkCollections()
            ->whereDate('collection_date', '>=', now()->subDays(30)->toDateString())
            ->orderByDesc('collection_date')
            ->get();

        if ($history->isEmpty()) {
            return '<div style="color:#6b7280; font-size: 0.875rem;">No milk collections in the last 30 days.</div>';
        }

        $rows = $history->map(function ($collection) {
            $date = $collection->collection_date ? $collection->collection_date->format('d M Y') : '--';
            $qty = number_format((float) $collection->qty, 2) . ' L';

            return "<tr><td style='padding:6px 8px;border-bottom:1px solid #e5e7eb;'>$date</td><td style='padding:6px 8px;border-bottom:1px solid #e5e7eb;'>$qty</td></tr>";
        })->implode('');

        return '<table style="width:100%; border-collapse:collapse; font-size:0.875rem;">'
            . '<thead><tr><th style="text-align:left; padding:6px 8px; border-bottom:1px solid #d1d5db; font-weight:600;">Date</th>'
            . '<th style="text-align:left; padding:6px 8px; border-bottom:1px solid #d1d5db; font-weight:600;">Qty</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table>';
    }

    // use SoftDeletes;
}