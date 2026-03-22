<?php

namespace App\Services;

use App\Models\BudgetTracking;
use App\Models\CryptoAsset;
use App\Models\CryptoDividend;
use App\Models\CryptoLot;
use App\Models\CryptoSale;
use App\Models\ModuleTransfer;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class CryptoService
{
    public function getAll(BudgetTracking $budget, array $filters = []): LengthAwarePaginator
    {
        $query = CryptoAsset::where('budget_tracking_id', $budget->id)
            ->withSum('lots', 'quantity')
            ->withSum('sales', 'quantity_sold')
            ->withSum('dividends', 'quantity_rewarded');

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
            // Cost Basis = (quantity × buy_price) + buying_fee
            $fee         = (float) ($lot->fee ?? 0);
            $costBasis   = (float) $lot->quantity * (float) $lot->buy_price + $fee;
            $currentValue = (float) $lot->quantity * $latestPrice;
            $pnl         = $currentValue - $costBasis;
            $pnlPct      = $costBasis > 0 ? round(($pnl / $costBasis) * 100, 2) : 0;

            return [
                'id'            => $lot->id,
                'quantity'      => $lot->quantity,
                'buy_price'     => $lot->buy_price,
                'fee'           => $lot->fee,
                'cost_basis'    => round($costBasis, 2),
                'purchase_date' => $lot->purchase_date?->toDateString(),
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

    public function recordSale(CryptoAsset $crypto, array $data): CryptoSale
    {
        return $crypto->sales()->create(array_merge($data, [
            'budget_tracking_id' => $crypto->budget_tracking_id,
        ]));
    }

    public function recordDividend(CryptoAsset $crypto, array $data): CryptoDividend
    {
        return $crypto->dividends()->create(array_merge($data, [
            'budget_tracking_id' => $crypto->budget_tracking_id,
        ]));
    }

    public function getDividends(CryptoAsset $crypto): array
    {
        return $crypto->dividends()
            ->orderBy('paid_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($d) => [
                'id'                => $d->id,
                'quantity_rewarded' => (float) $d->quantity_rewarded,
                'price_at_reward'   => (float) $d->price_at_reward,
                'est_value'         => round((float) $d->quantity_rewarded * (float) $d->price_at_reward, 2),
                'paid_at'           => $d->paid_at?->toDateString(),
                'notes'             => $d->notes,
                'created_at'        => $d->created_at,
            ])
            ->toArray();
    }

    public function getPortfolioSummary(BudgetTracking $budget): array
    {
        $btId   = $budget->id;
        $assets = CryptoAsset::with('lots')->where('budget_tracking_id', $btId)->get();

        // Per-asset sold qty map
        $soldQtyMap = CryptoSale::where('budget_tracking_id', $btId)
            ->selectRaw('crypto_asset_id, SUM(quantity_sold) as total_sold')
            ->groupBy('crypto_asset_id')
            ->pluck('total_sold', 'crypto_asset_id');

        // Per-asset reward qty map
        $rewardQtyMap = CryptoDividend::where('budget_tracking_id', $btId)
            ->selectRaw('crypto_asset_id, SUM(quantity_rewarded) as total_rewarded')
            ->groupBy('crypto_asset_id')
            ->pluck('total_rewarded', 'crypto_asset_id');

        // Total lots cost basis: (quantity × buy_price) + fee for each lot
        $totalLotsCostBasis = $assets->flatMap(fn($a) => $a->lots)
            ->sum(fn($l) => (float) $l->quantity * (float) $l->buy_price + (float) ($l->fee ?? 0));

        // Build per-symbol summaries
        $bySymbol = $assets->map(function ($asset) use ($soldQtyMap, $rewardQtyMap) {
            $lots      = $asset->lots;
            $lotsQty   = $lots->sum(fn($l) => (float) $l->quantity);
            $rewardQty = (float) ($rewardQtyMap[$asset->id] ?? 0);
            $soldQty   = (float) ($soldQtyMap[$asset->id] ?? 0);
            $netQty    = max(0, $lotsQty + $rewardQty - $soldQty);

            // Cost basis includes buying fees: Σ(qty × buy_price + fee)
            $totalCost   = $lots->sum(fn($l) => (float) $l->quantity * (float) $l->buy_price + (float) ($l->fee ?? 0));
            $latestPrice = (float) ($asset->latest_price ?? 0);
            $currentVal  = $netQty * $latestPrice;

            // Average buy price (per unit, excluding reward qty in denominator)
            $avgCost = $lotsQty > 0 ? ($totalCost / $lotsQty) : 0;

            // P&L: current value of all holdings minus cost basis of purchased lots
            // Reward qty contributes to current value at zero cost → free profit
            $unrealizedPnL = $currentVal - $totalCost;
            $unrealizedPct = $totalCost > 0 ? (($unrealizedPnL / $totalCost) * 100) : 0;

            return [
                'asset_id'           => $asset->id,
                'symbol'             => $asset->symbol,
                'coin_name'          => $asset->coin_name,
                'latest_price'       => round($latestPrice, 8),
                'net_qty'            => round($netQty, 8),
                'lots_qty_bought'    => round($lotsQty, 8),
                'reward_qty'         => round($rewardQty, 8),
                'sold_qty'           => round($soldQty, 8),
                'total_cost'         => round($totalCost, 2),
                'avg_cost'           => round($avgCost, 8),
                'current_value'      => round($currentVal, 2),
                'unrealized_pnl'     => round($unrealizedPnL, 2),
                'unrealized_pnl_pct' => round($unrealizedPct, 2),
                '_current_val_raw'   => $currentVal,
            ];
        })->values();

        $totalCurrentValue = $bySymbol->sum('_current_val_raw');

        // Inject allocation %
        $bySymbol = $bySymbol->map(function ($sym) use ($totalCurrentValue) {
            $allocPct = $totalCurrentValue > 0
                ? round(($sym['_current_val_raw'] / $totalCurrentValue) * 100, 2)
                : 0;
            unset($sym['_current_val_raw']);
            return array_merge($sym, ['alloc_pct' => $allocPct]);
        })->values();

        // Overall P&L
        $totalProfitLoss = $totalCurrentValue - $totalLotsCostBasis;
        $totalProfitPct  = $totalLotsCostBasis > 0 ? (($totalProfitLoss / $totalLotsCostBasis) * 100) : 0;

        // Transfer accounting
        $totalTransferred = (float) ModuleTransfer::where('budget_tracking_id', $btId)
            ->where('module', 'crypto')->sum('amount');

        $transferredOut = (float) ModuleTransfer::where('budget_tracking_id', $btId)
            ->where('transfer_from', 'crypto')
            ->sum('total');

        // Net proceeds from sales: (qty × sell_price) - sell_fee
        $totalSaleProceeds = (float) CryptoSale::where('budget_tracking_id', $btId)->sum('proceeds');

        // Reward qty adds to holdings (not cash) — no cash to add to available balance
        $totalRewardQty = (float) CryptoDividend::where('budget_tracking_id', $btId)->sum('quantity_rewarded');

        // available = in - out - lots_cost_basis + net_sale_proceeds
        // (reward qty increases holdings; when sold, proceeds are added above)
        $availableBalance = $totalTransferred - $transferredOut - $totalLotsCostBasis + $totalSaleProceeds;

        return [
            'total_cost_basis'             => round($totalLotsCostBasis, 2),
            'total_current_value'          => round($totalCurrentValue, 2),
            'total_profit_loss'            => round($totalProfitLoss, 2),
            'total_profit_loss_percentage' => round($totalProfitPct, 2),
            'total_transferred'            => round($totalTransferred, 2),
            'total_sale_proceeds'          => round($totalSaleProceeds, 2),
            'total_reward_qty'             => round($totalRewardQty, 8),
            'available_balance'            => round($availableBalance, 2),
            'count'                        => $assets->count(),
            'by_symbol'                    => $bySymbol,
        ];
    }
}
