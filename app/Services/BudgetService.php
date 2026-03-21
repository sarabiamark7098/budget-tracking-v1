<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetTracking;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BudgetService
{
    public function getAll(BudgetTracking $budget, array $filters = []): LengthAwarePaginator
    {
        $query = Budget::with('category')
            ->where('budget_tracking_id', $budget->id);

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
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
        $data['alert_threshold'] ??= $budget->alert_threshold ?? 80;
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

        return $budgets->map(function (Budget $budget) {
            return [
                'id' => $budget->id,
                'name' => $budget->name,
                'amount' => $budget->amount,
                'spent_amount' => $budget->spent_amount,
                'remaining_amount' => $budget->remaining_amount,
                'usage_percentage' => $budget->usage_percentage,
                'alert_threshold' => $budget->alert_threshold,
                'is_over_budget' => $budget->spent_amount > (float) $budget->amount,
                'alert_triggered' => $budget->usage_percentage >= $budget->alert_threshold,
                'category' => $budget->category,
            ];
        })->toArray();
    }
}
