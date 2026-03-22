<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoDividend extends Model
{
    protected $fillable = [
        'crypto_asset_id',
        'budget_tracking_id',
        'quantity_rewarded',
        'price_at_reward',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'quantity_rewarded' => 'decimal:8',
        'price_at_reward'   => 'decimal:8',
        'paid_at'           => 'date',
    ];

    public function cryptoAsset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class);
    }
}
