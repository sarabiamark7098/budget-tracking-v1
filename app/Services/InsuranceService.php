<?php

namespace App\Services;

use App\Models\BudgetTracking;
use App\Models\InsurancePlan;
use App\Models\InsurancePayment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class InsuranceService
{
    public function getAll(BudgetTracking $budget, array $filters = []): LengthAwarePaginator
    {
        $query = InsurancePlan::with(['insurancePayments', 'files'])
            ->where('budget_tracking_id', $budget->id);

        if (!empty($filters['coverage_type'])) {
            $query->where('coverage_type', $filters['coverage_type']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(BudgetTracking $budget, User $user, array $data): InsurancePlan
    {
        return InsurancePlan::create(array_merge($data, [
            'budget_tracking_id' => $budget->id,
            'user_id'            => $user->id,
        ]));
    }

    public function update(InsurancePlan $plan, array $data): InsurancePlan
    {
        $plan->update($data);
        return $plan->fresh(['insurancePayments']);
    }

    public function delete(InsurancePlan $plan): bool
    {
        return $plan->delete();
    }

    public function recordPayment(BudgetTracking $budget, User $user, array $data): InsurancePayment
    {
        return InsurancePayment::create(array_merge($data, [
            'budget_tracking_id' => $budget->id,
            'user_id'            => $user->id,
        ]));
    }

    public function getPayments(BudgetTracking $budget, array $filters = []): LengthAwarePaginator
    {
        $query = InsurancePayment::with('insurancePlan')
            ->where('budget_tracking_id', $budget->id);

        if (!empty($filters['insurance_plan_id'])) {
            $query->where('insurance_plan_id', $filters['insurance_plan_id']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('payment_date', 'desc')->paginate($perPage);
    }
}
