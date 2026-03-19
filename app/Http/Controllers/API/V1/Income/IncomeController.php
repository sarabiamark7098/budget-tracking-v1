<?php

namespace App\Http\Controllers\API\V1\Income;

use App\Http\Controllers\Controller;
use App\Http\Requests\Income\StoreIncomeRequest;
use App\Http\Requests\Income\UpdateIncomeRequest;
use App\Http\Resources\Income\IncomeResource;
use App\Models\Income;
use App\Services\IncomeService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private IncomeService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category_id', 'date_from', 'date_to', 'search', 'per_page']);
        $incomes = $this->service->getAll(auth()->user(), $filters);
        return $this->respondSuccess(IncomeResource::collection($incomes)->response()->getData(true));
    }

    public function store(StoreIncomeRequest $request): JsonResponse
    {
        $income = $this->service->create(auth()->user(), $request->validated());
        $income->load('category');
        return $this->respondCreated(new IncomeResource($income), 'Income created successfully');
    }

    public function show(Income $income): JsonResponse
    {
        abort_if($income->user_id !== auth()->id(), 403, 'Unauthorized');
        $income->load('category');
        return $this->respondSuccess(new IncomeResource($income));
    }

    public function update(UpdateIncomeRequest $request, Income $income): JsonResponse
    {
        abort_if($income->user_id !== auth()->id(), 403, 'Unauthorized');
        $income = $this->service->update($income, $request->validated());
        return $this->respondSuccess(new IncomeResource($income), 'Income updated successfully');
    }

    public function destroy(Income $income): JsonResponse
    {
        abort_if($income->user_id !== auth()->id(), 403, 'Unauthorized');
        $this->service->delete($income);
        return $this->respondSuccess(null, 'Income deleted successfully');
    }

    public function monthly(Request $request): JsonResponse
    {
        $year = $request->get('year', now()->year);
        $data = $this->service->getMonthlySummary(auth()->user(), (int) $year);
        return $this->respondSuccess($data, 'Monthly summary retrieved');
    }
}
