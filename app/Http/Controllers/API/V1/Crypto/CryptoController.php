<?php

namespace App\Http\Controllers\API\V1\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crypto\StoreCryptoRequest;
use App\Http\Requests\Crypto\UpdateCryptoRequest;
use App\Http\Resources\Crypto\CryptoResource;
use App\Models\CryptoAsset;
use App\Models\CryptoDividend;
use App\Models\CryptoLot;
use App\Models\CryptoSale;
use App\Models\ModuleTransfer;
use App\Services\CryptoService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CryptoController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private CryptoService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'per_page']);
        $assets = $this->service->getAll($this->budget($request), $filters);
        return $this->respondSuccess(CryptoResource::collection($assets)->response()->getData(true));
    }

    public function store(StoreCryptoRequest $request): JsonResponse
    {
        $crypto = $this->service->create($this->budget($request), auth()->user(), $request->validated());
        return $this->respondCreated(new CryptoResource($crypto), 'Crypto asset created successfully');
    }

    public function show(Request $request, CryptoAsset $crypto): JsonResponse
    {
        abort_if($crypto->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        return $this->respondSuccess(new CryptoResource($crypto));
    }

    public function update(UpdateCryptoRequest $request, CryptoAsset $crypto): JsonResponse
    {
        abort_if($crypto->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $crypto = $this->service->update($crypto, $request->validated());
        return $this->respondSuccess(new CryptoResource($crypto), 'Crypto asset updated successfully');
    }

    public function destroy(Request $request, CryptoAsset $crypto): JsonResponse
    {
        abort_if($crypto->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $this->service->delete($crypto);
        return $this->respondSuccess(null, 'Crypto asset deleted successfully');
    }

    public function portfolio(Request $request): JsonResponse
    {
        $summary = $this->service->getPortfolioSummary($this->budget($request));
        return $this->respondSuccess($summary, 'Crypto portfolio summary retrieved');
    }

    public function getLots(Request $request, CryptoAsset $crypto): JsonResponse
    {
        abort_if($crypto->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $lots = $this->service->getLots($crypto);
        return $this->respondSuccess(['lots' => $lots]);
    }

    // ── Buy (Add Lot) ─────────────────────────────────────────────────────────
    // Cost Basis = (Purchase Price × Amount) + Buying Fees

    public function storeLot(Request $request, CryptoAsset $crypto): JsonResponse
    {
        abort_if($crypto->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $data = $request->validate([
            'quantity'      => ['required', 'numeric', 'min:0.00000001'],
            'buy_price'     => ['required', 'numeric', 'min:0'],
            'fee'           => ['nullable', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
        ]);

        $data['purchase_date'] = $data['purchase_date'] ?? now()->toDateString();
        $data['fee']           = (float) ($data['fee'] ?? 0);

        // Cost Basis = (quantity × buy_price) + fee
        $costBasis = round((float) $data['quantity'] * (float) $data['buy_price'] + $data['fee'], 2);

        // Available balance check
        $btId           = $this->budget($request)->id;
        $transferred    = (float) ModuleTransfer::where('budget_tracking_id', $btId)->where('module', 'crypto')->sum('amount');
        $transferredOut = (float) ModuleTransfer::where('budget_tracking_id', $btId)->where('transfer_from', 'crypto')->sum('total');
        $deployed       = (float) (CryptoLot::whereHas('cryptoAsset', fn($q) => $q->where('budget_tracking_id', $btId))
            ->selectRaw('SUM(quantity * buy_price + fee) as total')->value('total') ?? 0);
        $saleProceeds   = (float) CryptoSale::where('budget_tracking_id', $btId)->sum('proceeds');

        $available = $transferred - $transferredOut - $deployed + $saleProceeds;

        if ($costBasis > $available) {
            return $this->respondError(
                'Insufficient crypto balance. Available: ₱' . number_format($available, 2) .
                ', Required (incl. fee): ₱' . number_format($costBasis, 2),
                422
            );
        }

        $lot = $this->service->addLot($crypto, $data);

        if (is_null($crypto->latest_price)) {
            $crypto->update(['latest_price' => $data['buy_price']]);
        }

        return $this->respondCreated($this->service->getLots($crypto->fresh()), 'Lot added successfully');
    }

    public function updateLatestPrice(Request $request, CryptoAsset $crypto): JsonResponse
    {
        abort_if($crypto->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $data = $request->validate([
            'latest_price' => ['required', 'numeric', 'min:0'],
        ]);

        $crypto = $this->service->updateLatestPrice($crypto, (float) $data['latest_price']);
        return $this->respondSuccess(new CryptoResource($crypto), 'Latest price updated successfully');
    }

    // ── Sell ──────────────────────────────────────────────────────────────────
    // Net Proceeds = (Selling Price × Amount) - Selling Fees
    // Profit/Loss  = Net Proceeds - Cost Basis

    public function sell(Request $request, CryptoAsset $crypto): JsonResponse
    {
        abort_if($crypto->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $data = $request->validate([
            'quantity_sold' => ['required', 'numeric', 'min:0.00000001'],
            'sell_price'    => ['required', 'numeric', 'min:0'],
            'sell_fee'      => ['nullable', 'numeric', 'min:0'],
            'sold_at'       => ['nullable', 'date'],
        ]);

        $data['sold_at']  = $data['sold_at']  ?? now()->toDateString();
        $data['sell_fee'] = (float) ($data['sell_fee'] ?? 0);

        // Validate net available quantity (lots + rewards - sold)
        $totalLots   = (float) ($crypto->lots()->sum('quantity') ?? 0);
        $totalReward = (float) (CryptoDividend::where('crypto_asset_id', $crypto->id)->sum('quantity_rewarded') ?? 0);
        $totalSold   = (float) (CryptoSale::where('crypto_asset_id', $crypto->id)->sum('quantity_sold') ?? 0);
        $netQty      = max(0, $totalLots + $totalReward - $totalSold);

        if ((float) $data['quantity_sold'] > $netQty) {
            return $this->respondError(
                'Insufficient quantity. Available to sell: ' . number_format($netQty, 8) .
                ', Requested: ' . number_format((float) $data['quantity_sold'], 8),
                422
            );
        }

        // Net Proceeds = (quantity_sold × sell_price) - sell_fee
        $data['proceeds'] = round(
            (float) $data['quantity_sold'] * (float) $data['sell_price'] - $data['sell_fee'],
            2
        );

        if ($data['proceeds'] < 0) {
            $data['proceeds'] = 0;
        }

        $sale    = $this->service->recordSale($crypto, $data);
        $summary = $this->service->getPortfolioSummary($this->budget($request));

        return $this->respondCreated([
            'sale'      => $sale,
            'portfolio' => $summary,
        ], 'Sale recorded. Net proceeds added to available balance.');
    }

    // ── Reward History ────────────────────────────────────────────────────────

    public function getDividends(Request $request, CryptoAsset $crypto): JsonResponse
    {
        abort_if($crypto->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $rewards = $this->service->getDividends($crypto);
        return $this->respondSuccess(['dividends' => $rewards]);
    }

    // ── Staking / Reward (quantity-based) ─────────────────────────────────────

    public function storeDividend(Request $request, CryptoAsset $crypto): JsonResponse
    {
        abort_if($crypto->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $data = $request->validate([
            'quantity_rewarded' => ['required', 'numeric', 'min:0.00000001'],
            'price_at_reward'   => ['nullable', 'numeric', 'min:0'],
            'paid_at'           => ['nullable', 'date'],
            'notes'             => ['nullable', 'string', 'max:255'],
        ]);

        $data['paid_at']         = $data['paid_at']         ?? now()->toDateString();
        $data['price_at_reward'] = (float) ($data['price_at_reward'] ?? $crypto->latest_price ?? 0);

        $dividend = $this->service->recordDividend($crypto, $data);
        $summary  = $this->service->getPortfolioSummary($this->budget($request));

        return $this->respondCreated([
            'dividend'  => $dividend,
            'portfolio' => $summary,
        ], 'Reward quantity recorded. Holdings updated.');
    }
}
