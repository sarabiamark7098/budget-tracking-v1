<?php

namespace App\Http\Controllers\API\V1\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crypto\StoreCryptoRequest;
use App\Http\Requests\Crypto\UpdateCryptoRequest;
use App\Http\Resources\Crypto\CryptoResource;
use App\Models\CryptoAsset;
use App\Services\CryptoService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CryptoController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private CryptoService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'per_page']);
        $assets = $this->service->getAll(auth()->user(), $filters);
        return $this->respondSuccess(CryptoResource::collection($assets)->response()->getData(true));
    }

    public function store(StoreCryptoRequest $request): JsonResponse
    {
        $crypto = $this->service->create(auth()->user(), $request->validated());
        return $this->respondCreated(new CryptoResource($crypto), 'Crypto asset created successfully');
    }

    public function show(CryptoAsset $crypto): JsonResponse
    {
        abort_if($crypto->user_id !== auth()->id(), 403, 'Unauthorized');
        return $this->respondSuccess(new CryptoResource($crypto));
    }

    public function update(UpdateCryptoRequest $request, CryptoAsset $crypto): JsonResponse
    {
        abort_if($crypto->user_id !== auth()->id(), 403, 'Unauthorized');
        $crypto = $this->service->update($crypto, $request->validated());
        return $this->respondSuccess(new CryptoResource($crypto), 'Crypto asset updated successfully');
    }

    public function destroy(CryptoAsset $crypto): JsonResponse
    {
        abort_if($crypto->user_id !== auth()->id(), 403, 'Unauthorized');
        $this->service->delete($crypto);
        return $this->respondSuccess(null, 'Crypto asset deleted successfully');
    }

    public function portfolio(): JsonResponse
    {
        $summary = $this->service->getPortfolioSummary(auth()->user());
        return $this->respondSuccess($summary, 'Crypto portfolio summary retrieved');
    }
}
