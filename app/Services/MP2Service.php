<?php

namespace App\Services;

use App\Models\BudgetTracking;
use App\Models\MP2Plan;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class MP2Service
{
    /**
     * Default PAG-IBIG MP2 annual dividend rate.
     * Actual rate is declared by PAG-IBIG each year (e.g. 7.03% for 2023, 7.1% for 2024).
     */
    const ANNUAL_DIVIDEND_RATE = 0.0703;

    public function getAll(BudgetTracking $budget, array $filters = []): LengthAwarePaginator
    {
        $query = MP2Plan::where('budget_tracking_id', $budget->id);
        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(BudgetTracking $budget, User $user, array $data): MP2Plan
    {
        $calculation = $this->calculate($data);
        $data['projected_earnings'] = $calculation['projected_earnings'];
        $data['total_contributions'] = $calculation['total_contributions'];

        return MP2Plan::create(array_merge($data, [
            'budget_tracking_id' => $budget->id,
            'user_id'            => $user->id,
        ]));
    }

    public function update(MP2Plan $plan, array $data): MP2Plan
    {
        $mergedData = array_merge($plan->toArray(), $data);
        $calculation = $this->calculate($mergedData);
        $data['projected_earnings'] = $calculation['projected_earnings'];
        $data['total_contributions'] = $calculation['total_contributions'];

        $plan->update($data);
        return $plan->fresh();
    }

    public function delete(MP2Plan $plan): bool
    {
        return $plan->delete();
    }

    /**
     * Calculate MP2 projected value using the PAG-IBIG Average Daily Balance (ADB) method.
     *
     * PAG-IBIG MP2 dividend formula:
     *   - Dividends are credited annually.
     *   - Each monthly deposit earns a prorated dividend based on how many months
     *     it was held within the year: weight = (12 - month_index + 1) / 12
     *   - Opening balance earns a full year dividend.
     *
     *   Annual dividend = (opening_balance × rate)
     *                   + Σ [monthly_deposit × ((13 - deposit_month) / 12) × rate]
     *
     * Optional lump-sum deposits per year can be provided via data['lump_sum_per_year']
     * (associative array: year_number => amount, e.g. [3 => 100000]).
     */
    public function calculate(array $data): array
    {
        $monthlyContribution = (float) ($data['monthly_contribution'] ?? 0);
        $years               = (int) ($data['duration_years'] ?? 1);
        $annualRate          = (float) ($data['dividend_rate'] ?? self::ANNUAL_DIVIDEND_RATE);
        $lumpSums            = $data['lump_sum_per_year'] ?? [];   // e.g. [3 => 100000]

        $totalContributions = 0;
        $openingBalance     = 0;
        $yearlyBreakdown    = [];

        for ($year = 1; $year <= $years; $year++) {
            $lump = (float) ($lumpSums[$year] ?? 0);

            // Dividend on opening balance (held all year)
            $dividendOnOpening = $openingBalance * $annualRate;

            // Dividend on lump-sum (deposited at start of year — full year)
            $dividendOnLump = $lump * $annualRate;

            // Dividend on monthly contributions using ADB weighting
            // Month 1 deposit earns 12/12, Month 2 earns 11/12, … Month 12 earns 1/12
            $dividendOnMonthly = 0;
            for ($m = 1; $m <= 12; $m++) {
                $weight             = (13 - $m) / 12;
                $dividendOnMonthly += $monthlyContribution * $weight * $annualRate;
            }

            $yearlyContribution  = ($monthlyContribution * 12) + $lump;
            $totalDividends      = $dividendOnOpening + $dividendOnLump + $dividendOnMonthly;
            $closingBalance      = $openingBalance + $yearlyContribution + $totalDividends;

            $totalContributions += $yearlyContribution;

            $yearlyBreakdown[] = [
                'year'                    => $year,
                'opening_balance'         => round($openingBalance, 2),
                'lump_sum'                => round($lump, 2),
                'monthly_contribution'    => round($monthlyContribution * 12, 2),
                'yearly_contribution'     => round($yearlyContribution, 2),
                'dividend_on_opening'     => round($dividendOnOpening, 2),
                'dividend_on_lump'        => round($dividendOnLump, 2),
                'dividend_on_monthly_adb' => round($dividendOnMonthly, 2),
                'total_dividends'         => round($totalDividends, 2),
                'closing_balance'         => round($closingBalance, 2),
                'cumulative_contributions'=> round($totalContributions, 2),
                'effective_return_pct'    => $openingBalance + $yearlyContribution > 0
                    ? round(($totalDividends / ($openingBalance + $yearlyContribution)) * 100, 4)
                    : 0,
            ];

            $openingBalance = $closingBalance;
        }

        $totalValue      = $openingBalance;
        $projectedEarnings = $totalValue - $totalContributions;

        return [
            'monthly_contribution'  => $monthlyContribution,
            'duration_years'        => $years,
            'annual_dividend_rate'  => round($annualRate * 100, 4),
            'total_contributions'   => round($totalContributions, 2),
            'projected_earnings'    => round($projectedEarnings, 2),
            'total_value'           => round($totalValue, 2),
            'effective_total_return'=> $totalContributions > 0
                ? round(($projectedEarnings / $totalContributions) * 100, 2)
                : 0,
            'yearly_breakdown'      => $yearlyBreakdown,
        ];
    }
}
