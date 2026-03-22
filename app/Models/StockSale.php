<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockSale extends Model
{
    protected $fillable = [
        'stock_id',
        'budget_tracking_id',
        'shares_sold',
        'sell_price',
        'proceeds',
        'sold_at',
    ];

    protected $casts = [
        'shares_sold' => 'decimal:4',
        'sell_price'  => 'decimal:4',
        'proceeds'    => 'decimal:2',
        'sold_at'     => 'date',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
