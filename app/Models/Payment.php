<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'budget_tracking_id',
        'debt_id',
        'amount',
        'payment_date',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Payment $payment) {
            $debt = $payment->debt;
            if ($debt) {
                $newBalance = max(0, (float) $debt->remaining_balance - (float) $payment->amount);
                $status = $newBalance <= 0 ? 'paid' : $debt->status;
                $debt->update([
                    'remaining_balance' => $newBalance,
                    'status' => $status,
                ]);
            }
        });

        static::deleted(function (Payment $payment) {
            $debt = $payment->debt;
            if ($debt) {
                $newBalance = (float) $debt->remaining_balance + (float) $payment->amount;
                $status = $newBalance > 0 ? 'active' : 'paid';
                $debt->update([
                    'remaining_balance' => $newBalance,
                    'status' => $status,
                ]);
            }
        });
    }

    public function budgetTracking(): BelongsTo
    {
        return $this->belongsTo(BudgetTracking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }
}
