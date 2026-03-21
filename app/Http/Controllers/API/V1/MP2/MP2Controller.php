<?php

namespace App\Http\Controllers\API\V1\MP2;

use App\Http\Controllers\Controller;
use App\Http\Requests\MP2\CalculateMP2Request;
use App\Http\Requests\MP2\StoreMP2Request;
use App\Http\Requests\MP2\UpdateMP2Request;
use App\Http\Resources\MP2\MP2PlanResource;
use App\Models\MP2Plan;
use App\Services\MP2Service;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MP2Controller extends Controller
{
    use ApiResponseTrait;

    public function __construct(private MP2Service $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['per_page']);
        $plans = $this->service->getAll($this->budget($request), $filters);
        return $this->respondSuccess(MP2PlanResource::collection($plans)->response()->getData(true));
    }

    public function store(StoreMP2Request $request): JsonResponse
    {
        $plan = $this->service->create($this->budget($request), auth()->user(), $request->validated());
        return $this->respondCreated(new MP2PlanResource($plan), 'MP2 plan created successfully');
    }

    public function update(UpdateMP2Request $request, MP2Plan $mp2Plan): JsonResponse
    {
        abort_if($mp2Plan->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $plan = $this->service->update($mp2Plan, $request->validated());
        return $this->respondSuccess(new MP2PlanResource($plan), 'MP2 plan updated successfully');
    }

    public function destroy(Request $request, MP2Plan $mp2Plan): JsonResponse
    {
        abort_if($mp2Plan->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $this->service->delete($mp2Plan);
        return $this->respondSuccess(null, 'MP2 plan deleted successfully');
    }

    public function calculate(CalculateMP2Request $request): JsonResponse
    {
        $result = $this->service->calculate($request->validated());
        return $this->respondSuccess($result, 'MP2 calculation completed');
    }
}
