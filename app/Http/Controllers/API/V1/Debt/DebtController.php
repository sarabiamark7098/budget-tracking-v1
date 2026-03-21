<?php

namespace App\Http\Controllers\API\V1\Debt;

use App\Http\Controllers\Controller;
use App\Http\Requests\Debt\StoreDebtRequest;
use App\Http\Requests\Debt\UpdateDebtRequest;
use App\Http\Resources\Debt\DebtResource;
use App\Models\Debt;
use App\Services\DebtService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private DebtService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'status', 'per_page']);
        $debts = $this->service->getAll($this->budget($request), $filters);
        return $this->respondSuccess(DebtResource::collection($debts)->response()->getData(true));
    }

    public function store(StoreDebtRequest $request): JsonResponse
    {
        $debt = $this->service->create($this->budget($request), auth()->user(), $request->validated());
        return $this->respondCreated(new DebtResource($debt), 'Debt created successfully');
    }

    public function show(Request $request, Debt $debt): JsonResponse
    {
        abort_if($debt->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $debt->load('payments');
        return $this->respondSuccess(new DebtResource($debt));
    }

    public function update(UpdateDebtRequest $request, Debt $debt): JsonResponse
    {
        abort_if($debt->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $debt = $this->service->update($debt, $request->validated());
        return $this->respondSuccess(new DebtResource($debt), 'Debt updated successfully');
    }

    public function destroy(Request $request, Debt $debt): JsonResponse
    {
        abort_if($debt->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $this->service->delete($debt);
        return $this->respondSuccess(null, 'Debt deleted successfully');
    }

    /**
     * GET /api/v1/debts/{debt}/amortization
     * Returns a full monthly amortization schedule for the debt.
     * Optional query params: duration_months, fixed_payment
     */
    public function amortization(Request $request, Debt $debt): JsonResponse
    {
        abort_if($debt->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $durationMonths = (int) ($request->query('duration_months', 12));
        $fixedPayment   = $request->query('fixed_payment') ? (float) $request->query('fixed_payment') : null;

        $schedule = $this->service->amortizationSchedule(
            (float) $debt->amount,
            (float) $debt->interest_rate,
            $durationMonths,
            $fixedPayment
        );

        return $this->respondSuccess([
            'debt'         => new DebtResource($debt),
            'amortization' => $schedule,
        ]);
    }

    /**
     * GET /api/v1/debts/{debt}/accrual
     * Returns daily interest accrual calculation for a business debt.
     * Query params: days_elapsed, total_paid
     */
    public function accrual(Request $request, Debt $debt): JsonResponse
    {
        abort_if($debt->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $daysElapsed = (int) ($request->query('days_elapsed', 0));
        $totalPaid   = (float) ($request->query('total_paid', $debt->payments()->sum('amount')));

        $accrual = $this->service->dailyInterestAccrual(
            (float) $debt->amount,
            (float) $debt->interest_rate,
            $daysElapsed,
            $totalPaid
        );

        return $this->respondSuccess([
            'debt'    => new DebtResource($debt),
            'accrual' => $accrual,
        ]);
    }
}
