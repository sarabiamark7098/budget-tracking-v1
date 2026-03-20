<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetTrackingAllocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'budget_tracking_id',
        'category_id',
        'name',
        'allocated_amount',
        'color',
        'icon',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    protected $appends = ['spent_amount', 'remaining_amount', 'usage_percentage'];

    // ─── Relationships ──────────────────────────────────────────────────────────

    public function budgetTracking(): BelongsTo
    {
        return $this->belongsTo(BudgetTracking::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BudgetTrackingTransaction::class);
    }

    // ─── Computed Attributes ────────────────────────────────────────────────────

    public function getSpentAmountAttribute(): float
    {
        return (float) $this->transactions()
            ->where('type', 'expense')
            ->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->allocated_amount - $this->spent_amount);
    }

    public function getUsagePercentageAttribute(): float
    {
        if ((float) $this->allocated_amount <= 0) {
            return 0;
        }

        return round(($this->spent_amount / (float) $this->allocated_amount) * 100, 2);
    }
}
