<?php

namespace App\Http\Controllers\API\V1\Debt;

use App\Http\Controllers\Controller;
use App\Http\Requests\Debt\StoreDebtRequest;
use App\Http\Requests\Debt\UpdateDebtRequest;
use App\Http\Resources\Debt\DebtResource;
use App\Http\Resources\Payment\PaymentResource;
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
        $debts   = $this->service->getAll($this->budget($request), $filters);
        return $this->respondSuccess(DebtResource::collection($debts)->response()->getData(true));
    }

    public function store(StoreDebtRequest $request): JsonResponse
    {
        $debt = $this->service->create($this->budget($request), auth()->user(), $request->validated());
        return $this->respondCreated(new DebtResource($debt), 'Debt created successfully');
    }

    public function show(Request $request, Debt $debt): JsonResponse
    {
        $this->authorize('view', $debt);
        $debt->load('payments');
        return $this->respondSuccess(new DebtResource($debt));
    }

    public function update(UpdateDebtRequest $request, Debt $debt): JsonResponse
    {
        $this->authorize('update', $debt);
        $debt = $this->service->update($debt, $request->validated());
        return $this->respondSuccess(new DebtResource($debt), 'Debt updated successfully');
    }

    public function destroy(Request $request, Debt $debt): JsonResponse
    {
        $this->authorize('delete', $debt);
        $this->service->delete($debt);
        return $this->respondSuccess(null, 'Debt deleted successfully');
    }

    /**
     * GET /api/v1/debts/{debt}/balance
     * Returns the current balance due for a business debt (readonly preview for the Pay modal).
     */
    public function balance(Request $request, Debt $debt): JsonResponse
    {
        $this->authorize('pay', $debt);
        abort_if($debt->type !== 'business', 422, 'Balance calculation is only for business debts');

        return $this->respondSuccess($this->service->businessBalanceDue($debt));
    }

    /**
     * POST /api/v1/debts/{debt}/pay
     * Unified pay endpoint for all debt types/modes.
     *
     * Personal / shop_pay_later  → pays off full remaining balance
     * Personal / pay_installment → records next monthly installment
     * Business                   → amount required in request body; splits into interest + principal
     */
    public function pay(Request $request, Debt $debt): JsonResponse
    {
        $this->authorize('pay', $debt);

        if ($debt->status === 'paid') {
            return response()->json(['success' => false, 'message' => 'This debt is already fully paid.'], 422);
        }

        if ($debt->type === 'business') {
            $request->validate([
                'amount' => ['required', 'numeric', 'min:0.01'],
            ]);

            [$payment, $breakdown] = $this->service->payBusiness(
                $debt,
                auth()->user(),
                (float) $request->input('amount')
            );

            $debt->load('payments');

            return $this->respondSuccess([
                'payment'   => new PaymentResource($payment),
                'debt'      => new DebtResource($debt),
                'breakdown' => $breakdown,
            ], 'Payment recorded');
        }

        if ($debt->personal_mode === 'shop_pay_later') {
            $payment = $this->service->payShopPayLater($debt, auth()->user());
            $debt->load('payments');
            return $this->respondSuccess([
                'payment' => new PaymentResource($payment),
                'debt'    => new DebtResource($debt),
            ], 'Payment recorded');
        }

        if ($debt->personal_mode === 'pay_installment') {
            $payment = $this->service->payInstallment($debt, auth()->user());
            $debt->load('payments');
            return $this->respondSuccess([
                'payment' => new PaymentResource($payment),
                'debt'    => new DebtResource($debt),
            ], 'Installment recorded');
        }

        return response()->json(['success' => false, 'message' => 'Unknown debt mode.'], 422);
    }
}
