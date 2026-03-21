<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'budget_tracking_id',
        'item_name',
        'total_cost',
        'payment_method',      // cash | credit_card | other
        'is_installment',
        'installment_count',   // months to pay (credit card)
        'installment_amount',  // monthly cost (auto = total_cost / installment_count)
        'installments_paid',   // months already paid
        'purchase_date',
    ];

    protected $casts = [
        'total_cost'        => 'decimal:2',
        'installment_amount'=> 'decimal:2',
        'is_installment'    => 'boolean',
        'purchase_date'     => 'date',
    ];

    protected $appends = ['remaining_balance', 'amount_paid', 'remaining_installments'];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function budgetTracking(): BelongsTo
    {
        return $this->belongsTo(BudgetTracking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class)->orderBy('installment_number');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    // ── Computed attributes ────────────────────────────────────────────────────

    /** Amount paid so far (installments_paid × monthly cost, or full total for non-CC). */
    public function getAmountPaidAttribute(): float
    {
        if ($this->payment_method === 'credit_card' && $this->installment_amount) {
            return (float) $this->installment_amount * (int) $this->installments_paid;
        }
        // Cash / other: fully paid on purchase day
        return (float) $this->total_cost;
    }

    /** Remaining balance: what is still owed. Always 0 for cash/other. */
    public function getRemainingBalanceAttribute(): float
    {
        if ($this->payment_method !== 'credit_card') {
            return 0.0;
        }

        $paid = (float) $this->installment_amount * (int) $this->installments_paid;
        return max(0, (float) $this->total_cost - $paid);
    }

    /** How many installment months are left. */
    public function getRemainingInstallmentsAttribute(): int
    {
        if ($this->payment_method !== 'credit_card' || ! $this->installment_count) {
            return 0;
        }
        return max(0, (int) $this->installment_count - (int) $this->installments_paid);
    }
}
