<?php

namespace App\Services;

use App\Models\BudgetTracking;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function getAll(BudgetTracking $budget, array $filters = []): LengthAwarePaginator
    {
        $query = Expense::with(['category', 'budget', 'files'])
            ->where('budget_tracking_id', $budget->id);

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['budget_id'])) {
            $query->where('budget_id', $filters['budget_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('spent_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('spent_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        $query->orderBy('spent_at', 'desc');

        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    public function create(BudgetTracking $budget, User $user, array $data): Expense
    {
        $data['budget_tracking_id'] = $budget->id;
        $data['user_id'] = $user->id;

        return Expense::create($data);
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->update($data);
        return $expense->fresh(['category', 'budget']);
    }

    /**
     * Return the budget impact for a given expense — used in API responses
     * to give immediate feedback on how this expense affects the budget.
     * Remaining amount may be negative when the budget has been exceeded.
     */
    public function getBudgetImpact(Expense $expense): ?array
    {
        $expense->loadMissing('budget');

        if (! $expense->budget) {
            return null;
        }

        $budget    = $expense->budget;
        $spent     = $budget->spent_amount;
        $allocated = (float) $budget->amount;
        $usagePct  = $allocated > 0 ? round(($spent / $allocated) * 100, 2) : 0;
        $remaining = $allocated - $spent;   // intentionally negative when over budget

        $status = match (true) {
            $usagePct > 100                       => 'over_budget',
            $usagePct >= $budget->alert_threshold => 'warning',
            default                               => 'on_track',
        };

        return [
            'budget_id'        => $budget->id,
            'budget_name'      => $budget->name,
            'allocated_amount' => $allocated,
            'spent_amount'     => round($spent, 2),
            'remaining_amount' => round($remaining, 2),
            'usage_pct'        => $usagePct,
            'alert_threshold'  => $budget->alert_threshold,
            'status'           => $status,
        ];
    }

    public function delete(Expense $expense): bool
    {
        return $expense->delete();
    }

    public function getMonthlySummary(BudgetTracking $budget, int $year): array
    {
        return DB::table('expenses')
            ->where('budget_tracking_id', $budget->id)
            ->whereNull('deleted_at')
            ->whereYear('spent_at', $year)
            ->select(
                DB::raw('MONTH(spent_at) as month'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('MONTH(spent_at)'))
            ->orderBy('month')
            ->get()
            ->toArray();
    }
}
