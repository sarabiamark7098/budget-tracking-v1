<?php

namespace App\Services;

use App\Models\BudgetTracking;
use App\Models\ModuleTransfer;
use App\Models\Stock;
use App\Models\StockDividend;
use App\Models\StockLot;
use App\Models\StockSale;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class StockService
{
    public function getAll(BudgetTracking $budget, array $filters = []): LengthAwarePaginator
    {
        $query = Stock::where('budget_tracking_id', $budget->id)
            ->withSum('lots', 'shares')
            ->withSum('sales', 'shares_sold');

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

    public function recordSale(Stock $stock, array $data): StockSale
    {
        return $stock->sales()->create(array_merge($data, [
            'budget_tracking_id' => $stock->budget_tracking_id,
        ]));
    }

    public function recordDividend(Stock $stock, array $data): StockDividend
    {
        return $stock->dividends()->create(array_merge($data, [
            'budget_tracking_id' => $stock->budget_tracking_id,
        ]));
    }

    public function getDividends(Stock $stock): array
    {
        return $stock->dividends()
            ->orderBy('paid_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($d) => [
                'id'     => $d->id,
                'amount' => (float) $d->amount,
                'paid_at' => $d->paid_at?->toDateString(),
                'notes'  => $d->notes,
                'created_at' => $d->created_at,
            ])
            ->toArray();
    }

    public function getPortfolioSummary(BudgetTracking $budget): array
    {
        $btId   = $budget->id;
        $stocks = Stock::with('lots')->where('budget_tracking_id', $btId)->get();

        // Per-stock sold shares map
        $soldSharesMap = StockSale::where('budget_tracking_id', $btId)
            ->selectRaw('stock_id, SUM(shares_sold) as total_sold')
            ->groupBy('stock_id')
            ->pluck('total_sold', 'stock_id');

        // Per-stock dividends map
        $dividendMap = StockDividend::where('budget_tracking_id', $btId)
            ->selectRaw('stock_id, SUM(amount) as total_div, COUNT(*) as div_count')
            ->groupBy('stock_id')
            ->get()
            ->keyBy('stock_id');

        // Total lots cost basis (all historical lots, never deleted)
        $totalLotsCostBasis = $stocks->flatMap(fn($s) => $s->lots)
            ->sum(fn($l) => (float) $l->shares * (float) $l->buy_price);

        // Build per-symbol summaries with net shares
        $bySymbol = $stocks->map(function ($stock) use ($soldSharesMap, $dividendMap) {
            $lots        = $stock->lots;
            $totalQty    = $lots->sum(fn($l) => (float) $l->shares);
            $soldQty     = (float) ($soldSharesMap[$stock->id] ?? 0);
            $netQty      = max(0, $totalQty - $soldQty);
            $totalCost   = $lots->sum(fn($l) => (float) $l->shares * (float) $l->buy_price);
            $latestPrice = (float) ($stock->latest_price ?? 0);
            $currentVal  = $netQty * $latestPrice;
            $weightedAvg = $totalQty > 0 ? ($totalCost / $totalQty) : 0;
            $netCost     = $netQty * $weightedAvg;
            $unrealizedPnL = $currentVal - $netCost;
            $unrealizedPct = $netCost > 0 ? (($unrealizedPnL / $netCost) * 100) : 0;

            $divRow      = $dividendMap[$stock->id] ?? null;
            $totalDiv    = (float) ($divRow?->total_div ?? 0);
            $divCount    = (int)   ($divRow?->div_count ?? 0);

            return [
                'stock_id'            => $stock->id,
                'symbol'              => $stock->symbol,
                'company_name'        => $stock->company_name,
                'latest_price'        => round($latestPrice, 4),
                'net_shares'          => round($netQty, 4),
                'total_shares_bought' => round($totalQty, 4),
                'total_cost_basis'    => round($totalCost, 2),
                'weighted_avg_cost'   => round($weightedAvg, 4),
                'current_value'       => round($currentVal, 2),
                'unrealized_pnl'      => round($unrealizedPnL, 2),
                'unrealized_pnl_pct'  => round($unrealizedPct, 2),
                'total_dividends'     => round($totalDiv, 2),
                'dividend_count'      => $divCount,
                // allocation set below
                '_current_val_raw'    => $currentVal,
            ];
        })->values();

        $totalCurrentValue = $bySymbol->sum('_current_val_raw');

        // Inject allocation percentage now that we have the total
        $bySymbol = $bySymbol->map(function ($sym) use ($totalCurrentValue) {
            $allocPct = $totalCurrentValue > 0
                ? round(($sym['_current_val_raw'] / $totalCurrentValue) * 100, 2)
                : 0;
            unset($sym['_current_val_raw']);
            return array_merge($sym, ['portfolio_allocation_pct' => $allocPct]);
        })->values();

        // HHI for diversification
        $hhi = $bySymbol->sum(fn($s) => pow($s['portfolio_allocation_pct'] / 100, 2));

        // Overall P&L (based on net shares, using weighted avg cost)
        $totalNetCost       = $bySymbol->sum(fn($s) => (float) $s['total_cost_basis'] * ((float) $s['net_shares'] / max(1, (float) $s['total_shares_bought'])));
        $totalProfitLoss    = $totalCurrentValue - $totalNetCost;
        $totalProfitPct     = $totalNetCost > 0 ? (($totalProfitLoss / $totalNetCost) * 100) : 0;

        // Transfer accounting
        $totalTransferred = (float) ModuleTransfer::where('budget_tracking_id', $btId)
            ->where('module', 'stock')->sum('amount');

        $transferredOut = (float) ModuleTransfer::where('budget_tracking_id', $btId)
            ->where('transfer_from', 'stock')
            ->sum('total');

        // Cash from sales & dividends (add back to available)
        $totalSaleProceeds = (float) StockSale::where('budget_tracking_id', $btId)->sum('proceeds');
        $totalDividends    = (float) StockDividend::where('budget_tracking_id', $btId)->sum('amount');

        // available = in - out - allLotsCostBasis + saleProceeds + dividends
        $availableBalance = $totalTransferred - $transferredOut - $totalLotsCostBasis
            + $totalSaleProceeds + $totalDividends;

        return [
            'total_cost_basis'             => round($totalLotsCostBasis, 2),
            'total_current_value'          => round($totalCurrentValue, 2),
            'total_profit_loss'            => round($totalProfitLoss, 2),
            'total_profit_loss_percentage' => round($totalProfitPct, 2),
            'total_transferred'            => round($totalTransferred, 2),
            'total_sale_proceeds'          => round($totalSaleProceeds, 2),
            'total_dividends'              => round($totalDividends, 2),
            'available_balance'            => round($availableBalance, 2),
            'concentration_index_hhi'      => round($hhi, 4),
            'diversification_level'        => $hhi < 0.15 ? 'high' : ($hhi < 0.25 ? 'moderate' : 'low'),
            'count'                        => $stocks->count(),
            'unique_symbols'               => $bySymbol->count(),
            'by_symbol'                    => $bySymbol,
        ];
    }
}
