<?php

namespace App\Services;

use App\Models\MP2Plan;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class MP2Service
{
    const ANNUAL_DIVIDEND_RATE = 0.0703;

    public function getAll(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = MP2Plan::where('user_id', $user->id);
        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(User $user, array $data): MP2Plan
    {
        $calculation = $this->calculate($data);
        $data['projected_earnings'] = $calculation['projected_earnings'];
        $data['total_contributions'] = $calculation['total_contributions'];

        return MP2Plan::create(array_merge($data, ['user_id' => $user->id]));
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

    public function calculate(array $data): array
    {
        $monthlyContribution = (float) ($data['monthly_contribution'] ?? 0);
        $years = (int) ($data['duration_years'] ?? 1);
        $annualDividendRate = self::ANNUAL_DIVIDEND_RATE;

        $totalContributions = $monthlyContribution * 12 * $years;
        $totalValue = 0;
        $yearlyBreakdown = [];

        for ($year = 1; $year <= $years; $year++) {
            $yearlyContribution = $monthlyContribution * 12;
            $balanceAtStartOfYear = $totalValue;
            $dividends = ($balanceAtStartOfYear + $yearlyContribution) * $annualDividendRate;
            $totalValue = $balanceAtStartOfYear + $yearlyContribution + $dividends;

            $yearlyBreakdown[] = [
                'year' => $year,
                'yearly_contribution' => round($yearlyContribution, 2),
                'dividends_earned' => round($dividends, 2),
                'cumulative_contributions' => round($monthlyContribution * 12 * $year, 2),
                'cumulative_value' => round($totalValue, 2),
            ];
        }

        $projectedEarnings = $totalValue - $totalContributions;

        return [
            'monthly_contribution' => $monthlyContribution,
            'duration_years' => $years,
            'total_contributions' => round($totalContributions, 2),
            'projected_earnings' => round($projectedEarnings, 2),
            'total_value' => round($totalValue, 2),
            'annual_dividend_rate' => $annualDividendRate * 100,
            'yearly_breakdown' => $yearlyBreakdown,
        ];
    }
}
