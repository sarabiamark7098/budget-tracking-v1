<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'budget_tracking_id',
        'category_id',
        'name',
        'type',
        'amount_invested',
        'current_value',
        'purchase_date',
        'description',
        // new payment-related fields
        'total_value',
        'period',
        'months_of_payment',
        'amount_per_payment',
        'date_started',
        'other_investment_title',
        'payment_status',
    ];

    protected $casts = [
        'amount_invested'    => 'decimal:2',
        'current_value'      => 'decimal:2',
        'total_value'        => 'decimal:2',
        'amount_per_payment' => 'decimal:2',
        'purchase_date'      => 'date',
        'date_started'       => 'date',
    ];

    protected $appends = ['roi', 'roi_amount', 'total_paid'];

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

    public function payments(): HasMany
    {
        return $this->hasMany(InvestmentPayment::class);
    }

    public function getROIAttribute(): float
    {
        $invested = (float) $this->amount_invested;
        if ($invested == 0) {
            return 0;
        }
        return round((((float) $this->current_value - $invested) / $invested) * 100, 2);
    }

    public function getROIAmountAttribute(): float
    {
        return (float) $this->current_value - (float) $this->amount_invested;
    }

    public function getTotalPaidAttribute(): float
    {
        // Use eager-loaded withSum value when available (avoids N+1)
        if (isset($this->payments_sum_amount)) {
            return (float) $this->payments_sum_amount;
        }
        return (float) $this->payments()->sum('amount');
    }
}
