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
        $debts = $this->service->getAll(auth()->user(), $filters);
        return $this->respondSuccess(DebtResource::collection($debts)->response()->getData(true));
    }

    public function store(StoreDebtRequest $request): JsonResponse
    {
        $debt = $this->service->create(auth()->user(), $request->validated());
        return $this->respondCreated(new DebtResource($debt), 'Debt created successfully');
    }

    public function show(Debt $debt): JsonResponse
    {
        abort_if($debt->user_id !== auth()->id(), 403, 'Unauthorized');
        $debt->load('payments');
        return $this->respondSuccess(new DebtResource($debt));
    }

    public function update(UpdateDebtRequest $request, Debt $debt): JsonResponse
    {
        abort_if($debt->user_id !== auth()->id(), 403, 'Unauthorized');
        $debt = $this->service->update($debt, $request->validated());
        return $this->respondSuccess(new DebtResource($debt), 'Debt updated successfully');
    }

    public function destroy(Debt $debt): JsonResponse
    {
        abort_if($debt->user_id !== auth()->id(), 403, 'Unauthorized');
        $this->service->delete($debt);
        return $this->respondSuccess(null, 'Debt deleted successfully');
    }
}
