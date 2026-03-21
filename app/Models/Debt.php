<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'budget_tracking_id',
        'type',
        'personal_mode',
        'lender_name',
        'borrower_name',
        'business_name',
        'amount',
        'remaining_balance',
        'interest_rate',
        'months_to_pay',
        'monthly_payment',
        'status',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'interest_rate'    => 'decimal:3',
        'monthly_payment'  => 'decimal:2',
    ];

    public function budgetTracking(): BelongsTo
    {
        return $this->belongsTo(BudgetTracking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('installment_number')->orderBy('created_at');
    }
}
