<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CryptoAsset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'budget_tracking_id',
        'coin_name',
        'symbol',
        'wallet_address',
        'quantity',
        'buy_price',
        'current_price',
        'purchase_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'buy_price' => 'decimal:8',
        'current_price' => 'decimal:8',
        'purchase_date' => 'date',
    ];

    protected $appends = ['current_value', 'profit_loss'];

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
        return (float) $this->quantity * (float) $this->current_price;
    }

    public function getProfitLossAttribute(): float
    {
        $costBasis = (float) $this->quantity * (float) $this->buy_price;
        return $this->current_value - $costBasis;
    }

    public function getProfitLossPercentageAttribute(): float
    {
        $costBasis = (float) $this->quantity * (float) $this->buy_price;
        if ($costBasis == 0) {
            return 0;
        }
        return round(($this->profit_loss / $costBasis) * 100, 2);
    }
}
