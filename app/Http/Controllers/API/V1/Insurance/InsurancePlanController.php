<?php

namespace App\Http\Controllers\API\V1\Insurance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StoreInsurancePlanRequest;
use App\Http\Requests\Insurance\UpdateInsurancePlanRequest;
use App\Http\Resources\Insurance\InsurancePlanResource;
use App\Http\Resources\Insurance\InsurancePaymentResource;
use App\Models\InsurancePlan;
use App\Services\InsuranceService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsurancePlanController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private InsuranceService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['per_page']);
        $plans = $this->service->getAll($this->budget($request), $filters);
        return $this->respondSuccess(InsurancePlanResource::collection($plans)->response()->getData(true));
    }

    public function store(StoreInsurancePlanRequest $request): JsonResponse
    {
        $plan = $this->service->create($this->budget($request), auth()->user(), $request->validated());
        return $this->respondCreated(new InsurancePlanResource($plan), 'Insurance plan created successfully');
    }

    public function show(Request $request, InsurancePlan $insurancePlan): JsonResponse
    {
        abort_if($insurancePlan->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $insurancePlan->load('insurancePayments');
        return $this->respondSuccess(new InsurancePlanResource($insurancePlan));
    }

    public function update(UpdateInsurancePlanRequest $request, InsurancePlan $insurancePlan): JsonResponse
    {
        abort_if($insurancePlan->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $plan = $this->service->update($insurancePlan, $request->validated());
        return $this->respondSuccess(new InsurancePlanResource($plan), 'Insurance plan updated successfully');
    }

    public function destroy(Request $request, InsurancePlan $insurancePlan): JsonResponse
    {
        abort_if($insurancePlan->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $this->service->delete($insurancePlan);
        return $this->respondSuccess(null, 'Insurance plan deleted successfully');
    }

    public function pay(Request $request, InsurancePlan $insurancePlan): JsonResponse
    {
        abort_if($insurancePlan->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $data = $request->validate([
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['nullable', 'date'],
            'note'         => ['nullable', 'string', 'max:255'],
        ]);

        $data['payment_date'] = $data['payment_date'] ?? now()->toDateString();

        $payment = $this->service->recordPayment(
            $this->budget($request),
            auth()->user(),
            array_merge($data, ['insurance_plan_id' => $insurancePlan->id])
        );

        return $this->respondCreated(new InsurancePaymentResource($payment), 'Payment recorded successfully');
    }

    public function getPayments(Request $request, InsurancePlan $insurancePlan): JsonResponse
    {
        abort_if($insurancePlan->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $payments = $insurancePlan->insurancePayments()
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        return $this->respondSuccess(
            InsurancePaymentResource::collection($payments)->response()->getData(true)
        );
    }
}
