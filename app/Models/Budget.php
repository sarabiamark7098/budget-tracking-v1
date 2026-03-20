<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'amount',
        'period',
        'start_date',
        'end_date',
        'alert_threshold',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'alert_threshold' => 'integer',
    ];

    protected $appends = ['spent_amount', 'remaining_amount', 'usage_percentage'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getSpentAmountAttribute(): float
    {
        // Primary: expenses explicitly linked to this budget via budget_id
        $explicit = Expense::where('budget_id', $this->id)->sum('amount');

        // Fallback: expenses matched by category + date range that have no budget assigned yet
        // (covers records created before the budget_id column existed)
        $fallbackQuery = Expense::where('user_id', $this->user_id)
            ->whereNull('budget_id')
            ->whereBetween('spent_at', [$this->start_date, $this->end_date]);

        if ($this->category_id) {
            $fallbackQuery->where('category_id', $this->category_id);
        }

        return (float) $explicit + (float) $fallbackQuery->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->amount - $this->spent_amount;
    }

    public function getUsagePercentageAttribute(): float
    {
        if ((float) $this->amount == 0) {
            return 0;
        }
        return round(($this->spent_amount / (float) $this->amount) * 100, 2);
    }
}
