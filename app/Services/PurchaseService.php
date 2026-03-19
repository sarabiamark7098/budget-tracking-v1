<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class PurchaseService
{
    public function getAll(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Purchase::with(['category', 'files'])
            ->where('user_id', $user->id);

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['is_installment'])) {
            $query->where('is_installment', (bool) $filters['is_installment']);
        }

        if (!empty($filters['search'])) {
            $query->where('item_name', 'like', '%' . $filters['search'] . '%');
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('purchase_date', 'desc')->paginate($perPage);
    }

    public function create(User $user, array $data): Purchase
    {
        if (!empty($data['is_installment']) && !empty($data['installment_count']) && empty($data['installment_amount'])) {
            $data['installment_amount'] = (float) $data['total_cost'] / (int) $data['installment_count'];
        }
        return Purchase::create(array_merge($data, ['user_id' => $user->id]));
    }

    public function update(Purchase $purchase, array $data): Purchase
    {
        $purchase->update($data);
        return $purchase->fresh(['category']);
    }

    public function delete(Purchase $purchase): bool
    {
        return $purchase->delete();
    }

    public function payInstallment(Purchase $purchase): Purchase
    {
        if (!$purchase->is_installment) {
            return $purchase;
        }

        $newPaid = $purchase->installments_paid + 1;
        $purchase->update(['installments_paid' => $newPaid]);

        return $purchase->fresh();
    }
}
