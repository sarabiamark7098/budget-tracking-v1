<?php

namespace App\Http\Controllers\API\V1\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreStockRequest;
use App\Http\Requests\Stock\UpdateStockRequest;
use App\Http\Resources\Stock\StockResource;
use App\Models\ModuleTransfer;
use App\Models\Stock;
use App\Models\StockDividend;
use App\Models\StockLot;
use App\Models\StockSale;
use App\Services\StockService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private StockService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'per_page']);
        $stocks = $this->service->getAll($this->budget($request), $filters);
        return $this->respondSuccess(StockResource::collection($stocks)->response()->getData(true));
    }

    public function store(StoreStockRequest $request): JsonResponse
    {
        $stock = $this->service->create($this->budget($request), auth()->user(), $request->validated());
        return $this->respondCreated(new StockResource($stock), 'Stock created successfully');
    }

    public function show(Request $request, Stock $stock): JsonResponse
    {
        abort_if($stock->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        return $this->respondSuccess(new StockResource($stock));
    }

    public function update(UpdateStockRequest $request, Stock $stock): JsonResponse
    {
        abort_if($stock->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $stock = $this->service->update($stock, $request->validated());
        return $this->respondSuccess(new StockResource($stock), 'Stock updated successfully');
    }

    public function destroy(Request $request, Stock $stock): JsonResponse
    {
        abort_if($stock->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $this->service->delete($stock);
        return $this->respondSuccess(null, 'Stock deleted successfully');
    }

    public function portfolio(Request $request): JsonResponse
    {
        $summary = $this->service->getPortfolioSummary($this->budget($request));
        return $this->respondSuccess($summary, 'Stock portfolio summary retrieved');
    }

    public function getLots(Request $request, Stock $stock): JsonResponse
    {
        abort_if($stock->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $lots = $this->service->getLots($stock);
        return $this->respondSuccess(['lots' => $lots]);
    }

    public function storeLot(Request $request, Stock $stock): JsonResponse
    {
        abort_if($stock->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $data = $request->validate([
            'shares'        => ['required', 'numeric', 'min:0.0001'],
            'buy_price'     => ['required', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
        ]);

        $data['purchase_date'] = $data['purchase_date'] ?? now()->toDateString();

        // Available balance check (all-direction transfer accounting + sales + dividends)
        $btId           = $this->budget($request)->id;
        $transferred    = (float) ModuleTransfer::where('budget_tracking_id', $btId)
            ->where('module', 'stock')->sum('amount');
        $transferredOut = (float) ModuleTransfer::where('budget_tracking_id', $btId)
            ->where('transfer_from', 'stock')->sum('total');
        $deployed       = (float) (StockLot::whereHas('stock', fn($q) => $q->where('budget_tracking_id', $btId))
            ->selectRaw('SUM(shares * buy_price) as total')->value('total') ?? 0);
        $saleProceeds   = (float) StockSale::where('budget_tracking_id', $btId)->sum('proceeds');
        $dividends      = (float) StockDividend::where('budget_tracking_id', $btId)->sum('amount');

        $available = $transferred - $transferredOut - $deployed + $saleProceeds + $dividends;
        $required  = round((float) $data['shares'] * (float) $data['buy_price'], 2);

        if ($required > $available) {
            return $this->respondError(
                'Insufficient stock balance. Available: ₱' . number_format($available, 2) .
                ', Required: ₱' . number_format($required, 2),
                422
            );
        }

        $lot = $this->service->addLot($stock, $data);

        // Set latest_price if not set yet
        if (is_null($stock->latest_price)) {
            $stock->update(['latest_price' => $data['buy_price']]);
        }

        return $this->respondCreated($this->service->getLots($stock->fresh()), 'Lot added successfully');
    }

    public function updateLatestPrice(Request $request, Stock $stock): JsonResponse
    {
        abort_if($stock->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $data = $request->validate([
            'latest_price' => ['required', 'numeric', 'min:0'],
        ]);

        $stock = $this->service->updateLatestPrice($stock, (float) $data['latest_price']);
        return $this->respondSuccess(new StockResource($stock), 'Latest price updated successfully');
    }

    // ── Sell shares ──────────────────────────────────────────────────────────

    public function sell(Request $request, Stock $stock): JsonResponse
    {
        abort_if($stock->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $data = $request->validate([
            'shares_sold' => ['required', 'numeric', 'min:0.0001'],
            'sell_price'  => ['required', 'numeric', 'min:0'],
            'sold_at'     => ['nullable', 'date'],
        ]);

        $data['sold_at'] = $data['sold_at'] ?? now()->toDateString();

        // Validate: cannot sell more than net available shares for this stock
        $btId       = $this->budget($request)->id;
        $totalLots  = (float) ($stock->lots()->sum('shares') ?? 0);
        $totalSold  = (float) (StockSale::where('stock_id', $stock->id)->sum('shares_sold') ?? 0);
        $netShares  = max(0, $totalLots - $totalSold);

        if ((float) $data['shares_sold'] > $netShares) {
            return $this->respondError(
                'Insufficient shares. Available to sell: ' . number_format($netShares, 4) .
                ', Requested: ' . number_format((float) $data['shares_sold'], 4),
                422
            );
        }

        $data['proceeds'] = round((float) $data['shares_sold'] * (float) $data['sell_price'], 2);

        $sale = $this->service->recordSale($stock, $data);

        $summary = $this->service->getPortfolioSummary($this->budget($request));
        return $this->respondCreated([
            'sale'      => $sale,
            'portfolio' => $summary,
        ], 'Sale recorded. Proceeds added to available balance.');
    }

    // ── Dividend ─────────────────────────────────────────────────────────────

    public function storeDividend(Request $request, Stock $stock): JsonResponse
    {
        abort_if($stock->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $data = $request->validate([
            'amount'  => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['nullable', 'date'],
            'notes'   => ['nullable', 'string', 'max:255'],
        ]);

        $data['paid_at'] = $data['paid_at'] ?? now()->toDateString();

        $dividend = $this->service->recordDividend($stock, $data);

        $summary = $this->service->getPortfolioSummary($this->budget($request));
        return $this->respondCreated([
            'dividend'  => $dividend,
            'portfolio' => $summary,
        ], 'Dividend recorded. Amount added to available balance.');
    }

    public function getDividends(Request $request, Stock $stock): JsonResponse
    {
        abort_if($stock->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $dividends = $this->service->getDividends($stock);
        return $this->respondSuccess(['dividends' => $dividends]);
    }
}
