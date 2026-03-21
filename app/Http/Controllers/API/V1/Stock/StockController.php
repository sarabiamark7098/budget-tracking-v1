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
}
