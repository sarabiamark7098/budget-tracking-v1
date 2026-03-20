<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class DebtService
{
    public function getAll(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Debt::with('payments')
            ->where('user_id', $user->id);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(User $user, array $data): Debt
    {
        $data['remaining_balance'] = $data['remaining_balance'] ?? $data['amount'];
        return Debt::create(array_merge($data, ['user_id' => $user->id]));
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

    public function calculateRemainingBalance(Debt $debt): float
    {
        $totalPaid = $debt->payments()->sum('amount');
        return max(0, (float) $debt->amount - (float) $totalPaid);
    }

    /**
     * Generate a monthly amortization schedule for a debt with interest.
     *
     * Formula (compound monthly interest):
     *   Balance_n = Balance_(n-1) × (1 + monthly_rate) − payment
     *
     * If monthly_payment is not given, it is derived so the debt is paid off
     * within duration_months using the standard annuity formula:
     *   PMT = P × r / (1 − (1 + r)^−n)
     *
     * @param  float  $principal       Original loan amount
     * @param  float  $annualRatePct   Annual interest rate as percentage (e.g. 10 = 10%)
     * @param  int    $durationMonths  Number of monthly payments
     * @param  float|null $fixedPayment  Override computed payment amount
     * @return array  ['monthly_payment', 'total_paid', 'total_interest', 'schedule']
     */
    public function amortizationSchedule(
        float $principal,
        float $annualRatePct,
        int   $durationMonths,
        ?float $fixedPayment = null
    ): array {
        $monthlyRate = $annualRatePct / 100 / 12;

        // Standard annuity PMT formula; handle 0% interest edge case
        if ($monthlyRate > 0 && $fixedPayment === null) {
            $pmt = $principal * $monthlyRate / (1 - pow(1 + $monthlyRate, -$durationMonths));
        } else {
            $pmt = $fixedPayment ?? ($principal / $durationMonths);
        }

        $balance   = $principal;
        $schedule  = [];
        $totalPaid = 0;

        for ($month = 1; $month <= $durationMonths; $month++) {
            $interest   = $balance * $monthlyRate;
            $principal_portion = $pmt - $interest;

            // Last payment adjustment to clear rounding residual
            if ($month === $durationMonths) {
                $principal_portion = $balance;
                $pmt               = $principal_portion + $interest;
            }

            $balance   -= $principal_portion;
            $totalPaid += $pmt;

            $schedule[] = [
                'month'             => $month,
                'payment'           => round($pmt, 2),
                'principal_portion' => round($principal_portion, 2),
                'interest_portion'  => round($interest, 2),
                'remaining_balance' => round(max(0, $balance), 2),
            ];

            if ($balance <= 0.01) {
                break;
            }
        }

        return [
            'monthly_payment'  => round($fixedPayment ?? $schedule[0]['payment'], 2),
            'total_paid'       => round($totalPaid, 2),
            'total_interest'   => round($totalPaid - $principal, 2),
            'interest_to_principal_ratio' => $principal > 0
                ? round((($totalPaid - $principal) / $principal) * 100, 2)
                : 0,
            'schedule'         => $schedule,
        ];
    }

    /**
     * Calculate accrued interest for a business debt using daily compounding.
     *
     * Formula (simple daily interest, common for informal business loans):
     *   Daily interest = principal × (monthly_rate / 30)
     *   Accrued        = daily_interest × days_elapsed
     *   Current balance = principal + accrued − total_paid
     *
     * @param  float  $principal       Original loan amount
     * @param  float  $monthlyRatePct  Monthly interest rate as percentage (e.g. 10 = 10%/month)
     * @param  int    $daysElapsed     Days since loan start
     * @param  float  $totalPaid       Sum of all payments made
     * @return array  ['daily_interest', 'accrued_interest', 'current_balance']
     */
    public function dailyInterestAccrual(
        float $principal,
        float $monthlyRatePct,
        int   $daysElapsed,
        float $totalPaid = 0
    ): array {
        $dailyRate      = $monthlyRatePct / 100 / 30;
        $dailyInterest  = $principal * $dailyRate;
        $accruedInterest = $dailyInterest * $daysElapsed;
        $currentBalance  = max(0, $principal + $accruedInterest - $totalPaid);

        return [
            'principal'         => round($principal, 2),
            'monthly_rate_pct'  => $monthlyRatePct,
            'daily_interest'    => round($dailyInterest, 2),
            'days_elapsed'      => $daysElapsed,
            'accrued_interest'  => round($accruedInterest, 2),
            'total_paid'        => round($totalPaid, 2),
            'current_balance'   => round($currentBalance, 2),
            'total_cost'        => round($principal + $accruedInterest, 2),
        ];
    }
}
