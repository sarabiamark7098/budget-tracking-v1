<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLot extends Model
{
    protected $fillable = ['stock_id', 'shares', 'buy_price', 'purchase_date'];

    protected $casts = [
        'shares'        => 'decimal:4',
        'buy_price'     => 'decimal:4',
        'purchase_date' => 'date',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
