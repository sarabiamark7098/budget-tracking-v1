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
    const ANNUAL_DIVIDEND_RATE = 0.071;

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
     *   - Opening balance earns a full-year dividend.
     *   - Monthly deposits earn a prorated dividend based on entitled months:
     *       weight = entitled_months / 12  (January = 12/12, February = 11/12, … December = 1/12)
     *   - Lump-sum deposits made in January earn a full-year dividend (12/12)
     *     and REPLACE January's regular monthly contribution for that year.
     *     Monthly ADB therefore covers only February–December (11 months) when a lump sum exists.
     *
     * Optional lump-sum deposits per year: data['lump_sum_per_year'] = [year_number => amount]
     *
     * Rate input: data['dividend_rate'] as a percentage value (e.g. 7.1 for 7.1%).
     * Omit or pass null to use the current default PAG-IBIG declared rate.
     */
    public function calculate(array $data): array
    {
        $monthlyContribution = (float) ($data['monthly_contribution'] ?? 0);
        $years               = (int) ($data['duration_years'] ?? 1);
        $lumpSums            = $data['lump_sum_per_year'] ?? [];

        // Accept dividend_rate as a percentage (e.g. 7.1 → 0.071)
        $ratePct    = isset($data['dividend_rate']) && $data['dividend_rate'] !== null
            ? (float) $data['dividend_rate']
            : null;
        $annualRate = $ratePct !== null ? $ratePct / 100.0 : self::ANNUAL_DIVIDEND_RATE;

        // Derive optional calendar start year for labelling breakdown rows
        $startYear = isset($data['start_date']) && $data['start_date']
            ? (int) date('Y', strtotime($data['start_date']))
            : null;

        $totalContributions = 0;
        $openingBalance     = 0;
        $yearlyBreakdown    = [];

        for ($year = 1; $year <= $years; $year++) {
            $lump = (float) ($lumpSums[$year] ?? 0);

            // ── Dividend on opening balance (held all year → full 12/12 weight) ──────
            $dividendOnOpening = $openingBalance * $annualRate;

            // ── Dividend on lump-sum (deposited Jan 1 → full year, 12/12 weight) ─────
            $dividendOnLump = $lump * $annualRate;

            // ── Dividend on monthly contributions (ADB weighting) ────────────────────
            // When a lump sum is present it occupies January, so regular monthly
            // deposits start in February (entitled months: 11, 10, …, 1).
            // Without a lump sum all 12 months are counted (12, 11, …, 1).
            $monthStart        = $lump > 0 ? 2 : 1;
            $dividendOnMonthly = 0;
            for ($m = $monthStart; $m <= 12; $m++) {
                $weight             = (13 - $m) / 12;
                $dividendOnMonthly += $monthlyContribution * $weight * $annualRate;
            }

            $monthlyCount        = $lump > 0 ? 11 : 12;
            $yearlyContribution  = ($monthlyContribution * $monthlyCount) + $lump;
            $totalDividends      = $dividendOnOpening + $dividendOnLump + $dividendOnMonthly;
            $closingBalance      = $openingBalance + $yearlyContribution + $totalDividends;

            $totalContributions += $yearlyContribution;

            $yearlyBreakdown[] = [
                'year'                    => $year,
                'calendar_year'           => $startYear ? ($startYear + $year - 1) : null,
                'opening_balance'         => round($openingBalance, 2),
                'lump_sum'                => round($lump, 2),
                'monthly_contribution'    => round($monthlyContribution * $monthlyCount, 2),
                'yearly_contribution'     => round($yearlyContribution, 2),
                'dividend_on_opening'     => round($dividendOnOpening, 2),
                'dividend_on_lump'        => round($dividendOnLump, 2),
                'dividend_on_monthly_adb' => round($dividendOnMonthly, 2),
                'total_dividends'         => round($totalDividends, 2),
                'dividends_earned'        => round($totalDividends, 2),          // alias
                'closing_balance'         => round($closingBalance, 2),
                'cumulative_value'        => round($closingBalance, 2),          // alias
                'cumulative_contributions'=> round($totalContributions, 2),
                'effective_return_pct'    => $openingBalance + $yearlyContribution > 0
                    ? round(($totalDividends / ($openingBalance + $yearlyContribution)) * 100, 4)
                    : 0,
            ];

            $openingBalance = $closingBalance;
        }

        $totalValue        = $openingBalance;
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
