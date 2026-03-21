<?php

namespace App\Http\Controllers\API\V1\Report;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ReportService $service) {}

    public function incomeExpense(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to']);
        $report = $this->service->generateIncomeExpenseReport($this->budget($request), $filters);
        return $this->respondSuccess($report, 'Income/Expense report generated');
    }

    public function netWorth(Request $request): JsonResponse
    {
        $report = $this->service->generateNetWorthReport($this->budget($request));
        return $this->respondSuccess($report, 'Net worth report generated');
    }

    public function exportCsv(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to']);
        return $this->service->exportFullCsv($this->budget($request), $filters);
    }

    public function exportPdf(): JsonResponse
    {
        return $this->respondError('PDF export requires a PDF library (e.g. dompdf). Install and configure separately.', 501);
    }
}
