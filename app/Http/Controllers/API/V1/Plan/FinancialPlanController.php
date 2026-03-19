<?php

namespace App\Http\Controllers\API\V1\Plan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StoreFinancialPlanRequest;
use App\Http\Requests\Plan\UpdateFinancialPlanRequest;
use App\Http\Resources\Plan\FinancialPlanResource;
use App\Models\FinancialPlan;
use App\Services\FinancialPlanService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialPlanController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private FinancialPlanService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'per_page']);
        $plans = $this->service->getAll(auth()->user(), $filters);
        return $this->respondSuccess(FinancialPlanResource::collection($plans)->response()->getData(true));
    }

    public function store(StoreFinancialPlanRequest $request): JsonResponse
    {
        $plan = $this->service->create(auth()->user(), $request->validated());
        return $this->respondCreated(new FinancialPlanResource($plan), 'Financial plan created successfully');
    }

    public function show(FinancialPlan $financialPlan): JsonResponse
    {
        abort_if($financialPlan->user_id !== auth()->id(), 403, 'Unauthorized');
        $financialPlan->load('financialGoals');
        return $this->respondSuccess(new FinancialPlanResource($financialPlan));
    }

    public function update(UpdateFinancialPlanRequest $request, FinancialPlan $financialPlan): JsonResponse
    {
        abort_if($financialPlan->user_id !== auth()->id(), 403, 'Unauthorized');
        $plan = $this->service->update($financialPlan, $request->validated());
        return $this->respondSuccess(new FinancialPlanResource($plan), 'Financial plan updated successfully');
    }

    public function destroy(FinancialPlan $financialPlan): JsonResponse
    {
        abort_if($financialPlan->user_id !== auth()->id(), 403, 'Unauthorized');
        $this->service->delete($financialPlan);
        return $this->respondSuccess(null, 'Financial plan deleted successfully');
    }
}
