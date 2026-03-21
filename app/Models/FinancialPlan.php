<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'budget_tracking_id',
        'name',
        'description',
        'monthly_income_target',
        'monthly_expense_limit',
        'savings_target',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'monthly_income_target' => 'decimal:2',
        'monthly_expense_limit' => 'decimal:2',
        'savings_target' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function budgetTracking(): BelongsTo
    {
        return $this->belongsTo(BudgetTracking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function financialGoals(): HasMany
    {
        return $this->hasMany(FinancialGoal::class);
    }
}
