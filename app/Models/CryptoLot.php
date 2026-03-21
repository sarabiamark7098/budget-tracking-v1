<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoLot extends Model
{
    protected $fillable = ['crypto_asset_id', 'quantity', 'buy_price', 'purchase_date'];

    protected $casts = [
        'quantity'      => 'decimal:8',
        'buy_price'     => 'decimal:8',
        'purchase_date' => 'date',
    ];

    public function cryptoAsset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class);
    }
}
