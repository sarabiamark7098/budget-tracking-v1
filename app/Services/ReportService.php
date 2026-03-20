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

        $net         = $totalIncome - $totalExpense;
        $savingsRate = $totalIncome > 0 ? ($net / $totalIncome) * 100 : 0;
        $expenseRatio = $totalIncome > 0 ? ($totalExpense / $totalIncome) * 100 : 0;

        // Month-over-month trend within the period
        $monthlyTrend = $this->buildMonthlyTrend($user, $dateFrom, $dateTo);

        // Burn-rate: average daily expense in the period
        $periodDays = max(1, (int) (new \DateTime($dateFrom))->diff(new \DateTime($dateTo))->days + 1);
        $dailyBurnRate = $totalExpense / $periodDays;

        // Days of runway: how many days savings can cover at current burn rate
        $runway = $dailyBurnRate > 0 ? (int) ($net / $dailyBurnRate) : PHP_INT_MAX;

        return [
            'period'               => ['from' => $dateFrom, 'to' => $dateTo],
            'total_income'         => round($totalIncome, 2),
            'total_expense'        => round($totalExpense, 2),
            'net'                  => round($net, 2),
            'savings_rate_pct'     => round($savingsRate, 2),
            'expense_ratio_pct'    => round($expenseRatio, 2),
            'daily_burn_rate'      => round($dailyBurnRate, 2),
            'savings_runway_days'  => $runway >= PHP_INT_MAX ? null : $runway,
            'income_by_category'   => $incomeByCategory,
            'expense_by_category'  => $expenseByCategory,
            'monthly_trend'        => $monthlyTrend,
            'incomes'              => $incomes,
            'expenses'             => $expenses,
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

    /**
     * Build month-by-month income vs expense trend within the report period.
     * Each month returns: income, expense, net, savings_rate_pct, mom_net_change_pct
     */
    private function buildMonthlyTrend(User $user, string $dateFrom, string $dateTo): array
    {
        $start  = new \DateTime($dateFrom);
        $end    = new \DateTime($dateTo);
        $cursor = (clone $start)->modify('first day of this month');
        $trend  = [];
        $prevNet = null;

        while ($cursor <= $end) {
            $y = $cursor->format('Y');
            $m = $cursor->format('m');

            $income  = (float) Income::where('user_id', $user->id)
                ->whereYear('received_at', $y)->whereMonth('received_at', $m)->sum('amount');
            $expense = (float) Expense::where('user_id', $user->id)
                ->whereYear('spent_at', $y)->whereMonth('spent_at', $m)->sum('amount');

            $net         = $income - $expense;
            $savingsRate = $income > 0 ? ($net / $income) * 100 : 0;
            $momChange   = ($prevNet !== null && $prevNet != 0)
                ? (($net - $prevNet) / abs($prevNet)) * 100
                : null;

            $trend[] = [
                'month'           => $cursor->format('Y-m'),
                'label'           => $cursor->format('M Y'),
                'income'          => round($income, 2),
                'expense'         => round($expense, 2),
                'net'             => round($net, 2),
                'savings_rate_pct'=> round($savingsRate, 2),
                'mom_net_change_pct' => $momChange !== null ? round($momChange, 2) : null,
            ];

            $prevNet = $net;
            $cursor->modify('+1 month');
        }

        return $trend;
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
