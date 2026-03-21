<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentPayment extends Model
{
    protected $fillable = [
        'investment_id',
        'amount',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }
}
