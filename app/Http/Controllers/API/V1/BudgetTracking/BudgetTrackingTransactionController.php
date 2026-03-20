<?php

namespace App\Http\Controllers\API\V1\BudgetTracking;

use App\Http\Controllers\Controller;
use App\Http\Requests\BudgetTracking\StoreBudgetTrackingTransactionRequest;
use App\Http\Resources\BudgetTracking\BudgetTrackingTransactionResource;
use App\Models\BudgetTrackingTransaction;
use App\Services\BudgetTrackingService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetTrackingTransactionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private BudgetTrackingService $service) {}

    /**
     * GET /api/v1/budget-tracking/transactions
     * List all transactions for the user's budget tracking.
     */
    public function index(Request $request): JsonResponse
    {
        $budget = $this->resolveOrFail();
        $filters = $request->only(['type', 'user_id', 'date_from', 'date_to', 'per_page']);
        $paginated = $this->service->getTransactions($budget, $filters);

        return $this->respondSuccess(
            BudgetTrackingTransactionResource::collection($paginated)->response()->getData(true)
        );
    }

    /**
     * POST /api/v1/budget-tracking/transactions
     * Add a transaction. Any member can add.
     */
    public function store(StoreBudgetTrackingTransactionRequest $request): JsonResponse
    {
        $budget = $this->resolveOrFail();
        $tx = $this->service->addTransaction($budget, auth()->user(), $request->validated());
        return $this->respondCreated(new BudgetTrackingTransactionResource($tx), 'Transaction added successfully.');
    }

    /**
     * PUT /api/v1/budget-tracking/transactions/{transaction}
     * Update a transaction. Member can only update their own; owner can update any.
     */
    public function update(StoreBudgetTrackingTransactionRequest $request, BudgetTrackingTransaction $transaction): JsonResponse
    {
        $budget = $this->resolveOrFail();
        abort_if($transaction->budget_tracking_id !== $budget->id, 404, 'Transaction not found.');

        $tx = $this->service->updateTransaction($budget, auth()->user(), $transaction, $request->validated());
        return $this->respondSuccess(new BudgetTrackingTransactionResource($tx), 'Transaction updated successfully.');
    }

    /**
     * DELETE /api/v1/budget-tracking/transactions/{transaction}
     * Delete a transaction. Member can only delete their own; owner can delete any.
     */
    public function destroy(BudgetTrackingTransaction $transaction): JsonResponse
    {
        $budget = $this->resolveOrFail();
        abort_if($transaction->budget_tracking_id !== $budget->id, 404, 'Transaction not found.');

        $this->service->deleteTransaction($budget, auth()->user(), $transaction);
        return $this->respondSuccess(null, 'Transaction deleted successfully.');
    }

    // ─── Private Helper ───────────────────────────────────────────────────────────

    private function resolveOrFail(): \App\Models\BudgetTracking
    {
        $budget = $this->service->getForUser(auth()->user());
        abort_if(! $budget, 404, 'Budget tracking not found.');
        return $budget;
    }
}
