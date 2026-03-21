<?php

namespace App\Http\Controllers\API\V1\Expense;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Http\Resources\Expense\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ExpenseService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['budget_id', 'category_id', 'date_from', 'date_to', 'search', 'per_page']);
        $expenses = $this->service->getAll($this->budget($request), $filters);
        return $this->respondSuccess(ExpenseResource::collection($expenses)->response()->getData(true));
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $expense      = $this->service->create($this->budget($request), auth()->user(), $request->validated());
        $expense->load(['category', 'budget']);
        $budgetImpact = $this->service->getBudgetImpact($expense);

        $data = (new ExpenseResource($expense))->toArray($request);
        $data['budget_impact'] = $budgetImpact;

        return $this->respondCreated($data, 'Expense created successfully');
    }

    public function show(Request $request, Expense $expense): JsonResponse
    {
        abort_if($expense->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $expense->load(['category', 'files', 'budget']);
        $budgetImpact = $this->service->getBudgetImpact($expense);

        $data = (new ExpenseResource($expense))->toArray($request);
        $data['budget_impact'] = $budgetImpact;

        return $this->respondSuccess($data);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse
    {
        abort_if($expense->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $expense      = $this->service->update($expense, $request->validated());
        $budgetImpact = $this->service->getBudgetImpact($expense);

        $data = (new ExpenseResource($expense))->toArray($request);
        $data['budget_impact'] = $budgetImpact;

        return $this->respondSuccess($data, 'Expense updated successfully');
    }

    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        abort_if($expense->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $this->service->delete($expense);
        return $this->respondSuccess(null, 'Expense deleted successfully');
    }

    public function monthly(Request $request): JsonResponse
    {
        $year = $request->get('year', now()->year);
        $data = $this->service->getMonthlySummary($this->budget($request), (int) $year);
        return $this->respondSuccess($data, 'Monthly summary retrieved');
    }
}
