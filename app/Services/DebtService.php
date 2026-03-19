<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class DebtService
{
    public function getAll(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Debt::with('payments')
            ->where('user_id', $user->id);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(User $user, array $data): Debt
    {
        $data['remaining_balance'] = $data['remaining_balance'] ?? $data['amount'];
        return Debt::create(array_merge($data, ['user_id' => $user->id]));
    }

    public function update(Debt $debt, array $data): Debt
    {
        $debt->update($data);
        return $debt->fresh(['payments']);
    }

    public function delete(Debt $debt): bool
    {
        return $debt->delete();
    }

    public function calculateRemainingBalance(Debt $debt): float
    {
        $totalPaid = $debt->payments()->sum('amount');
        return max(0, (float) $debt->amount - (float) $totalPaid);
    }
}
