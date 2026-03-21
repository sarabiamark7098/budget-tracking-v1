<?php

namespace App\Services;

use App\Models\BudgetTracking;
use App\Models\Investment;
use App\Models\InvestmentPayment;
use App\Models\ModuleTransfer;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class InvestmentService
{
    public function getAll(BudgetTracking $budget, array $filters = []): LengthAwarePaginator
    {
        $query = Investment::with('category')
            ->withSum('payments', 'amount')
            ->where('budget_tracking_id', $budget->id);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(BudgetTracking $budget, User $user, array $data): Investment
    {
        return Investment::create(array_merge($data, [
            'budget_tracking_id' => $budget->id,
            'user_id'            => $user->id,
        ]));
    }

    public function update(Investment $investment, array $data): Investment
    {
        $investment->update($data);
        return $investment->fresh(['category']);
    }

    public function delete(Investment $investment): bool
    {
        return $investment->delete();
    }

    public function getPortfolioSummary(BudgetTracking $budget): array
    {
        $btId        = $budget->id;
        $investments = Investment::where('budget_tracking_id', $btId)->get();

        $totalInvested     = $investments->sum('amount_invested');
        $totalCurrentValue = $investments->sum('current_value');
        $totalROIAmount    = $totalCurrentValue - $totalInvested;
        $totalROIPercent   = $totalInvested > 0 ? (($totalROIAmount / $totalInvested) * 100) : 0;

        $byType = $investments->groupBy('type')->map(function ($group) {
            return [
                'count'          => $group->count(),
                'amount_invested' => $group->sum('amount_invested'),
                'current_value'  => $group->sum('current_value'),
                'roi_amount'     => $group->sum('current_value') - $group->sum('amount_invested'),
            ];
        });

        $totalTransferred = (float) ModuleTransfer::where('budget_tracking_id', $btId)
            ->where('module', 'investment')->sum('amount');
        $availableBalance = $totalTransferred - $totalInvested;

        // Payment obligations (real_estate + other only — mutual_fund has no fixed ceiling)
        $payableInvestments = $investments->whereIn('type', ['real_estate', 'other']);
        $totalObligations   = $payableInvestments->sum(
            fn($i) => ($i->months_of_payment ?? 0) * ($i->amount_per_payment ?? 0)
        );

        // Total paid across all investment types with payment tracking
        $totalPaidAll = (float) InvestmentPayment::whereHas('investment', function ($q) use ($btId) {
            $q->where('budget_tracking_id', $btId);
        })->sum('amount');

        return [
            'total_invested'       => round($totalInvested, 2),
            'total_current_value'  => round($totalCurrentValue, 2),
            'total_roi_amount'     => round($totalROIAmount, 2),
            'total_roi_percentage' => round($totalROIPercent, 2),
            'total_transferred'    => round($totalTransferred, 2),
            'available_balance'    => round($availableBalance, 2),
            'by_type'              => $byType,
            'count'                => $investments->count(),
            // payment stats
            'total_obligations'      => round($totalObligations, 2),
            'total_paid_all'         => round($totalPaidAll, 2),
            'remaining_obligations'  => round(max(0, $totalObligations - $totalPaidAll), 2),
            'available_for_payments' => round(max(0, $totalTransferred - $totalPaidAll), 2),
        ];
    }
}
