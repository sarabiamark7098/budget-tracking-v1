<?php

namespace App\Services;

use App\Models\BudgetTracking;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    private static function bustCache(int $btId): void
    {
        DashboardService::clearAllTimeCache($btId);
    }
    public function getAll(BudgetTracking $budget, array $filters = []): LengthAwarePaginator
    {
        $query = Expense::with(['budget'])
            ->where('budget_tracking_id', $budget->id);

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
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        $query->orderBy('spent_at', 'desc');

        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    public function create(BudgetTracking $budget, User $user, array $data): Expense
    {
        $data['budget_tracking_id'] = $budget->id;
        $data['user_id']            = $user->id;
        $data['spent_at']           = now()->toDateString(); // always recorded as today

        $expense = Expense::create($data);
        self::bustCache($budget->id);
        return $expense;
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->update($data);
        self::bustCache((int) $expense->budget_tracking_id);
        return $expense->fresh(['budget']);
    }

    /**
     * Return live budget impact after an expense is saved.
     * Uses total_budget (cumulative) so remaining can be negative.
     */
    public function getBudgetImpact(Expense $expense): ?array
    {
        $expense->loadMissing('budget');

        if (! $expense->budget) {
            return null;
        }

        $b         = $expense->budget;
        $total     = $b->total_budget;
        $spent     = $b->spent_amount;
        $remaining = $b->remaining_amount;
        $usagePct  = $total > 0 ? round(($spent / $total) * 100, 2) : 0;

        $status = match (true) {
            $usagePct > 100  => 'over_budget',
            $usagePct >= 80  => 'warning',
            default          => 'on_track',
        };

        return [
            'budget_id'        => $b->id,
            'budget_name'      => $b->name,
            'total_budget'     => round($total, 2),
            'spent_amount'     => round($spent, 2),
            'remaining_amount' => round($remaining, 2),
            'usage_pct'        => $usagePct,
            'status'           => $status,
        ];
    }

    public function delete(Expense $expense): bool
    {
        $btId = (int) $expense->budget_tracking_id;
        $result = $expense->delete();
        self::bustCache($btId);
        return $result;
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
