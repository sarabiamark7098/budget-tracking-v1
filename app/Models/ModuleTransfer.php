<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleTransfer extends Model
{
    protected $fillable = [
        'budget_tracking_id',
        'user_id',
        'module',
        'transfer_from',
        'amount',
        'transfer_fee',
        'total',
        'note',
        'transfer_date',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount'        => 'decimal:2',
        'transfer_fee'  => 'decimal:2',
        'total'         => 'decimal:2',
    ];

    public function budgetTracking(): BelongsTo
    {
        return $this->belongsTo(BudgetTracking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
