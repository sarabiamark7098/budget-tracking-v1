<?php

namespace App\Services;

use App\Models\BudgetTracking;
use App\Models\CryptoAsset;
use App\Models\CryptoLot;
use App\Models\ModuleTransfer;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class CryptoService
{
    public function getAll(BudgetTracking $budget, array $filters = []): LengthAwarePaginator
    {
        $query = CryptoAsset::where('budget_tracking_id', $budget->id);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('coin_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('symbol', 'like', '%' . $filters['search'] . '%');
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(BudgetTracking $budget, User $user, array $data): CryptoAsset
    {
        return CryptoAsset::create(array_merge($data, [
            'budget_tracking_id' => $budget->id,
            'user_id'            => $user->id,
        ]));
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

    public function getLots(CryptoAsset $crypto): array
    {
        $lots        = $crypto->lots()->orderBy('purchase_date', 'desc')->get();
        $latestPrice = (float) ($crypto->latest_price ?? 0);

        return $lots->map(function ($lot) use ($latestPrice) {
            $costBasis    = (float) $lot->quantity * (float) $lot->buy_price;
            $currentValue = (float) $lot->quantity * $latestPrice;
            $pnl          = $currentValue - $costBasis;
            $pnlPct       = $costBasis > 0 ? round(($pnl / $costBasis) * 100, 2) : 0;

            return [
                'id'            => $lot->id,
                'quantity'      => $lot->quantity,
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

    public function addLot(CryptoAsset $crypto, array $data): CryptoLot
    {
        return $crypto->lots()->create($data);
    }

    public function updateLatestPrice(CryptoAsset $crypto, float $price): CryptoAsset
    {
        $crypto->update(['latest_price' => $price]);
        return $crypto->fresh();
    }

    public function getPortfolioSummary(BudgetTracking $budget): array
    {
        $assets = CryptoAsset::with('lots')->where('budget_tracking_id', $budget->id)->get();

        $allLots = $assets->flatMap(fn($a) => $a->lots->map(fn($l) => ['lot' => $l, 'asset' => $a]));

        $totalCostBasis    = $allLots->sum(fn($r) => (float) $r['lot']->quantity * (float) $r['lot']->buy_price);
        $totalCurrentValue = $allLots->sum(function ($r) {
            $price = (float) ($r['asset']->latest_price ?? 0);
            return (float) $r['lot']->quantity * $price;
        });
        $totalProfitLoss        = $totalCurrentValue - $totalCostBasis;
        $totalProfitLossPercent = $totalCostBasis > 0 ? (($totalProfitLoss / $totalCostBasis) * 100) : 0;

        $bySymbol = $assets->map(function ($asset) use ($totalCurrentValue) {
            $lots        = $asset->lots;
            $totalQty    = $lots->sum(fn($l) => (float) $l->quantity);
            $totalCost   = $lots->sum(fn($l) => (float) $l->quantity * (float) $l->buy_price);
            $latestPrice = (float) ($asset->latest_price ?? 0);
            $currentVal  = $totalQty * $latestPrice;
            $avgCost     = $totalQty > 0 ? ($totalCost / $totalQty) : 0;
            $pnl         = $currentVal - $totalCost;
            $pnlPct      = $totalCost > 0 ? (($pnl / $totalCost) * 100) : 0;
            $allocPct    = $totalCurrentValue > 0 ? (($currentVal / $totalCurrentValue) * 100) : 0;

            return [
                'asset_id'         => $asset->id,
                'symbol'           => $asset->symbol,
                'coin_name'        => $asset->coin_name,
                'latest_price'     => round($latestPrice, 8),
                'total_qty'        => round($totalQty, 8),
                'total_cost'       => round($totalCost, 2),
                'avg_cost'         => round($avgCost, 8),
                'current_value'    => round($currentVal, 2),
                'unrealized_pnl'   => round($pnl, 2),
                'unrealized_pnl_pct' => round($pnlPct, 2),
                'alloc_pct'        => round($allocPct, 2),
                'lot_count'        => $lots->count(),
            ];
        })->values();

        $totalTransferred = (float) ModuleTransfer::where('budget_tracking_id', $budget->id)
            ->where('module', 'crypto')->sum('amount');
        $availableBalance = $totalTransferred - $totalCostBasis;

        return [
            'total_cost_basis'             => round($totalCostBasis, 2),
            'total_current_value'          => round($totalCurrentValue, 2),
            'total_profit_loss'            => round($totalProfitLoss, 2),
            'total_profit_loss_percentage' => round($totalProfitLossPercent, 2),
            'total_transferred'            => round($totalTransferred, 2),
            'available_balance'            => round($availableBalance, 2),
            'count'                        => $assets->count(),
            'by_symbol'                    => $bySymbol,
        ];
    }
}
