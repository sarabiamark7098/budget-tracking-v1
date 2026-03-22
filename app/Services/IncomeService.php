<?php

namespace App\Services;

use App\Models\BudgetTracking;
use App\Models\Income;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class IncomeService
{
    // Clear the dashboard all-time totals cache whenever income changes.
    private static function bustCache(int $btId): void
    {
        DashboardService::clearAllTimeCache($btId);
    }
    public function getAll(BudgetTracking $budget, array $filters = []): LengthAwarePaginator
    {
        $query = Income::where('budget_tracking_id', $budget->id);

        if (!empty($filters['date_from'])) {
            $query->where('received_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('received_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('source', 'like', '%' . $filters['search'] . '%');
            });
        }

        $query->orderBy('received_at', 'desc');

        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    public function create(BudgetTracking $budget, User $user, array $data): Income
    {
        $data['budget_tracking_id'] = $budget->id;
        $data['user_id'] = $user->id;
        $data['source']  = $this->sanitizeSource($data['source'] ?? null);

        $income = Income::create($data);
        self::bustCache($budget->id);
        return $income;
    }

    public function update(Income $income, array $data): Income
    {
        if (array_key_exists('source', $data)) {
            $data['source'] = $this->sanitizeSource($data['source']);
        }

        $income->update($data);
        self::bustCache((int) $income->budget_tracking_id);
        return $income->fresh();
    }

    /**
     * Accept only the five valid enum values; coerce everything else to null.
     * This prevents a MySQL ENUM truncation error when old free-text data
     * (e.g. raw bank descriptions) is passed in the source field.
     */
    private function sanitizeSource(?string $source): ?string
    {
        $allowed = [
            'Compensation Income',
            'Business Income',
            'Passive Income',
            'Property Gains',
            'Other Sources',
        ];

        return in_array($source, $allowed, true) ? $source : null;
    }

    public function delete(Income $income): bool
    {
        $btId = (int) $income->budget_tracking_id;
        $result = $income->delete();
        self::bustCache($btId);
        return $result;
    }

    public function getMonthlySummary(BudgetTracking $budget, int $year): array
    {
        return DB::table('incomes')
            ->where('budget_tracking_id', $budget->id)
            ->whereNull('deleted_at')
            ->whereYear('received_at', $year)
            ->select(
                DB::raw('MONTH(received_at) as month'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('MONTH(received_at)'))
            ->orderBy('month')
            ->get()
            ->toArray();
    }
}
