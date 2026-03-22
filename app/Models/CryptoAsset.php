<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CryptoAsset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'budget_tracking_id',
        'coin_name',
        'symbol',
        'latest_price',
    ];

    protected $casts = [
        'latest_price' => 'decimal:8',
    ];

    public function budgetTracking(): BelongsTo
    {
        return $this->belongsTo(BudgetTracking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(CryptoLot::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(CryptoSale::class);
    }

    public function dividends(): HasMany
    {
        return $this->hasMany(CryptoDividend::class);
    }
}
