<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MP2Plan extends Model
{
    use SoftDeletes;

    protected $table = 'mp2_plans';

    protected $fillable = [
        'user_id',
        'budget_tracking_id',
        'name',
        'monthly_contribution',
        'duration_years',
        'start_date',
        'projected_earnings',
        'total_contributions',
        'notes',
    ];

    protected $casts = [
        'monthly_contribution' => 'decimal:2',
        'projected_earnings' => 'decimal:2',
        'total_contributions' => 'decimal:2',
        'start_date' => 'date',
    ];

    public function budgetTracking(): BelongsTo
    {
        return $this->belongsTo(BudgetTracking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calculateProjectedEarnings(): array
    {
        $monthlyContribution = (float) $this->monthly_contribution;
        $years = (int) $this->duration_years;
        $annualDividendRate = 0.0703;

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
                'cumulative_value' => round($totalValue, 2),
            ];
        }

        $projectedEarnings = $totalValue - $totalContributions;

        return [
            'total_contributions' => round($totalContributions, 2),
            'projected_earnings' => round($projectedEarnings, 2),
            'total_value' => round($totalValue, 2),
            'annual_dividend_rate' => $annualDividendRate * 100,
            'yearly_breakdown' => $yearlyBreakdown,
        ];
    }
}
