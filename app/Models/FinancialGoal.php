<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialGoal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'financial_plan_id',
        'name',
        'description',
        'target_amount',
        'current_amount',
        'deadline',
        'priority',
        'status',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'deadline' => 'date',
    ];

    protected $appends = ['progress_percentage'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function financialPlan(): BelongsTo
    {
        return $this->belongsTo(FinancialPlan::class);
    }

    public function getProgressPercentageAttribute(): float
    {
        $target = (float) $this->target_amount;
        if ($target == 0) {
            return 0;
        }
        return round(((float) $this->current_amount / $target) * 100, 2);
    }
}
