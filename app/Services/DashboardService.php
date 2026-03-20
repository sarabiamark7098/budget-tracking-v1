<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Investment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getSummary(User $user, array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->endOfMonth()->toDateString();

        $totalIncome = Income::where('user_id', $user->id)
            ->whereBetween('received_at', [$dateFrom, $dateTo])
            ->sum('amount');

        $totalExpenses = Expense::where('user_id', $user->id)
            ->whereBetween('spent_at', [$dateFrom, $dateTo])
            ->sum('amount');

        $balance = $totalIncome - $totalExpenses;

        $totalDebt = \App\Models\Debt::where('user_id', $user->id)
            ->where('status', '!=', 'paid')
            ->sum('remaining_balance');

        $totalInvestments = \App\Models\Investment::where('user_id', $user->id)
            ->sum('current_value');

        $monthlyData        = $this->getMonthlyData($user);
        $categoryBreakdown  = $this->getCategoryBreakdown($user, $dateFrom, $dateTo);
        $recentTransactions = $this->getRecentTransactions($user);
        $healthScore        = $this->calculateFinancialHealthScore(
            $totalIncome, $totalExpenses, $totalDebt, $totalInvestments
        );

        return [
            'total_income'           => round($totalIncome, 2),
            'total_expenses'         => round($totalExpenses, 2),
            'balance'                => round($balance, 2),
            'total_savings'          => max(0, round($balance, 2)),
            'total_debt'             => round($totalDebt, 2),
            'total_investments'      => round($totalInvestments, 2),
            'financial_health'       => $healthScore,
            'monthly_data'           => $monthlyData,
            'category_breakdown'     => $categoryBreakdown,
            'recent_transactions'    => $recentTransactions,
            'period'                 => ['from' => $dateFrom, 'to' => $dateTo],
        ];
    }

    private function getMonthlyData(User $user): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->format('Y-m');
            $monthNum = $date->format('m');
            $yearNum = $date->format('Y');

            $income = Income::where('user_id', $user->id)
                ->whereYear('received_at', $yearNum)
                ->whereMonth('received_at', $monthNum)
                ->sum('amount');

            $expense = Expense::where('user_id', $user->id)
                ->whereYear('spent_at', $yearNum)
                ->whereMonth('spent_at', $monthNum)
                ->sum('amount');

            $months[] = [
                'month' => $month,
                'label' => $date->format('M Y'),
                'income' => round($income, 2),
                'expense' => round($expense, 2),
                'net' => round($income - $expense, 2),
            ];
        }

        return $months;
    }

    private function getCategoryBreakdown(User $user, string $dateFrom, string $dateTo): array
    {
        return DB::table('expenses')
            ->join('categories', 'expenses.category_id', '=', 'categories.id')
            ->where('expenses.user_id', $user->id)
            ->whereNull('expenses.deleted_at')
            ->whereBetween('expenses.spent_at', [$dateFrom, $dateTo])
            ->select(
                'categories.id',
                'categories.name',
                'categories.color',
                'categories.icon',
                DB::raw('SUM(expenses.amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('categories.id', 'categories.name', 'categories.color', 'categories.icon')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    /**
     * Financial Health Score (0–100).
     *
     * Scoring dimensions (25 pts each):
     *
     * 1. Savings Rate = (income − expenses) / income × 100
     *    ≥ 20% → 25 pts | 10–19% → 15 pts | > 0% → 8 pts | ≤ 0% → 0 pts
     *
     * 2. Debt-to-Income Ratio (DTI) = total_debt / income × 100
     *    < 20% → 25 pts | 20–40% → 15 pts | 40–80% → 8 pts | > 80% → 0 pts
     *
     * 3. Investment Rate = investments / income × 100
     *    ≥ 20% → 25 pts | 10–19% → 15 pts | > 0% → 8 pts | 0% → 0 pts
     *
     * 4. Expense-to-Income Ratio = expenses / income × 100
     *    ≤ 50% → 25 pts | 51–70% → 15 pts | 71–90% → 8 pts | > 90% → 0 pts
     *
     * Grade: 80–100 → Excellent | 60–79 → Good | 40–59 → Fair | < 40 → Needs Improvement
     */
    private function calculateFinancialHealthScore(
        float $income,
        float $expenses,
        float $debt,
        float $investments
    ): array {
        if ($income <= 0) {
            return [
                'score' => 0, 'grade' => 'N/A', 'grade_label' => 'No income data',
                'savings_rate_pct' => 0, 'dti_pct' => 0,
                'investment_rate_pct' => 0, 'expense_ratio_pct' => 0,
                'breakdown' => [],
            ];
        }

        $savingsRate    = (($income - $expenses) / $income) * 100;
        $dti            = ($debt / $income) * 100;
        $investmentRate = ($investments / $income) * 100;
        $expenseRatio   = ($expenses / $income) * 100;

        $savingsScore = match (true) {
            $savingsRate >= 20 => 25,
            $savingsRate >= 10 => 15,
            $savingsRate > 0   => 8,
            default            => 0,
        };

        $dtiScore = match (true) {
            $dti < 20  => 25,
            $dti < 40  => 15,
            $dti < 80  => 8,
            default    => 0,
        };

        $investmentScore = match (true) {
            $investmentRate >= 20 => 25,
            $investmentRate >= 10 => 15,
            $investmentRate > 0   => 8,
            default               => 0,
        };

        $expenseScore = match (true) {
            $expenseRatio <= 50 => 25,
            $expenseRatio <= 70 => 15,
            $expenseRatio <= 90 => 8,
            default             => 0,
        };

        $score = $savingsScore + $dtiScore + $investmentScore + $expenseScore;

        $grade = match (true) {
            $score >= 80 => 'A',
            $score >= 60 => 'B',
            $score >= 40 => 'C',
            default      => 'D',
        };

        $gradeLabel = match ($grade) {
            'A' => 'Excellent',
            'B' => 'Good',
            'C' => 'Fair',
            default => 'Needs Improvement',
        };

        return [
            'score'                => $score,
            'grade'                => $grade,
            'grade_label'          => $gradeLabel,
            'savings_rate_pct'     => round($savingsRate, 2),
            'dti_pct'              => round($dti, 2),
            'investment_rate_pct'  => round($investmentRate, 2),
            'expense_ratio_pct'    => round($expenseRatio, 2),
            'breakdown'            => [
                ['dimension' => 'Savings Rate',          'value_pct' => round($savingsRate, 2),    'score' => $savingsScore,    'max' => 25],
                ['dimension' => 'Debt-to-Income (DTI)',  'value_pct' => round($dti, 2),            'score' => $dtiScore,        'max' => 25],
                ['dimension' => 'Investment Rate',       'value_pct' => round($investmentRate, 2), 'score' => $investmentScore, 'max' => 25],
                ['dimension' => 'Expense Ratio',         'value_pct' => round($expenseRatio, 2),   'score' => $expenseScore,    'max' => 25],
            ],
        ];
    }

    private function getRecentTransactions(User $user): array
    {
        $incomes = Income::with('category')
            ->where('user_id', $user->id)
            ->orderBy('received_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'type' => 'income',
                'title' => $i->title,
                'amount' => $i->amount,
                'date' => $i->received_at,
                'category' => $i->category?->name,
            ]);

        $expenses = Expense::with('category')
            ->where('user_id', $user->id)
            ->orderBy('spent_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'type' => 'expense',
                'title' => $e->title,
                'amount' => $e->amount,
                'date' => $e->spent_at,
                'category' => $e->category?->name,
            ]);

        return $incomes->concat($expenses)
            ->sortByDesc('date')
            ->take(10)
            ->values()
            ->toArray();
    }
}
