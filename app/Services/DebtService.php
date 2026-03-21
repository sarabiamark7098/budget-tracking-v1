<?php

namespace App\Services;

use App\Models\BudgetTracking;
use App\Models\Debt;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class DebtService
{
    public function getAll(BudgetTracking $budget, array $filters = []): LengthAwarePaginator
    {
        $query = Debt::with('payments')
            ->where('budget_tracking_id', $budget->id);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = $filters['per_page'] ?? 50;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(BudgetTracking $budget, User $user, array $data): Debt
    {
        $data['remaining_balance'] = $data['amount'];
        $data['status']            = 'active';

        $debt = Debt::create(array_merge($data, [
            'budget_tracking_id' => $budget->id,
            'user_id'            => $user->id,
        ]));

        return $debt->load('payments');
    }

    public function update(Debt $debt, array $data): Debt
    {
        $debt->update($data);
        return $debt->fresh(['payments']);
    }

    public function delete(Debt $debt): bool
    {
        return $debt->delete();
    }

    /**
     * Record a payment for a personal "Shop Pay Later" debt.
     * Pays off the full remaining balance in one shot.
     */
    public function payShopPayLater(Debt $debt, User $user): Payment
    {
        $amount = (float) $debt->remaining_balance;

        return Payment::create([
            'debt_id'            => $debt->id,
            'budget_tracking_id' => $debt->budget_tracking_id,
            'user_id'            => $user->id,
            'amount'             => $amount,
            'payment_date'       => now()->toDateString(),
        ]);
    }

    /**
     * Record the next monthly installment for a personal "Pay Installment" debt.
     */
    public function payInstallment(Debt $debt, User $user): Payment
    {
        $installmentNumber = $debt->payments()->count() + 1;
        $amount            = (float) $debt->monthly_payment;

        // Do not overpay beyond remaining balance
        $amount = min($amount, (float) $debt->remaining_balance);

        return Payment::create([
            'debt_id'            => $debt->id,
            'budget_tracking_id' => $debt->budget_tracking_id,
            'user_id'            => $user->id,
            'amount'             => $amount,
            'payment_date'       => now()->toDateString(),
            'installment_number' => $installmentNumber,
        ]);
    }

    /**
     * Record a payment for a business debt.
     * Interest is computed with simple annual interest from creation date.
     * Payment is applied to interest first, then principal.
     *
     * Returns [payment, breakdown] so the caller can generate an invoice.
     */
    public function payBusiness(Debt $debt, User $user, float $amountToPay): array
    {
        $principal    = (float) $debt->remaining_balance;
        $annualRate   = (float) $debt->interest_rate;
        $lastPayment  = $debt->payments()->latest('payment_date')->first();
        $startDate    = $lastPayment ? $lastPayment->payment_date : $debt->created_at;
        // Signed: negative because start date is in the past relative to now
        // days_accumulated = days_elapsed / -1  (flips negative → positive)
        $daysElapsed = (int) now()->diffInDays($startDate, false);

        // Formula: daily_interest   = (principal × rate/100) / 30
        //          accrued_interest = daily_interest × (days_elapsed / -1)
        //          balance_due      = principal + accrued_interest
        $dailyInterest   = ($principal * ($annualRate / 100)) / 30;
        $accruedInterest = $dailyInterest * ($daysElapsed / -1);
        $interestDue     = max(0, $accruedInterest);

        // Split payment: interest first, then principal
        if ($amountToPay >= $interestDue) {
            $interestPaid  = $interestDue;
            $principalPaid = $amountToPay - $interestDue;
        } else {
            $interestPaid  = $amountToPay;
            $principalPaid = 0;
        }

        $principalPaid = min($principalPaid, $principal); // cap at remaining principal

        $payment = Payment::create([
            'debt_id'            => $debt->id,
            'budget_tracking_id' => $debt->budget_tracking_id,
            'user_id'            => $user->id,
            'amount'             => round($amountToPay, 2),
            'interest_paid'      => round($interestPaid, 2),
            'principal_paid'     => round($principalPaid, 2),
            'days_elapsed'       => (int) ($daysElapsed / -1), // days accumulated
            'payment_date'       => now()->toDateString(),
        ]);

        $balanceDue = round($principal + $accruedInterest, 2);
        $breakdown  = [
            'amount_borrowed'     => round($principal, 2),
            'annual_rate'         => $annualRate,
            'days_elapsed'        => (int) ($daysElapsed / -1),
            'accrued_interest'    => round($accruedInterest, 2),
            'balance_due'         => $balanceDue,
            'rounded_balance_due' => (int) round($balanceDue),
            'amount_paid'         => round($amountToPay, 2),
            'interest_paid'       => round($interestPaid, 2),
            'principal_paid'      => round($principalPaid, 2),
        ];

        return [$payment, $breakdown];
    }

    /**
     * Calculate the current balance due for a business debt (readonly preview).
     */
    public function businessBalanceDue(Debt $debt): array
    {
        $principal   = (float) $debt->remaining_balance;
        $annualRate  = (float) $debt->interest_rate;
        $lastPayment     = $debt->payments()->latest('payment_date')->first();
        $startDate       = $lastPayment ? $lastPayment->payment_date : $debt->created_at;
        $daysElapsed = (int) now()->diffInDays($startDate, false); // negative (past date)

        $dailyInterest   = ($principal * ($annualRate / 100)) / 30;
        $accruedInterest = $dailyInterest * ($daysElapsed / -1);

        return [
            'amount_borrowed'  => round($principal, 2),
            'annual_rate'      => $annualRate,
            'days_elapsed'     => (int) ($daysElapsed / -1), // displayed as "Days Accumulated"
            'daily_interest'   => round($dailyInterest, 4),
            'accrued_interest'       => round($accruedInterest, 2),
            'balance_due'            => round($principal + $accruedInterest, 2),
            'rounded_balance_due'    => (int) round($principal + $accruedInterest),
        ];
    }
}
