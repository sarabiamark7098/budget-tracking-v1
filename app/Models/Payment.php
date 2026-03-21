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
        'interest_paid',
        'principal_paid',
        'days_elapsed',
        'installment_number',
        'payment_date',
        'note',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'interest_paid' => 'decimal:2',
        'principal_paid' => 'decimal:2',
        'payment_date'  => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Payment $payment) {
            $debt = $payment->debt;
            if ($debt) {
                // For business debts: only the principal_paid portion reduces the balance.
                // For personal debts: the full amount is principal (no interest split).
                $reduction = ($payment->principal_paid !== null)
                    ? (float) $payment->principal_paid
                    : (float) $payment->amount;

                $newBalance = max(0, (float) $debt->remaining_balance - $reduction);
                // Treat anything below ₱1.00 as fully settled (ignore remaining centavos)
                if ($newBalance < 1) {
                    $newBalance = 0;
                }
                $status = $newBalance == 0 ? 'paid' : $debt->status;
                $debt->update([
                    'remaining_balance' => $newBalance,
                    'status'            => $status,
                ]);
            }
        });

        static::deleted(function (Payment $payment) {
            $debt = $payment->debt;
            if ($debt) {
                $reduction  = ($payment->principal_paid !== null)
                    ? (float) $payment->principal_paid
                    : (float) $payment->amount;
                $newBalance = (float) $debt->remaining_balance + $reduction;
                $status     = $newBalance > 0 ? 'active' : 'paid';
                $debt->update([
                    'remaining_balance' => $newBalance,
                    'status'            => $status,
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
