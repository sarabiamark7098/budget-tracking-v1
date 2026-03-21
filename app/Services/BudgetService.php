<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetTracking;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class BudgetService
{
    public function getAll(BudgetTracking $budget, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Budget::with('category')
            ->where('budget_tracking_id', $budget->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function create(BudgetTracking $budget, User $user, array $data): Budget
    {
        return Budget::create(array_merge($data, [
            'budget_tracking_id' => $budget->id,
            'user_id'            => $user->id,
        ]));
    }

    public function update(Budget $budget, array $data): Budget
    {
        $budget->update($data);
        return $budget->fresh(['category']);
    }

    public function delete(Budget $budget): bool
    {
        return $budget->delete();
    }

    public function getBudgetSummary(BudgetTracking $budgetTracking): array
    {
        $budgets = Budget::with('category')
            ->where('budget_tracking_id', $budgetTracking->id)
            ->get();

        $totalBudget    = $budgets->sum(fn($b) => $b->total_budget);
        $totalSpent     = $budgets->sum(fn($b) => $b->spent_amount);
        $totalRemaining = $totalBudget - $totalSpent;

        return [
            'total_budget'    => round($totalBudget, 2),
            'total_spent'     => round($totalSpent, 2),
            'total_remaining' => round($totalRemaining, 2),
            'items'           => $budgets->map(fn(Budget $b) => [
                'id'               => $b->id,
                'name'             => $b->name,
                'amount'           => $b->amount,
                'total_budget'     => $b->total_budget,
                'spent_amount'     => $b->spent_amount,
                'remaining_amount' => $b->remaining_amount,
                'usage_percentage' => $b->usage_percentage,
                'is_over_budget'   => $b->remaining_amount < 0,
                'category'         => $b->category,
            ])->toArray(),
        ];
    }
}
