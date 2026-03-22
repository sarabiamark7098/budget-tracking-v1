<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoSale extends Model
{
    protected $fillable = [
        'crypto_asset_id',
        'budget_tracking_id',
        'quantity_sold',
        'sell_price',
        'sell_fee',
        'proceeds',
        'sold_at',
    ];

    protected $casts = [
        'quantity_sold' => 'decimal:8',
        'sell_price'    => 'decimal:8',
        'sell_fee'      => 'decimal:2',
        'proceeds'      => 'decimal:2',
        'sold_at'       => 'date',
    ];

    public function cryptoAsset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class);
    }
}
