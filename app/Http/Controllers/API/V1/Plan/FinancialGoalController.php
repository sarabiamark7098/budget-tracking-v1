<?php

namespace App\Http\Controllers\API\V1\Plan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StoreFinancialGoalRequest;
use App\Http\Requests\Plan\UpdateFinancialGoalRequest;
use App\Http\Resources\Plan\FinancialGoalResource;
use App\Models\FinancialGoal;
use App\Services\FinancialGoalService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialGoalController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private FinancialGoalService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['financial_plan_id', 'status', 'priority', 'per_page']);
        $goals = $this->service->getAll($this->budget($request), $filters);
        return $this->respondSuccess(FinancialGoalResource::collection($goals)->response()->getData(true));
    }

    public function store(StoreFinancialGoalRequest $request): JsonResponse
    {
        $goal = $this->service->create($this->budget($request), auth()->user(), $request->validated());
        return $this->respondCreated(new FinancialGoalResource($goal), 'Financial goal created successfully');
    }

    public function show(Request $request, FinancialGoal $financialGoal): JsonResponse
    {
        abort_if($financialGoal->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        return $this->respondSuccess(new FinancialGoalResource($financialGoal));
    }

    public function update(UpdateFinancialGoalRequest $request, FinancialGoal $financialGoal): JsonResponse
    {
        abort_if($financialGoal->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $goal = $this->service->update($financialGoal, $request->validated());
        return $this->respondSuccess(new FinancialGoalResource($goal), 'Financial goal updated successfully');
    }

    public function destroy(Request $request, FinancialGoal $financialGoal): JsonResponse
    {
        abort_if($financialGoal->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $this->service->delete($financialGoal);
        return $this->respondSuccess(null, 'Financial goal deleted successfully');
    }

    public function updateProgress(Request $request, FinancialGoal $financialGoal): JsonResponse
    {
        abort_if($financialGoal->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $request->validate([
            'amount' => ['required', 'numeric'],
        ]);

        $goal = $this->service->updateProgress($financialGoal, (float) $request->amount);
        return $this->respondSuccess(new FinancialGoalResource($goal), 'Progress updated successfully');
    }
}
