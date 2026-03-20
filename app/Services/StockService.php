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

        $totalCostBasis    = $stocks->sum(fn($s) => (float) $s->shares * (float) $s->buy_price);
        $totalCurrentValue = $stocks->sum(fn($s) => $s->current_value);
        $totalProfitLoss   = $totalCurrentValue - $totalCostBasis;
        $totalProfitLossPercent = $totalCostBasis > 0
            ? (($totalProfitLoss / $totalCostBasis) * 100)
            : 0;

        // Weighted average cost per ticker + per-ticker allocation
        $bySymbol = $stocks->groupBy('symbol')->map(function ($lots) use ($totalCurrentValue) {
            $totalShares   = $lots->sum(fn($s) => (float) $s->shares);
            $totalCost     = $lots->sum(fn($s) => (float) $s->shares * (float) $s->buy_price);
            $currentVal    = $lots->sum(fn($s) => $s->current_value);
            $weightedAvgCost = $totalShares > 0 ? ($totalCost / $totalShares) : 0;
            $latestPrice   = (float) $lots->sortByDesc('purchase_date')->first()->current_price;
            $unrealizedPnL = $currentVal - $totalCost;
            $unrealizedPct = $totalCost > 0 ? (($unrealizedPnL / $totalCost) * 100) : 0;
            $allocation    = $totalCurrentValue > 0 ? (($currentVal / $totalCurrentValue) * 100) : 0;

            return [
                'symbol'               => $lots->first()->symbol,
                'company_name'         => $lots->first()->company_name,
                'total_shares'         => round($totalShares, 4),
                'total_cost_basis'     => round($totalCost, 2),
                'weighted_avg_cost'    => round($weightedAvgCost, 4),
                'current_price'        => round($latestPrice, 4),
                'current_value'        => round($currentVal, 2),
                'unrealized_pnl'       => round($unrealizedPnL, 2),
                'unrealized_pnl_pct'   => round($unrealizedPct, 2),
                'portfolio_allocation_pct' => round($allocation, 2),
                'lot_count'            => $lots->count(),
            ];
        })->values();

        // Concentration risk: Herfindahl–Hirschman Index (HHI) on current value
        // HHI = Σ (allocation_i/100)² — ranges 0 (diversified) to 1 (concentrated)
        $hhi = $bySymbol->sum(fn($s) => pow($s['portfolio_allocation_pct'] / 100, 2));

        return [
            'total_cost_basis'             => round($totalCostBasis, 2),
            'total_current_value'          => round($totalCurrentValue, 2),
            'total_profit_loss'            => round($totalProfitLoss, 2),
            'total_profit_loss_percentage' => round($totalProfitLossPercent, 2),
            'concentration_index_hhi'      => round($hhi, 4),
            'diversification_level'        => $hhi < 0.15 ? 'high' : ($hhi < 0.25 ? 'moderate' : 'low'),
            'count'                        => $stocks->count(),
            'unique_symbols'               => $bySymbol->count(),
            'by_symbol'                    => $bySymbol,
            'stocks'                       => $stocks,
        ];
    }
}
