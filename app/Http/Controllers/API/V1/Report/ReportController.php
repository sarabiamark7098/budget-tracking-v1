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
        $filters = $request->only(['date_from', 'date_to', 'type']);
        $type = $request->get('type', 'income_expense');

        if ($type === 'net_worth') {
            $data = [$this->service->generateNetWorthReport($this->budget($request))];
            $filename = 'net_worth_report_' . now()->format('Y_m_d');
        } else {
            $report = $this->service->generateIncomeExpenseReport($this->budget($request), $filters);
            $data = array_merge(
                collect($report['incomes'])->map(fn($i) => [
                    'type' => 'income',
                    'title' => $i->title,
                    'amount' => $i->amount,
                    'date' => $i->received_at,
                    'category' => $i->category?->name ?? 'N/A',
                ])->toArray(),
                collect($report['expenses'])->map(fn($e) => [
                    'type' => 'expense',
                    'title' => $e->title,
                    'amount' => $e->amount,
                    'date' => $e->spent_at,
                    'category' => $e->category?->name ?? 'N/A',
                ])->toArray()
            );
            $filename = 'income_expense_report_' . now()->format('Y_m_d');
        }

        return $this->service->exportToCsv($data, $filename);
    }

    public function exportPdf(): JsonResponse
    {
        return $this->respondError('PDF export requires a PDF library (e.g. dompdf). Install and configure separately.', 501);
    }
}
