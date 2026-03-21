<?php

namespace App\Http\Controllers\API\V1\BudgetTracking;

use App\Http\Controllers\Controller;
use App\Http\Requests\BudgetTracking\JoinBudgetTrackingRequest;
use App\Http\Requests\BudgetTracking\StoreBudgetTrackingRequest;
use App\Http\Requests\BudgetTracking\UpdateBudgetTrackingRequest;
use App\Http\Resources\BudgetTracking\BudgetTrackingHistoryResource;
use App\Http\Resources\BudgetTracking\BudgetTrackingResource;
use App\Services\BudgetTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;

class BudgetTrackingController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private BudgetTrackingService $service) {}

    /**
     * GET /api/v1/budget-tracking
     * Return the authenticated user's single budget tracking (owned or shared).
     */
    public function show(): JsonResponse
    {
        $budget = $this->service->getForUser(auth()->user());

        if (! $budget) {
            return $this->respondError('You do not have a budget tracking yet.', 404);
        }

        return $this->respondSuccess(new BudgetTrackingResource($budget));
    }

    /**
     * POST /api/v1/budget-tracking
     * Create a new budget tracking. Each user may only have one.
     */
    public function store(StoreBudgetTrackingRequest $request): JsonResponse
    {
        $budget = $this->service->create(auth()->user(), $request->validated());
        return $this->respondCreated(new BudgetTrackingResource($budget), 'Budget tracking created successfully.');
    }

    /**
     * PUT /api/v1/budget-tracking
     * Update the budget tracking. Owner only.
     */
    public function update(UpdateBudgetTrackingRequest $request): JsonResponse
    {
        $budget = $this->resolveOrFail();
        $budget = $this->service->update($budget, auth()->user(), $request->validated());
        return $this->respondSuccess(new BudgetTrackingResource($budget), 'Budget tracking updated successfully.');
    }

    /**
     * DELETE /api/v1/budget-tracking
     * Owner deletes the entire budget tracking for everyone.
     * Members use POST /leave instead.
     */
    public function destroy(): JsonResponse
    {
        $budget = $this->resolveOrFail();
        $this->service->delete($budget, auth()->user());
        return $this->respondSuccess(null, 'Budget tracking deleted successfully.');
    }

    /**
     * GET /api/v1/budget-tracking/summary
     * Aggregated summary: totals, per-allocation usage, per-member contributions.
     */
    public function summary(): JsonResponse
    {
        $budget = $this->resolveOrFail();
        return $this->respondSuccess($this->service->getSummary($budget));
    }

    /**
     * POST /api/v1/budget-tracking/join
     * Join a budget tracking using its 8-character join code.
     */
    public function join(JoinBudgetTrackingRequest $request): JsonResponse
    {
        $budget = $this->service->joinByCode(auth()->user(), $request->input('join_code'));
        return $this->respondSuccess(new BudgetTrackingResource($budget), 'Joined budget tracking successfully.');
    }

    /**
     * POST /api/v1/budget-tracking/leave
     * Leave the current budget tracking. Only non-owner members can do this.
     */
    public function leave(): JsonResponse
    {
        $budget = $this->resolveOrFail();
        $this->service->leave($budget, auth()->user());
        return $this->respondSuccess(null, 'You have left the budget tracking.');
    }

    /**
     * POST /api/v1/budget-tracking/code/regenerate
     * Generate a new join code. Owner only. Old code is immediately invalidated.
     */
    public function regenerateCode(): JsonResponse
    {
        $budget = $this->resolveOrFail();
        $budget = $this->service->regenerateCode($budget, auth()->user());

        return $this->respondSuccess([
            'join_code' => $budget->join_code,
        ], 'Join code regenerated. Share the new code with members.');
    }

    /**
     * DELETE /api/v1/budget-tracking/members/{userId}
     * Remove a member from the budget tracking. Owner only.
     */
    public function removeMember(int $userId): JsonResponse
    {
        $budget = $this->resolveOrFail();
        $this->service->removeMember($budget, auth()->user(), $userId);
        return $this->respondSuccess(null, 'Member removed successfully.');
    }

    /**
     * GET /api/v1/budget-tracking/consolidated
     * Consolidated financial data from ALL members' accounts,
     * each record attributed by user name.
     */
    public function consolidated(): JsonResponse
    {
        $budget = $this->resolveOrFail();
        return $this->respondSuccess($this->service->getConsolidatedData($budget));
    }

    /**
     * GET /api/v1/budget-tracking/history
     * Paginated change history log for the budget tracking.
     */
    public function history(Request $request): JsonResponse
    {
        $budget = $this->resolveOrFail();

        $filters  = $request->only(['action', 'user_id', 'per_page']);
        $paginated = $this->service->getHistory($budget, $filters);

        return $this->respondSuccess(
            BudgetTrackingHistoryResource::collection($paginated)->response()->getData(true)
        );
    }

    // ─── Private Helper ───────────────────────────────────────────────────────────

    /**
     * Resolve the user's current budget tracking or abort with 404.
     * Also enforces membership — if the resolved tracking has no membership for this
     * user it means the data is inconsistent, so we treat it as not found.
     */
    private function resolveOrFail(): \App\Models\BudgetTracking
    {
        $budget = $this->service->getForUser(auth()->user());

        abort_if(! $budget, 404, 'Budget tracking not found.');

        return $budget;
    }
}
