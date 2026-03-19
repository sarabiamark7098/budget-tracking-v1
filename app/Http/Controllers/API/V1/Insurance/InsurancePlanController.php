<?php

namespace App\Http\Controllers\API\V1\Insurance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StoreInsurancePlanRequest;
use App\Http\Requests\Insurance\UpdateInsurancePlanRequest;
use App\Http\Resources\Insurance\InsurancePlanResource;
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
        $filters = $request->only(['coverage_type', 'per_page']);
        $plans = $this->service->getAll(auth()->user(), $filters);
        return $this->respondSuccess(InsurancePlanResource::collection($plans)->response()->getData(true));
    }

    public function store(StoreInsurancePlanRequest $request): JsonResponse
    {
        $plan = $this->service->create(auth()->user(), $request->validated());
        return $this->respondCreated(new InsurancePlanResource($plan), 'Insurance plan created successfully');
    }

    public function show(InsurancePlan $insurancePlan): JsonResponse
    {
        abort_if($insurancePlan->user_id !== auth()->id(), 403, 'Unauthorized');
        $insurancePlan->load('insurancePayments');
        return $this->respondSuccess(new InsurancePlanResource($insurancePlan));
    }

    public function update(UpdateInsurancePlanRequest $request, InsurancePlan $insurancePlan): JsonResponse
    {
        abort_if($insurancePlan->user_id !== auth()->id(), 403, 'Unauthorized');
        $plan = $this->service->update($insurancePlan, $request->validated());
        return $this->respondSuccess(new InsurancePlanResource($plan), 'Insurance plan updated successfully');
    }

    public function destroy(InsurancePlan $insurancePlan): JsonResponse
    {
        abort_if($insurancePlan->user_id !== auth()->id(), 403, 'Unauthorized');
        $this->service->delete($insurancePlan);
        return $this->respondSuccess(null, 'Insurance plan deleted successfully');
    }
}
