<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentService
{
    public function getAll(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Payment::with('debt')
            ->where('user_id', $user->id);

        if (!empty($filters['debt_id'])) {
            $query->where('debt_id', $filters['debt_id']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('payment_date', 'desc')->paginate($perPage);
    }

    public function create(User $user, array $data): Payment
    {
        $payment = Payment::create(array_merge($data, ['user_id' => $user->id]));
        return $payment->load('debt');
    }

    public function getPaymentHistory(Debt $debt): Collection
    {
        return $debt->payments()->orderBy('payment_date', 'desc')->get();
    }

    public function delete(Payment $payment): bool
    {
        return $payment->delete();
    }
}
