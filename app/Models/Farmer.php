<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Farmer extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_id', // Alphanumeric custom ID (e.g., F0001)
        'name',
        'national_id',
        'phone',
        'route_id',
    ];

    /**
     * Intercept the creation of a farmer to auto-generate sequential alphanumeric IDs.
     */
    protected static function booted(): void
    {
        static::creating(function ($farmer) {
            if (empty($farmer->farmer_id)) {
                // Grab the highest alphanumeric ID present in the DB (e.g., "F0003")
                $lastFarmerId = self::max('farmer_id');

                if ($lastFarmerId) {
                    // Strip out the letter prefix 'F' to parse numeric sequence
                    $numericPart = substr($lastFarmerId, 1);

                    // Increment sequence integer
                    $nextNumber = (int) $numericPart + 1;

                    // Pad with leading zeros and re-attach 'F' prefix (e.g., "F0004")
                    $farmer->farmer_id = 'F' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                } else {
                    // Seed initial record ID if database table is empty
                    $farmer->farmer_id = 'F0001';
                }
            }
        });
    }

    /* ---------------------------------------------------------------------- */
    /*  RELATIONSHIPS                                                         */
    /* ---------------------------------------------------------------------- */

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

    /**
     * Relationship: A farmer can have many payout history records.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(FarmerPayout::class, 'farmer_id', 'farmer_id');
    }
    /* ---------------------------------------------------------------------- */
    /*  FINANCIAL ACCESSORS & CALCULATIONS                                    */
    /* ---------------------------------------------------------------------- */

    /**
     * Calculates the total gross revenue earned purely from milk collections
     * for the current calendar month. Executes aggregation directly in SQL 
     * matching the effective price per liter assigned to the farmer's route.
     */
    public function getAccountBalanceAttribute(): float
    {
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

        return (float) ($currentMonthEarnings ?? 0.0);
    }

    /**
     * Sums all debt transactions issued to the farmer within the current calendar month.
     */
    public function getCurrentMonthDebtAttribute(): float
    {
        return (float) $this->debtTransactions()
            ->where('transaction_type', 'debt')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount');
    }

    /**
     * Calculates the final net payout balance due to the farmer
     * by subtracting current month debt from total gross milk earnings.
     */
    public function getNetPayableAttribute(): float
    {
        return (float) ($this->account_balance - $this->current_month_debt);
    }

    /* ---------------------------------------------------------------------- */
    /*  HISTORY & REPORTING ACCESSORS (LAST 30 DAYS)                          */
    /* ---------------------------------------------------------------------- */

    /**
     * Plain text representation of debt history over the last 30 days.
     */
    public function getLast30DaysDebtHistoryAttribute(): string
    {
        $history = $this->debtTransactions()
            ->whereDate('transaction_date', '>=', now()->subDays(30)->toDateString())
            ->orderByDesc('transaction_date')
            ->get();

        if ($history->isEmpty()) {
            return 'No debt activity in the last 30 days.';
        }

        return $history
            ->map(fn($transaction) => sprintf(
                '%s — KES %s%s',
                $transaction->transaction_date ? $transaction->transaction_date->format('d M Y') : '--',
                number_format((float) $transaction->amount, 2),
                $transaction->reason ? ' (' . $transaction->reason . ')' : ''
            ))
            ->implode(PHP_EOL);
    }

    /**
     * HTML table representation of debt history over the last 30 days for Filament Infolists.
     */
    public function getLast30DaysDebtHistoryTableAttribute(): string
    {
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

    /**
     * Plain text representation of milk collections over the last 30 days.
     */
    public function getLast30DaysMilkCollectionHistoryAttribute(): string
    {
        $history = $this->milkCollections()
            ->whereDate('collection_date', '>=', now()->subDays(30)->toDateString())
            ->orderByDesc('collection_date')
            ->get();

        if ($history->isEmpty()) {
            return 'No milk collections in the last 30 days.';
        }

        return $history
            ->map(fn($collection) => sprintf(
                '%s — %s L',
                $collection->collection_date ? $collection->collection_date->format('d M Y') : '--',
                number_format((float) $collection->qty, 2)
            ))
            ->implode(PHP_EOL);
    }

    /**
     * HTML table representation of milk collections over the last 30 days for Filament Infolists.
     */
    public function getLast30DaysMilkCollectionHistoryTableAttribute(): string
    {
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
