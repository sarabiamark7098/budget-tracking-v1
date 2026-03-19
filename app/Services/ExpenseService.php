<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function getAll(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Expense::with(['category', 'files'])
            ->where('user_id', $user->id);

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
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

    public function create(User $user, array $data): Expense
    {
        return Expense::create(array_merge($data, ['user_id' => $user->id]));
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->update($data);
        return $expense->fresh(['category']);
    }

    public function delete(Expense $expense): bool
    {
        return $expense->delete();
    }

    public function getMonthlySummary(User $user, int $year): array
    {
        return DB::table('expenses')
            ->where('user_id', $user->id)
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
