<?php

namespace App\Http\Controllers\API\V1\Insurance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StoreInsurancePaymentRequest;
use App\Http\Resources\Insurance\InsurancePaymentResource;
use App\Models\InsurancePayment;
use App\Services\InsuranceService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsurancePaymentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private InsuranceService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['insurance_plan_id', 'per_page']);
        $payments = $this->service->getPayments($this->budget($request), $filters);
        return $this->respondSuccess(InsurancePaymentResource::collection($payments)->response()->getData(true));
    }

    public function store(StoreInsurancePaymentRequest $request): JsonResponse
    {
        $payment = $this->service->recordPayment($this->budget($request), auth()->user(), $request->validated());
        return $this->respondCreated(new InsurancePaymentResource($payment), 'Insurance payment recorded successfully');
    }

    public function destroy(Request $request, InsurancePayment $insurancePayment): JsonResponse
    {
        abort_if($insurancePayment->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $insurancePayment->delete();
        return $this->respondSuccess(null, 'Insurance payment deleted successfully');
    }
}
