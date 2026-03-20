<?php

namespace App\Http\Controllers\API\V1\BudgetTracking;

use App\Http\Controllers\Controller;
use App\Http\Requests\BudgetTracking\StoreBudgetTrackingAllocationRequest;
use App\Http\Resources\BudgetTracking\BudgetTrackingAllocationResource;
use App\Models\BudgetTrackingAllocation;
use App\Services\BudgetTrackingService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class BudgetTrackingAllocationController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private BudgetTrackingService $service) {}

    /**
     * GET /api/v1/budget-tracking/allocations
     * List all budget allocations. Visible to all members.
     */
    public function index(): JsonResponse
    {
        $budget = $this->resolveOrFail();
        $allocations = $this->service->getAllocations($budget);
        return $this->respondSuccess(BudgetTrackingAllocationResource::collection($allocations));
    }

    /**
     * POST /api/v1/budget-tracking/allocations
     * Add a budget allocation. Owner only.
     */
    public function store(StoreBudgetTrackingAllocationRequest $request): JsonResponse
    {
        $budget = $this->resolveOrFail();
        $allocation = $this->service->addAllocation($budget, auth()->user(), $request->validated());
        return $this->respondCreated(new BudgetTrackingAllocationResource($allocation), 'Allocation added successfully.');
    }

    /**
     * PUT /api/v1/budget-tracking/allocations/{allocation}
     * Update an allocation. Owner only.
     */
    public function update(StoreBudgetTrackingAllocationRequest $request, BudgetTrackingAllocation $allocation): JsonResponse
    {
        $budget = $this->resolveOrFail();
        abort_if($allocation->budget_tracking_id !== $budget->id, 404, 'Allocation not found.');

        $allocation = $this->service->updateAllocation($budget, auth()->user(), $allocation, $request->validated());
        return $this->respondSuccess(new BudgetTrackingAllocationResource($allocation), 'Allocation updated successfully.');
    }

    /**
     * DELETE /api/v1/budget-tracking/allocations/{allocation}
     * Delete an allocation. Owner only.
     */
    public function destroy(BudgetTrackingAllocation $allocation): JsonResponse
    {
        $budget = $this->resolveOrFail();
        abort_if($allocation->budget_tracking_id !== $budget->id, 404, 'Allocation not found.');

        $this->service->deleteAllocation($budget, auth()->user(), $allocation);
        return $this->respondSuccess(null, 'Allocation deleted successfully.');
    }

    // ─── Private Helper ───────────────────────────────────────────────────────────

    private function resolveOrFail(): \App\Models\BudgetTracking
    {
        $budget = $this->service->getForUser(auth()->user());
        abort_if(! $budget, 404, 'Budget tracking not found.');
        return $budget;
    }
}
