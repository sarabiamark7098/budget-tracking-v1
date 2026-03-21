<?php

namespace App\Http\Controllers\API\V1\Investment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\StoreInvestmentRequest;
use App\Http\Requests\Investment\UpdateInvestmentRequest;
use App\Http\Resources\Investment\InvestmentResource;
use App\Models\Investment;
use App\Services\InvestmentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private InvestmentService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'per_page']);
        $investments = $this->service->getAll($this->budget($request), $filters);
        return $this->respondSuccess(InvestmentResource::collection($investments)->response()->getData(true));
    }

    public function store(StoreInvestmentRequest $request): JsonResponse
    {
        $investment = $this->service->create($this->budget($request), auth()->user(), $request->validated());
        $investment->load('category');
        return $this->respondCreated(new InvestmentResource($investment), 'Investment created successfully');
    }

    public function show(Request $request, Investment $investment): JsonResponse
    {
        abort_if($investment->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $investment->load('category');
        return $this->respondSuccess(new InvestmentResource($investment));
    }

    public function update(UpdateInvestmentRequest $request, Investment $investment): JsonResponse
    {
        abort_if($investment->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $investment = $this->service->update($investment, $request->validated());
        return $this->respondSuccess(new InvestmentResource($investment), 'Investment updated successfully');
    }

    public function destroy(Request $request, Investment $investment): JsonResponse
    {
        abort_if($investment->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $this->service->delete($investment);
        return $this->respondSuccess(null, 'Investment deleted successfully');
    }

    public function portfolio(Request $request): JsonResponse
    {
        $summary = $this->service->getPortfolioSummary($this->budget($request));
        return $this->respondSuccess($summary, 'Portfolio summary retrieved');
    }
}
