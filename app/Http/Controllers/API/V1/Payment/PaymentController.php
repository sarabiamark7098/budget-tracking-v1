<?php

namespace App\Http\Controllers\API\V1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\Payment\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private PaymentService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['debt_id', 'per_page']);
        $payments = $this->service->getAll($this->budget($request), $filters);
        return $this->respondSuccess(PaymentResource::collection($payments)->response()->getData(true));
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $payment = $this->service->create($this->budget($request), auth()->user(), $request->validated());
        return $this->respondCreated(new PaymentResource($payment), 'Payment recorded successfully');
    }

    public function show(Request $request, Payment $payment): JsonResponse
    {
        abort_if($payment->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $payment->load('debt');
        return $this->respondSuccess(new PaymentResource($payment));
    }

    public function destroy(Request $request, Payment $payment): JsonResponse
    {
        abort_if($payment->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $this->service->delete($payment);
        return $this->respondSuccess(null, 'Payment deleted successfully');
    }
}
