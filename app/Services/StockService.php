<?php

namespace App\Services;

use App\Models\BudgetTracking;
use App\Models\ModuleTransfer;
use App\Models\Stock;
use App\Models\StockLot;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class StockService
{
    public function getAll(BudgetTracking $budget, array $filters = []): LengthAwarePaginator
    {
        $query = Stock::where('budget_tracking_id', $budget->id);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('symbol', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('company_name', 'like', '%' . $filters['search'] . '%');
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(BudgetTracking $budget, User $user, array $data): Stock
    {
        return Stock::create(array_merge($data, [
            'budget_tracking_id' => $budget->id,
            'user_id'            => $user->id,
        ]));
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

    public function getLots(Stock $stock): array
    {
        $lots = $stock->lots()->orderBy('purchase_date', 'desc')->get();

        $latestPrice = (float) ($stock->latest_price ?? 0);

        return $lots->map(function ($lot) use ($latestPrice) {
            $costBasis    = (float) $lot->shares * (float) $lot->buy_price;
            $currentValue = (float) $lot->shares * $latestPrice;
            $pnl          = $currentValue - $costBasis;
            $pnlPct       = $costBasis > 0 ? round(($pnl / $costBasis) * 100, 2) : 0;

            return [
                'id'            => $lot->id,
                'shares'        => $lot->shares,
                'buy_price'     => $lot->buy_price,
                'purchase_date' => $lot->purchase_date?->toDateString(),
                'cost_basis'    => round($costBasis, 2),
                'current_value' => round($currentValue, 2),
                'pnl'           => round($pnl, 2),
                'pnl_pct'       => $pnlPct,
                'created_at'    => $lot->created_at,
            ];
        })->toArray();
    }

    public function addLot(Stock $stock, array $data): StockLot
    {
        return $stock->lots()->create($data);
    }

    public function updateLatestPrice(Stock $stock, float $price): Stock
    {
        $stock->update(['latest_price' => $price]);
        return $stock->fresh();
    }

    public function getPortfolioSummary(BudgetTracking $budget): array
    {
        $stocks = Stock::with('lots')->where('budget_tracking_id', $budget->id)->get();

        $allLots = $stocks->flatMap(fn($s) => $s->lots->map(fn($l) => ['lot' => $l, 'stock' => $s]));

        $totalCostBasis    = $allLots->sum(fn($r) => (float) $r['lot']->shares * (float) $r['lot']->buy_price);
        $totalCurrentValue = $allLots->sum(function ($r) {
            $price = (float) ($r['stock']->latest_price ?? $r['lot']->current_price);
            return (float) $r['lot']->shares * $price;
        });
        $totalProfitLoss        = $totalCurrentValue - $totalCostBasis;
        $totalProfitLossPercent = $totalCostBasis > 0 ? (($totalProfitLoss / $totalCostBasis) * 100) : 0;

        $bySymbol = $stocks->map(function ($stock) use ($totalCurrentValue) {
            $lots          = $stock->lots;
            $totalShares   = $lots->sum(fn($l) => (float) $l->shares);
            $totalCost     = $lots->sum(fn($l) => (float) $l->shares * (float) $l->buy_price);
            $latestPrice   = (float) ($stock->latest_price ?? 0);
            $currentVal    = $totalShares * $latestPrice;
            $weightedAvg   = $totalShares > 0 ? ($totalCost / $totalShares) : 0;
            $unrealizedPnL = $currentVal - $totalCost;
            $unrealizedPct = $totalCost > 0 ? (($unrealizedPnL / $totalCost) * 100) : 0;
            $allocation    = $totalCurrentValue > 0 ? (($currentVal / $totalCurrentValue) * 100) : 0;

            return [
                'stock_id'                 => $stock->id,
                'symbol'                   => $stock->symbol,
                'company_name'             => $stock->company_name,
                'latest_price'             => round($latestPrice, 4),
                'total_shares'             => round($totalShares, 4),
                'total_cost_basis'         => round($totalCost, 2),
                'weighted_avg_cost'        => round($weightedAvg, 4),
                'current_value'            => round($currentVal, 2),
                'unrealized_pnl'           => round($unrealizedPnL, 2),
                'unrealized_pnl_pct'       => round($unrealizedPct, 2),
                'portfolio_allocation_pct' => round($allocation, 2),
                'lot_count'                => $lots->count(),
            ];
        })->values();

        $hhi = $bySymbol->sum(fn($s) => pow($s['portfolio_allocation_pct'] / 100, 2));

        $totalTransferred = (float) ModuleTransfer::where('budget_tracking_id', $budget->id)
            ->where('module', 'stock')->sum('amount');
        $availableBalance = $totalTransferred - $totalCostBasis;

        return [
            'total_cost_basis'             => round($totalCostBasis, 2),
            'total_current_value'          => round($totalCurrentValue, 2),
            'total_profit_loss'            => round($totalProfitLoss, 2),
            'total_profit_loss_percentage' => round($totalProfitLossPercent, 2),
            'total_transferred'            => round($totalTransferred, 2),
            'available_balance'            => round($availableBalance, 2),
            'concentration_index_hhi'      => round($hhi, 4),
            'diversification_level'        => $hhi < 0.15 ? 'high' : ($hhi < 0.25 ? 'moderate' : 'low'),
            'count'                        => $stocks->count(),
            'unique_symbols'               => $bySymbol->count(),
            'by_symbol'                    => $bySymbol,
        ];
    }
}
