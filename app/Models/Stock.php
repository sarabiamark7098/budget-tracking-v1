<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stock extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'budget_tracking_id',
        'symbol',
        'company_name',
        'shares',
        'buy_price',
        'current_price',
        'purchase_date',
        'notes',
    ];

    protected $casts = [
        'shares' => 'decimal:4',
        'buy_price' => 'decimal:4',
        'current_price' => 'decimal:4',
        'purchase_date' => 'date',
    ];

    protected $appends = ['current_value', 'profit_loss', 'profit_loss_percentage'];

    public function budgetTracking(): BelongsTo
    {
        return $this->belongsTo(BudgetTracking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCurrentValueAttribute(): float
    {
        return (float) $this->shares * (float) $this->current_price;
    }

    public function getProfitLossAttribute(): float
    {
        return $this->current_value - ((float) $this->shares * (float) $this->buy_price);
    }

    public function getProfitLossPercentageAttribute(): float
    {
        $costBasis = (float) $this->shares * (float) $this->buy_price;
        if ($costBasis == 0) {
            return 0;
        }
        return round(($this->profit_loss / $costBasis) * 100, 2);
    }
}
