<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A milk farmer who delivers to a collection route and is paid monthly.
 *
 * MONEY MODEL (read this first):
 *   net_payable    = milk earnings this month - debts this month
 *   amount_paid    = payouts this month with status 'success'  (money has left)
 *   amount_pending = payouts this month with status 'pending'  (in flight, reserved)
 *   balance_due    = net_payable - amount_paid - amount_pending
 *
 * Payouts with status 'failed' are deliberately NOT subtracted. A failed
 * payout means no money moved, so the farmer is still owed and the amount
 * reappears in balance_due, ready to be retried.
 */
class Farmer extends Model
{
    use HasFactory;

    /**
     * Columns that may be mass-assigned (Farmer::create([...])).
     */
    protected $fillable = [
        'farmer_id', // Alphanumeric custom ID (e.g., F0001)
        'name',
        'national_id',
        'phone',     // Used for M-Pesa payouts; cleaned to 2547XXXXXXXX by the payout controller
        'route_id',  // Decides which milk price applies to this farmer
    ];

    /**
     * Model lifecycle hook. Runs once when the model class boots.
     * Used here to auto-generate sequential IDs like F0001, F0002...
     */
    protected static function booted(): void
    {
        // Fires just before a new farmer row is inserted.
        static::creating(function ($farmer) {

            // Only generate an ID if the caller did not supply one.
            if (empty($farmer->farmer_id)) {

                // Highest existing ID as a string (e.g. "F0003").
                // Works because the IDs are zero-padded to a fixed width.
                $lastFarmerId = self::max('farmer_id');

                if ($lastFarmerId) {
                    // Drop the 'F' prefix to get the numeric part ("0003").
                    $numericPart = substr($lastFarmerId, 1);

                    // Increment the sequence.
                    $nextNumber = (int) $numericPart + 1;

                    // Re-pad to 4 digits and re-attach the prefix (e.g. "F0004").
                    $farmer->farmer_id = 'F' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                } else {
                    // Empty table: start the sequence.
                    $farmer->farmer_id = 'F0001';
                }
            }
        });
    }

    /* ---------------------------------------------------------------------- */
    /*  RELATIONSHIPS                                                         */
    /* ---------------------------------------------------------------------- */

    /**
     * The route (collection area) this farmer belongs to.
     * Each route has its own milk price per liter.
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * All milk deliveries recorded for this farmer.
     * Linked on the custom farmer_id (F0001), not the numeric primary key.
     */
    public function milkCollections(): HasMany
    {
        return $this->hasMany(MilkCollection::class, 'farmer_id', 'farmer_id');
    }

    /**
     * All debt/credit transactions for this farmer (loans, feed, etc.).
     */
    public function debtTransactions(): HasMany
    {
        return $this->hasMany(Farmer_debt_transaction::class, 'farmer_id', 'farmer_id');
    }

    /**
     * Every M-Pesa payout attempt for this farmer (pending, success, failed).
     * This table is the source of truth for what has actually been paid.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(FarmerPayout::class, 'farmer_id', 'farmer_id');
    }

    /* ---------------------------------------------------------------------- */
    /*  FINANCIAL ACCESSORS & CALCULATIONS                                    */
    /*  Used like properties: $farmer->net_payable, $farmer->balance_due      */
    /* ---------------------------------------------------------------------- */

    /**
     * Gross milk earnings for the current calendar month.
     *
     * Done in a single SQL query: for each collection, quantity is multiplied
     * by the price that was active for the farmer's route on the collection
     * date (latest active price whose effective_date is on or before it).
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

        // No collections this month gives null, so default to 0.
        return (float) ($currentMonthEarnings ?? 0.0);
    }

    /**
     * Total debt issued to the farmer in the current calendar month.
     * Only rows of type 'debt' count (repayments are not included).
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
     * Net earnings for the month: milk earnings minus debts.
     * This is what the farmer EARNED, before any payouts are considered.
     * Use balance_due to find out what is still safe to send.
     */
    public function getNetPayableAttribute(): float
    {
        return (float) ($this->account_balance - $this->current_month_debt);
    }

    /**
     * Helper: total of this month's payouts that have any of the given statuses.
     * Private because only the accessors below should use it.
     *
     * @param  array<string>  $statuses  e.g. ['success'] or ['pending']
     */
    private function payoutTotalThisMonth(array $statuses): float
    {
        return (float) $this->payouts()
            ->whereIn('status', $statuses)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
    }

    /**
     * Money M-Pesa has CONFIRMED as sent (callback reported success).
     */
    public function getAmountPaidAttribute(): float
    {
        return $this->payoutTotalThisMonth(['success']);
    }

    /**
     * Money IN FLIGHT: sent to Safaricom but no result received yet.
     * Treated as reserved so it can never be sent a second time.
     */
    public function getAmountPendingAttribute(): float
    {
        return $this->payoutTotalThisMonth(['pending']);
    }

    /**
     * What is still owed AND safe to send right now.
     *
     * Failed payouts are intentionally not subtracted, so a failed farmer's
     * balance reappears and is picked up by the next payout run.
     * Never goes below zero, and is rounded to cents to avoid float noise.
     */
    public function getBalanceDueAttribute(): float
    {
        return max(0, round($this->net_payable - $this->amount_paid - $this->amount_pending, 2));
    }

    /**
     * Status of the most recent payout attempt ('success', 'pending',
     * 'failed'), or null if the farmer has never been paid.
     * Intended for coloured badges on the admin page.
     */
    public function getLatestPayoutStatusAttribute(): ?string
    {
        return $this->payouts()->latest('id')->value('status');
    }

    /* ---------------------------------------------------------------------- */
    /*  HISTORY & REPORTING ACCESSORS (LAST 30 DAYS)                          */
    /* ---------------------------------------------------------------------- */

    /**
     * Plain-text list of the farmer's debt activity over the last 30 days,
     * newest first, one line per transaction.
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

        // Format each row as: "12 Nov 2026 — KES 1,500.00 (reason)".
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
     * Same debt history as an HTML table, for Filament infolists.
     * The reason text is escaped with e() because users type it, and
     * unescaped HTML in a page is a script-injection risk.
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

        // Build one <tr> per transaction.
        $rows = $history->map(function ($transaction) {
            $date = $transaction->transaction_date ? $transaction->transaction_date->format('d M Y') : '--';
            $amount = 'KES ' . number_format((float) $transaction->amount, 2);
            $reason = e($transaction->reason ?: '—');

            return "<tr><td style='padding:6px 8px;border-bottom:1px solid #e5e7eb;'>$date</td><td style='padding:6px 8px;border-bottom:1px solid #e5e7eb;'>$amount</td><td style='padding:6px 8px;border-bottom:1px solid #e5e7eb;'>$reason</td></tr>";
        })->implode('');

        // Wrap the rows in a table with a header.
        return '<table style="width:100%; border-collapse:collapse; font-size:0.875rem;">'
            . '<thead><tr><th style="text-align:left; padding:6px 8px; border-bottom:1px solid #d1d5db; font-weight:600;">Date</th>'
            . '<th style="text-align:left; padding:6px 8px; border-bottom:1px solid #d1d5db; font-weight:600;">Amount</th>'
            . '<th style="text-align:left; padding:6px 8px; border-bottom:1px solid #d1d5db; font-weight:600;">Reason</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table>';
    }

    /**
     * Plain-text list of milk deliveries over the last 30 days,
     * newest first, one line per delivery.
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

        // Format each row as: "12 Nov 2026 — 14.50 L".
        return $history
            ->map(fn($collection) => sprintf(
                '%s — %s L',
                $collection->collection_date ? $collection->collection_date->format('d M Y') : '--',
                number_format((float) $collection->qty, 2)
            ))
            ->implode(PHP_EOL);
    }

    /**
     * Same milk collection history as an HTML table, for Filament infolists.
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

        // Build one <tr> per delivery.
        $rows = $history->map(function ($collection) {
            $date = $collection->collection_date ? $collection->collection_date->format('d M Y') : '--';
            $qty = number_format((float) $collection->qty, 2) . ' L';

            return "<tr><td style='padding:6px 8px;border-bottom:1px solid #e5e7eb;'>$date</td><td style='padding:6px 8px;border-bottom:1px solid #e5e7eb;'>$qty</td></tr>";
        })->implode('');

        // Wrap the rows in a table with a header.
        return '<table style="width:100%; border-collapse:collapse; font-size:0.875rem;">'
            . '<thead><tr><th style="text-align:left; padding:6px 8px; border-bottom:1px solid #d1d5db; font-weight:600;">Date</th>'
            . '<th style="text-align:left; padding:6px 8px; border-bottom:1px solid #d1d5db; font-weight:600;">Qty</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table>';
    }

    // use SoftDeletes;
}