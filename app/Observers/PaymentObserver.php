<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    public bool $afterCommit = true;

    public function created(Payment $payment): void
    {
        $debt = $payment->debt;
        if (!$debt) {
            return;
        }

        $reduction = ($payment->principal_paid !== null)
            ? (float) $payment->principal_paid
            : (float) $payment->amount;

        $newBalance = max(0, (float) $debt->remaining_balance - $reduction);
        if ($newBalance < 1) {
            $newBalance = 0;
        }
        $status = $newBalance == 0 ? 'paid' : $debt->status;
        $debt->update([
            'remaining_balance' => $newBalance,
            'status'            => $status,
        ]);
    }

    public function deleted(Payment $payment): void
    {
        $debt = $payment->debt;
        if (!$debt) {
            return;
        }

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
}
