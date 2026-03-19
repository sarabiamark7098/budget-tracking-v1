<?php

namespace App\Services;

use App\Models\FinancialPlan;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class FinancialPlanService
{
    public function getAll(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = FinancialPlan::with('financialGoals')
            ->where('user_id', $user->id);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(User $user, array $data): FinancialPlan
    {
        return FinancialPlan::create(array_merge($data, ['user_id' => $user->id]));
    }

    public function update(FinancialPlan $plan, array $data): FinancialPlan
    {
        $plan->update($data);
        return $plan->fresh(['financialGoals']);
    }

    public function delete(FinancialPlan $plan): bool
    {
        return $plan->delete();
    }
}
