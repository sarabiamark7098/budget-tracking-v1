<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayment extends Model
{
    protected $fillable = [
        'purchase_id',
        'budget_tracking_id',
        'user_id',
        'amount',
        'paid_at',
        'installment_number',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'amount'  => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function budgetTracking(): BelongsTo
    {
        return $this->belongsTo(BudgetTracking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
