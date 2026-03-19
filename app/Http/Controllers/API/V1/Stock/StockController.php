<?php

namespace App\Http\Controllers\API\V1\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreStockRequest;
use App\Http\Requests\Stock\UpdateStockRequest;
use App\Http\Resources\Stock\StockResource;
use App\Models\Stock;
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
        $stocks = $this->service->getAll(auth()->user(), $filters);
        return $this->respondSuccess(StockResource::collection($stocks)->response()->getData(true));
    }

    public function store(StoreStockRequest $request): JsonResponse
    {
        $stock = $this->service->create(auth()->user(), $request->validated());
        return $this->respondCreated(new StockResource($stock), 'Stock created successfully');
    }

    public function show(Stock $stock): JsonResponse
    {
        abort_if($stock->user_id !== auth()->id(), 403, 'Unauthorized');
        return $this->respondSuccess(new StockResource($stock));
    }

    public function update(UpdateStockRequest $request, Stock $stock): JsonResponse
    {
        abort_if($stock->user_id !== auth()->id(), 403, 'Unauthorized');
        $stock = $this->service->update($stock, $request->validated());
        return $this->respondSuccess(new StockResource($stock), 'Stock updated successfully');
    }

    public function destroy(Stock $stock): JsonResponse
    {
        abort_if($stock->user_id !== auth()->id(), 403, 'Unauthorized');
        $this->service->delete($stock);
        return $this->respondSuccess(null, 'Stock deleted successfully');
    }

    public function portfolio(): JsonResponse
    {
        $summary = $this->service->getPortfolioSummary(auth()->user());
        return $this->respondSuccess($summary, 'Stock portfolio summary retrieved');
    }
}
