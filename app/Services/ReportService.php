<?php

namespace App\Services;

use App\Models\CryptoAsset;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Investment;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    public function generateIncomeExpenseReport(User $user, array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? now()->startOfYear()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->endOfYear()->toDateString();

        $incomes = Income::with('category')
            ->where('user_id', $user->id)
            ->whereBetween('received_at', [$dateFrom, $dateTo])
            ->orderBy('received_at', 'desc')
            ->get();

        $expenses = Expense::with('category')
            ->where('user_id', $user->id)
            ->whereBetween('spent_at', [$dateFrom, $dateTo])
            ->orderBy('spent_at', 'desc')
            ->get();

        $totalIncome = $incomes->sum('amount');
        $totalExpense = $expenses->sum('amount');

        $incomeByCategory = $incomes->groupBy('category.name')->map(fn($g) => round($g->sum('amount'), 2));
        $expenseByCategory = $expenses->groupBy('category.name')->map(fn($g) => round($g->sum('amount'), 2));

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'total_income' => round($totalIncome, 2),
            'total_expense' => round($totalExpense, 2),
            'net' => round($totalIncome - $totalExpense, 2),
            'income_by_category' => $incomeByCategory,
            'expense_by_category' => $expenseByCategory,
            'incomes' => $incomes,
            'expenses' => $expenses,
        ];
    }

    public function generateNetWorthReport(User $user): array
    {
        $totalInvestments = Investment::where('user_id', $user->id)->sum('current_value');
        $totalStocks = Stock::where('user_id', $user->id)->get()->sum(fn($s) => $s->current_value);
        $totalCrypto = CryptoAsset::where('user_id', $user->id)->get()->sum(fn($a) => $a->current_value);
        $totalDebt = Debt::where('user_id', $user->id)->where('status', '!=', 'paid')->sum('remaining_balance');

        $totalAssets = $totalInvestments + $totalStocks + $totalCrypto;
        $netWorth = $totalAssets - $totalDebt;

        return [
            'total_assets' => round($totalAssets, 2),
            'total_investments' => round($totalInvestments, 2),
            'total_stocks' => round($totalStocks, 2),
            'total_crypto' => round($totalCrypto, 2),
            'total_liabilities' => round($totalDebt, 2),
            'net_worth' => round($netWorth, 2),
        ];
    }

    public function exportToCsv(array $data, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            if (!empty($data)) {
                fputcsv($handle, array_keys((array) $data[0]));
                foreach ($data as $row) {
                    fputcsv($handle, (array) $row);
                }
            }

            fclose($handle);
        }, $filename . '.csv', [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ]);
    }
}
