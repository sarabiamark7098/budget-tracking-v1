<?php

namespace App\Http\Controllers\API\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private DashboardService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to']);
        $summary = $this->service->getSummary(auth()->user(), $filters);
        return $this->respondSuccess($summary, 'Dashboard summary retrieved successfully');
    }
}
