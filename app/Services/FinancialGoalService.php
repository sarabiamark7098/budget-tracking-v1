<?php

namespace App\Services;

use App\Models\FinancialGoal;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class FinancialGoalService
{
    public function getAll(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = FinancialGoal::with('financialPlan')
            ->where('user_id', $user->id);

        if (!empty($filters['financial_plan_id'])) {
            $query->where('financial_plan_id', $filters['financial_plan_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(User $user, array $data): FinancialGoal
    {
        return FinancialGoal::create(array_merge($data, ['user_id' => $user->id]));
    }

    public function update(FinancialGoal $goal, array $data): FinancialGoal
    {
        $goal->update($data);
        return $goal->fresh(['financialPlan']);
    }

    public function delete(FinancialGoal $goal): bool
    {
        return $goal->delete();
    }

    public function updateProgress(FinancialGoal $goal, float $amount): FinancialGoal
    {
        $newAmount = (float) $goal->current_amount + $amount;
        $status = $newAmount >= (float) $goal->target_amount ? 'completed' : 'in_progress';

        $goal->update([
            'current_amount' => $newAmount,
            'status' => $status,
        ]);

        return $goal->fresh();
    }
}
