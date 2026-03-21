<?php

namespace App\Http\Controllers\API\V1\Budget;

use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\StoreBudgetRequest;
use App\Http\Requests\Budget\UpdateBudgetRequest;
use App\Http\Resources\Budget\BudgetResource;
use App\Models\Budget;
use App\Services\BudgetService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private BudgetService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['per_page']);
        $budgets = $this->service->getAll($this->budget($request), $filters);
        return $this->respondSuccess(BudgetResource::collection($budgets)->response()->getData(true));
    }

    public function store(StoreBudgetRequest $request): JsonResponse
    {
        $budget = $this->service->create($this->budget($request), auth()->user(), $request->validated());
        $budget->load('category');
        return $this->respondCreated(new BudgetResource($budget), 'Budget created successfully');
    }

    public function show(Request $request, Budget $budget): JsonResponse
    {
        abort_if($budget->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $budget->load('category');
        return $this->respondSuccess(new BudgetResource($budget));
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): JsonResponse
    {
        abort_if($budget->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $budget = $this->service->update($budget, $request->validated());
        return $this->respondSuccess(new BudgetResource($budget), 'Budget updated successfully');
    }

    public function destroy(Request $request, Budget $budget): JsonResponse
    {
        abort_if($budget->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $this->service->delete($budget);
        return $this->respondSuccess(null, 'Budget deleted successfully');
    }

    public function summary(Request $request): JsonResponse
    {
        $summary = $this->service->getBudgetSummary($this->budget($request));
        return $this->respondSuccess($summary, 'Budget summary retrieved');
    }
}
