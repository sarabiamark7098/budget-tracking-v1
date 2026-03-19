<?php

namespace App\Services;

use App\Models\Income;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class IncomeService
{
    public function getAll(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Income::with('category')
            ->where('user_id', $user->id);

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('received_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('received_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('source', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        $query->orderBy('received_at', 'desc');

        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    public function create(User $user, array $data): Income
    {
        return Income::create(array_merge($data, ['user_id' => $user->id]));
    }

    public function update(Income $income, array $data): Income
    {
        $income->update($data);
        return $income->fresh(['category']);
    }

    public function delete(Income $income): bool
    {
        return $income->delete();
    }

    public function getMonthlySummary(User $user, int $year): array
    {
        return DB::table('incomes')
            ->where('user_id', $user->id)
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
