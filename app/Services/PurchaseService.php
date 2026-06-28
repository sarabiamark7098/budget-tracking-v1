<?php

namespace App\Services;

use App\Models\BudgetTracking;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    private static function bustCache(int $btId): void
    {
        DashboardService::clearAllTimeCache($btId);
    }
    public function getAll(BudgetTracking $budget, array $filters = []): LengthAwarePaginator
    {
        $query = Purchase::where('budget_tracking_id', $budget->id);

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['search'])) {
            $query->where('item_name', 'like', '%' . $filters['search'] . '%');
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->with('payments')
            ->orderByRaw("CASE WHEN payment_method = 'credit_card' AND COALESCE(installments_paid,0) < COALESCE(installment_count,0) THEN 0 ELSE 1 END, created_at DESC")
            ->paginate($perPage);
    }

    public function create(BudgetTracking $budget, User $user, array $data): Purchase
    {
        // Credit card = installment; cash/other = one-time
        $data['is_installment'] = ($data['payment_method'] ?? 'cash') === 'credit_card';

        // Auto-calculate monthly cost if not provided
        if ($data['is_installment']
            && !empty($data['installment_count'])
            && empty($data['installment_amount'])
        ) {
            $data['installment_amount'] = (float) $data['total_cost'] / (int) $data['installment_count'];
        }

        // Non-CC purchases: clear installment fields
        if (! $data['is_installment']) {
            $data['installment_count']  = null;
            $data['installment_amount'] = null;
            $data['installments_paid']  = 0;
        }

        $data['installments_paid'] = $data['installments_paid'] ?? 0;

        $purchase = Purchase::create(array_merge($data, [
            'budget_tracking_id' => $budget->id,
            'user_id'            => $user->id,
        ]));
        self::bustCache($budget->id);
        return $purchase;
    }

    public function update(Purchase $purchase, array $data): Purchase
    {
        if (isset($data['payment_method'])) {
            $data['is_installment'] = $data['payment_method'] === 'credit_card';

            if (! $data['is_installment']) {
                $data['installment_count']  = null;
                $data['installment_amount'] = null;
                $data['installments_paid']  = 0;
            }
        }

        // Recalculate monthly cost when months change but amount not supplied
        if (
            ($data['is_installment'] ?? $purchase->is_installment)
            && !empty($data['installment_count'])
            && empty($data['installment_amount'])
        ) {
            $totalCost = $data['total_cost'] ?? (float) $purchase->total_cost;
            $data['installment_amount'] = $totalCost / (int) $data['installment_count'];
        }

        $purchase->update($data);
        self::bustCache((int) $purchase->budget_tracking_id);
        return $purchase->fresh();
    }

    public function delete(Purchase $purchase): bool
    {
        $btId = (int) $purchase->budget_tracking_id;
        $result = $purchase->delete();
        self::bustCache($btId);
        return $result;
    }

    public function payInstallment(Purchase $purchase): array|Purchase
    {
        if ($purchase->payment_method !== 'credit_card' || $purchase->remaining_installments <= 0) {
            return $purchase;
        }

        $monthly = (float) $purchase->installment_amount;
        $nextInstallmentNumber = $purchase->installments_paid + 1;

        DB::transaction(function () use ($purchase, $monthly, $nextInstallmentNumber) {
            $purchase->update(['installments_paid' => $nextInstallmentNumber]);

            PurchasePayment::create([
                'purchase_id'        => $purchase->id,
                'budget_tracking_id' => $purchase->budget_tracking_id,
                'user_id'            => $purchase->user_id,
                'amount'             => $monthly,
                'paid_at'            => now()->toDateString(),
                'installment_number' => $nextInstallmentNumber,
            ]);
        });

        self::bustCache((int) $purchase->budget_tracking_id);
        return $purchase->fresh(['payments']);
    }

    public function getSummary(BudgetTracking $budget): array
    {
        $purchases = Purchase::where('budget_tracking_id', $budget->id)->get();

        $totalPurchase    = $purchases->sum(fn($p) => (float) $p->total_cost);
        $totalPaid        = $purchases->sum(fn($p) => $p->amount_paid);
        $totalRemaining   = $purchases->sum(fn($p) => $p->remaining_balance);

        return [
            'total_purchase'   => round($totalPurchase, 2),
            'total_paid'       => round($totalPaid, 2),
            'total_remaining'  => round($totalRemaining, 2),
        ];
    }
}
