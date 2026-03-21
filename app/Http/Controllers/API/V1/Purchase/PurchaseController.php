<?php

namespace App\Http\Controllers\API\V1\Purchase;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Http\Requests\Purchase\UpdatePurchaseRequest;
use App\Http\Resources\Purchase\PurchaseResource;
use App\Models\Purchase;
use App\Services\PurchaseService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private PurchaseService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category_id', 'is_installment', 'search', 'per_page']);
        $purchases = $this->service->getAll($this->budget($request), $filters);
        return $this->respondSuccess(PurchaseResource::collection($purchases)->response()->getData(true));
    }

    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $purchase = $this->service->create($this->budget($request), auth()->user(), $request->validated());
        $purchase->load('category');
        return $this->respondCreated(new PurchaseResource($purchase), 'Purchase created successfully');
    }

    public function show(Request $request, Purchase $purchase): JsonResponse
    {
        abort_if($purchase->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $purchase->load(['category', 'files']);
        return $this->respondSuccess(new PurchaseResource($purchase));
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase): JsonResponse
    {
        abort_if($purchase->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $purchase = $this->service->update($purchase, $request->validated());
        return $this->respondSuccess(new PurchaseResource($purchase), 'Purchase updated successfully');
    }

    public function destroy(Request $request, Purchase $purchase): JsonResponse
    {
        abort_if($purchase->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $this->service->delete($purchase);
        return $this->respondSuccess(null, 'Purchase deleted successfully');
    }

    public function payInstallment(Request $request, Purchase $purchase): JsonResponse
    {
        abort_if($purchase->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        if (!$purchase->is_installment) {
            return $this->respondError('This purchase is not on installment', 422);
        }

        if ($purchase->remaining_installments <= 0) {
            return $this->respondError('All installments have been paid', 422);
        }

        $purchase = $this->service->payInstallment($purchase);
        return $this->respondSuccess(new PurchaseResource($purchase), 'Installment payment recorded');
    }
}
