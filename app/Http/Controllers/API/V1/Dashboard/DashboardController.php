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
        $summary = $this->service->getSummary($this->budget($request), $filters);
        return $this->respondSuccess($summary, 'Dashboard summary retrieved successfully');
    }

    /**
     * Paginated, unified transaction list — combines incomes, expenses, and debt payments.
     * Used by the "Show More" button on the dashboard transactions widget.
     *
     * Query params:
     *   page      (int, default 1)
     *   per_page  (int, default 10)
     */
    public function transactions(Request $request): JsonResponse
    {
        $filters = $request->only(['page', 'per_page']);
        $data    = $this->service->getTransactions($this->budget($request), $filters);
        return $this->respondSuccess($data, 'Transactions retrieved successfully');
    }
}
