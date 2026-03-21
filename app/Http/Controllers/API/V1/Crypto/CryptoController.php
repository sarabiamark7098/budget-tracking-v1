<?php

namespace App\Http\Controllers\API\V1\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crypto\StoreCryptoRequest;
use App\Http\Requests\Crypto\UpdateCryptoRequest;
use App\Http\Resources\Crypto\CryptoResource;
use App\Models\CryptoAsset;
use App\Models\CryptoLot;
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

    public function storeLot(Request $request, CryptoAsset $crypto): JsonResponse
    {
        abort_if($crypto->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $data = $request->validate([
            'quantity'      => ['required', 'numeric', 'min:0.00000001'],
            'buy_price'     => ['required', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
        ]);

        $data['purchase_date'] = $data['purchase_date'] ?? now()->toDateString();

        // Check available balance
        $btId           = $this->budget($request)->id;
        $transferred    = (float) ModuleTransfer::where('budget_tracking_id', $btId)->where('module', 'crypto')->sum('amount');
        $transferredOut = (float) ModuleTransfer::where('budget_tracking_id', $btId)->where('transfer_from', 'crypto')->where('module', 'income')->sum('total');
        $deployed       = (float) CryptoLot::whereHas('cryptoAsset', fn($q) => $q->where('budget_tracking_id', $btId))
            ->selectRaw('SUM(quantity * buy_price) as total')->value('total');
        $available = $transferred - $transferredOut - $deployed;
        $required  = round((float) $data['quantity'] * (float) $data['buy_price'], 2);

        if ($required > $available) {
            return $this->respondError(
                'Insufficient crypto balance. Available: ₱' . number_format($available, 2) .
                ', Required: ₱' . number_format($required, 2),
                422
            );
        }

        $this->service->addLot($crypto, $data);

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
}
