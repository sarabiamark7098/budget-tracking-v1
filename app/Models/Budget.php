<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'budget_tracking_id',
        'category_id',
        'name',
        'amount',
        'period',
        'start_date',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'start_date' => 'date',
    ];

    protected $appends = ['total_budget', 'spent_amount', 'remaining_amount', 'usage_percentage'];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function budgetTracking(): BelongsTo
    {
        return $this->belongsTo(BudgetTracking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // ── Computed attributes ────────────────────────────────────────────────────

    /**
     * Cumulative budget: amount × number of complete/started periods since start_date.
     * For monthly: each calendar month adds one cycle.
     * For weekly: each calendar week adds one cycle.
     * For yearly: each calendar year adds one cycle.
     */
    public function getTotalBudgetAttribute(): float
    {
        if (! $this->start_date || (float) $this->amount === 0.0) {
            return (float) $this->amount;
        }

        $start = $this->start_date instanceof Carbon
            ? $this->start_date
            : Carbon::parse($this->start_date);

        $now = Carbon::now();

        // Budget hasn't started yet
        if ($start->gt($now)) {
            return (float) $this->amount;
        }

        $periods = match ($this->period) {
            'weekly'  => (int) $start->diffInWeeks($now) + 1,
            'monthly' => (int) $start->diffInMonths($now) + 1,
            'yearly'  => (int) $start->diffInYears($now) + 1,
            default   => 1,
        };

        return (float) $this->amount * max(1, $periods);
    }

    /**
     * Total spent: expenses explicitly linked to this budget
     * + expenses matched by category (from start_date onwards) with no budget assigned.
     */
    public function getSpentAmountAttribute(): float
    {
        // Expenses directly tagged to this budget
        $explicit = Expense::where('budget_id', $this->id)->sum('amount');

        // Fallback: untagged expenses in the same tracker / category from start_date
        $fallback = Expense::where('budget_tracking_id', $this->budget_tracking_id)
            ->whereNull('budget_id')
            ->where('spent_at', '>=', $this->start_date);

        if ($this->category_id) {
            $fallback->where('category_id', $this->category_id);
        }

        return (float) $explicit + (float) $fallback->sum('amount');
    }

    /**
     * Remaining = cumulative total budget − spent. Can be negative when over budget.
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->total_budget - $this->spent_amount;
    }

    public function getUsagePercentageAttribute(): float
    {
        $total = $this->total_budget;

        if ($total == 0) {
            return 0;
        }

        return round(($this->spent_amount / $total) * 100, 2);
    }
}
