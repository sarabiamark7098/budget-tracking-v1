<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class StockService
{
    public function getAll(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Stock::where('user_id', $user->id);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('symbol', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('company_name', 'like', '%' . $filters['search'] . '%');
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(User $user, array $data): Stock
    {
        return Stock::create(array_merge($data, ['user_id' => $user->id]));
    }

    public function update(Stock $stock, array $data): Stock
    {
        $stock->update($data);
        return $stock->fresh();
    }

    public function delete(Stock $stock): bool
    {
        return $stock->delete();
    }

    public function getPortfolioSummary(User $user): array
    {
        $stocks = Stock::where('user_id', $user->id)->get();

        $totalCostBasis = $stocks->sum(fn($s) => (float) $s->shares * (float) $s->buy_price);
        $totalCurrentValue = $stocks->sum(fn($s) => $s->current_value);
        $totalProfitLoss = $totalCurrentValue - $totalCostBasis;
        $totalProfitLossPercent = $totalCostBasis > 0 ? (($totalProfitLoss / $totalCostBasis) * 100) : 0;

        return [
            'total_cost_basis' => round($totalCostBasis, 2),
            'total_current_value' => round($totalCurrentValue, 2),
            'total_profit_loss' => round($totalProfitLoss, 2),
            'total_profit_loss_percentage' => round($totalProfitLossPercent, 2),
            'count' => $stocks->count(),
            'stocks' => $stocks,
        ];
    }
}
