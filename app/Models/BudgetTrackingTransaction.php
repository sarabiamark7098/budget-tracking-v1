<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetTrackingTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'budget_tracking_id',
        'user_id',
        'budget_tracking_allocation_id',
        'category_id',
        'type',
        'title',
        'amount',
        'description',
        'date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date'   => 'date',
    ];

    // ─── Relationships ──────────────────────────────────────────────────────────

    public function budgetTracking(): BelongsTo
    {
        return $this->belongsTo(BudgetTracking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(BudgetTrackingAllocation::class, 'budget_tracking_allocation_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
