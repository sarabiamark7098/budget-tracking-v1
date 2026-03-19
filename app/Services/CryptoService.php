<?php

namespace App\Services;

use App\Models\CryptoAsset;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class CryptoService
{
    public function getAll(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = CryptoAsset::where('user_id', $user->id);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('coin_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('symbol', 'like', '%' . $filters['search'] . '%');
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(User $user, array $data): CryptoAsset
    {
        return CryptoAsset::create(array_merge($data, ['user_id' => $user->id]));
    }

    public function update(CryptoAsset $crypto, array $data): CryptoAsset
    {
        $crypto->update($data);
        return $crypto->fresh();
    }

    public function delete(CryptoAsset $crypto): bool
    {
        return $crypto->delete();
    }

    public function getPortfolioSummary(User $user): array
    {
        $assets = CryptoAsset::where('user_id', $user->id)->get();

        $totalCostBasis = $assets->sum(fn($a) => (float) $a->quantity * (float) $a->buy_price);
        $totalCurrentValue = $assets->sum(fn($a) => $a->current_value);
        $totalProfitLoss = $totalCurrentValue - $totalCostBasis;
        $totalProfitLossPercent = $totalCostBasis > 0 ? (($totalProfitLoss / $totalCostBasis) * 100) : 0;

        return [
            'total_cost_basis' => round($totalCostBasis, 2),
            'total_current_value' => round($totalCurrentValue, 2),
            'total_profit_loss' => round($totalProfitLoss, 2),
            'total_profit_loss_percentage' => round($totalProfitLossPercent, 2),
            'count' => $assets->count(),
            'assets' => $assets,
        ];
    }
}
