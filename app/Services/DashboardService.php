<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetTracking;
use App\Models\BudgetTrackingTransaction;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Investment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    // ─── Main Summary ─────────────────────────────────────────────────────────────

    public function getSummary(BudgetTracking $budget, array $filters = []): array
    {
        $btId     = $budget->id;
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo   = $filters['date_to']   ?? now()->endOfMonth()->toDateString();

        // ── ALL-TIME overview totals (no date filter) ────────────────────────────
        $totalIncome = (float) Income::where('budget_tracking_id', $btId)->sum('amount');

        $totalExpenses = (float) Expense::where('budget_tracking_id', $btId)->sum('amount');

        $totalDebtPayments = (float) Payment::where('budget_tracking_id', $btId)->sum('amount');

        $balance = $totalIncome - $totalExpenses - $totalDebtPayments;

        $totalDebt = (float) Debt::where('budget_tracking_id', $btId)
            ->where('status', '!=', 'paid')
            ->sum('remaining_balance');

        $totalInvestments = (float) Investment::where('budget_tracking_id', $btId)
            ->sum('current_value');

        // ── PERIOD-filtered income ────────────────────────────────────────────────
        $periodIncome = (float) Income::where('budget_tracking_id', $btId)
            ->whereBetween('received_at', [$dateFrom, $dateTo])
            ->sum('amount');

        // ── Sub-sections ─────────────────────────────────────────────────────────
        $monthlyData        = $this->getMonthlyData($budget);
        $categoryBreakdown  = $this->getCategoryBreakdown($budget, $dateFrom, $dateTo);
        $recentTransactions = $this->buildTransactions($budget, 10);
        $healthScore        = $this->calculateFinancialHealthScore(
            $totalIncome, $totalExpenses, $totalDebt, $totalInvestments
        );
        $budgetMonitor      = $this->getBudgetMonitor($budget, $dateFrom, $dateTo, $periodIncome);
        $budgetList         = $this->getBudgetList($budget, $dateFrom, $dateTo);
        $debtList           = $this->getDebtList($budget);
        $monthReport        = $this->getMonthReport($budget);
        $yearReport         = $this->getYearReport($budget);

        return [
            'total_income'           => round($totalIncome, 2),
            'total_expenses'         => round($totalExpenses, 2),
            'total_debt_payments'    => round($totalDebtPayments, 2),
            'balance'                => round($balance, 2),
            'total_savings'          => max(0, round($balance, 2)),
            'total_debt'             => round($totalDebt, 2),
            'total_investments'      => round($totalInvestments, 2),
            'financial_health'       => $healthScore,
            'budget_monitor'         => $budgetMonitor,
            'budget_list'            => $budgetList,
            'recent_transactions'    => $recentTransactions,
            'expense_breakdown'      => $categoryBreakdown,
            'category_breakdown'     => $categoryBreakdown,
            'debt_list'              => $debtList,
            'month_report'           => $monthReport,
            'year_report'            => $yearReport,
            'monthly_data'           => $monthlyData,
            'period'                 => ['from' => $dateFrom, 'to' => $dateTo],
        ];
    }

    // ─── Budget List ──────────────────────────────────────────────────────────────

    private function getBudgetList(BudgetTracking $budget, string $dateFrom, string $dateTo): array
    {
        $budgets = Budget::with('category')
            ->where('budget_tracking_id', $budget->id)
            ->orderByDesc('start_date')
            ->get();

        return $budgets->map(function (Budget $b) {
            $spent     = $b->spent_amount;
            $allocated = (float) $b->amount;
            $usagePct  = $allocated > 0 ? round(($spent / $allocated) * 100, 2) : 0;
            $remaining = max(0, $allocated - $spent);

            return [
                'id'               => $b->id,
                'name'             => $b->name,
                'category'         => $b->category?->name,
                'category_color'   => $b->category?->color,
                'period'           => $b->period,
                'start_date'       => $b->start_date->toDateString(),
                'end_date'         => $b->end_date->toDateString(),
                'allocated_amount' => $allocated,
                'spent_amount'     => round($spent, 2),
                'remaining_amount' => round($remaining, 2),
                'usage_pct'        => $usagePct,
                'alert_threshold'  => $b->alert_threshold,
                'status'           => $this->budgetStatus($usagePct, $b->alert_threshold),
            ];
        })->values()->toArray();
    }

    // ─── Transactions ─────────────────────────────────────────────────────────────

    private function buildTransactions(BudgetTracking $budget, int $take = 10, int $offset = 0): array
    {
        $all   = $this->getAllTransactions($budget);
        $total = $all->count();
        $sliced = $all->slice($offset, $take)->values();

        return [
            'data'     => $sliced->toArray(),
            'total'    => $total,
            'per_page' => $take,
            'offset'   => $offset,
            'has_more' => ($offset + $take) < $total,
        ];
    }

    public function getTransactions(BudgetTracking $budget, array $filters = []): array
    {
        $perPage = (int) ($filters['per_page'] ?? 10);
        $page    = (int) ($filters['page']     ?? 1);
        $offset  = ($page - 1) * $perPage;

        $all      = $this->getAllTransactions($budget);
        $total    = $all->count();
        $items    = $all->slice($offset, $perPage)->values();
        $lastPage = (int) ceil($total / $perPage);

        return [
            'data'         => $items->toArray(),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => $lastPage,
            'has_more'     => $page < $lastPage,
        ];
    }

    private function getAllTransactions(BudgetTracking $budget): \Illuminate\Support\Collection
    {
        $btId = $budget->id;

        $incomes = Income::with('category')
            ->where('budget_tracking_id', $btId)
            ->orderBy('received_at', 'desc')
            ->get()
            ->map(fn($i) => [
                'id'          => $i->id,
                'type'        => 'income',
                'title'       => $i->title,
                'amount'      => (float) $i->amount,
                'date'        => $i->received_at,
                'category'    => $i->category?->name,
                'description' => $i->description,
                'user_name'   => $i->user?->name,
            ]);

        $expenses = Expense::with('category')
            ->where('budget_tracking_id', $btId)
            ->orderBy('spent_at', 'desc')
            ->get()
            ->map(fn($e) => [
                'id'          => $e->id,
                'type'        => 'expense',
                'title'       => $e->title,
                'amount'      => (float) $e->amount,
                'date'        => $e->spent_at,
                'category'    => $e->category?->name,
                'description' => $e->description,
                'user_name'   => $e->user?->name,
            ]);

        $payments = Payment::with('debt')
            ->where('budget_tracking_id', $btId)
            ->orderBy('payment_date', 'desc')
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'type'        => 'debt_payment',
                'title'       => 'Payment — ' . ($p->debt?->lender_name ?? 'Debt'),
                'amount'      => (float) $p->amount,
                'date'        => $p->payment_date,
                'category'    => 'Debt Payment',
                'description' => $p->note,
                'user_name'   => $p->user?->name,
            ]);

        return $incomes->concat($expenses)->concat($payments)
            ->sortByDesc('date')
            ->values();
    }

    // ─── Expense Breakdown ────────────────────────────────────────────────────────

    private function getCategoryBreakdown(BudgetTracking $budget, string $dateFrom, string $dateTo): array
    {
        return DB::table('expenses')
            ->join('categories', 'expenses.category_id', '=', 'categories.id')
            ->where('expenses.budget_tracking_id', $budget->id)
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

    // ─── Debt List ────────────────────────────────────────────────────────────────

    private function getDebtList(BudgetTracking $budget): array
    {
        return Debt::where('budget_tracking_id', $budget->id)
            ->where('status', '!=', 'paid')
            ->orderBy('due_date')
            ->get()
            ->map(fn(Debt $d) => [
                'id'                => $d->id,
                'lender_name'       => $d->lender_name,
                'type'              => $d->type,
                'business_name'     => $d->business_name,
                'original_amount'   => (float) $d->amount,
                'remaining_balance' => (float) $d->remaining_balance,
                'total_paid'        => round((float) $d->amount - (float) $d->remaining_balance, 2),
                'interest_rate'     => (float) $d->interest_rate,
                'due_date'          => $d->due_date?->toDateString(),
                'status'            => $d->status,
                'user_name'         => $d->user?->name,
            ])
            ->values()
            ->toArray();
    }

    // ─── Month Report ─────────────────────────────────────────────────────────────

    private function getMonthReport(BudgetTracking $budget): array
    {
        $btId  = $budget->id;
        $month = now()->month;
        $year  = now()->year;

        $income = (float) Income::where('budget_tracking_id', $btId)
            ->whereYear('received_at', $year)->whereMonth('received_at', $month)->sum('amount');

        $expenses = (float) Expense::where('budget_tracking_id', $btId)
            ->whereYear('spent_at', $year)->whereMonth('spent_at', $month)->sum('amount');

        $debtPayments = (float) Payment::where('budget_tracking_id', $btId)
            ->whereYear('payment_date', $year)->whereMonth('payment_date', $month)->sum('amount');

        $totalDebt        = (float) Debt::where('budget_tracking_id', $btId)->where('status', '!=', 'paid')->sum('remaining_balance');
        $totalInvestments = (float) Investment::where('budget_tracking_id', $btId)->sum('current_value');

        $balance     = $income - $expenses - $debtPayments;
        $savingsRate = $income > 0 ? round(($balance / $income) * 100, 2) : 0;

        $allTimeIncome = (float) Income::where('budget_tracking_id', $btId)->sum('amount');
        $monthsWithIncome = Income::where('budget_tracking_id', $btId)
            ->selectRaw('YEAR(received_at) as yr, MONTH(received_at) as mo')
            ->groupBy('yr', 'mo')
            ->get()
            ->count();

        $avgMonthlyIncome = $monthsWithIncome > 0
            ? round($allTimeIncome / $monthsWithIncome, 2)
            : 0.0;

        return [
            'period'              => now()->format('F Y'),
            'month'               => $month,
            'year'                => $year,
            'total_income'        => round($income, 2),
            'total_expenses'      => round($expenses, 2),
            'debt_payments'       => round($debtPayments, 2),
            'balance'             => round($balance, 2),
            'total_debt'          => round($totalDebt, 2),
            'total_investments'   => round($totalInvestments, 2),
            'balance_remaining'   => round(max(0, $balance), 2),
            'savings_rate_pct'    => $savingsRate,
            'socioeconomic_class' => $this->getSocioeconomicClass($avgMonthlyIncome, $monthsWithIncome),
        ];
    }

    // ─── Year Report ──────────────────────────────────────────────────────────────

    private function getYearReport(BudgetTracking $budget): array
    {
        $btId = $budget->id;
        $year = now()->year;

        $income       = (float) Income::where('budget_tracking_id', $btId)->whereYear('received_at', $year)->sum('amount');
        $expenses     = (float) Expense::where('budget_tracking_id', $btId)->whereYear('spent_at', $year)->sum('amount');
        $debtPayments = (float) Payment::where('budget_tracking_id', $btId)->whereYear('payment_date', $year)->sum('amount');
        $totalDebt    = (float) Debt::where('budget_tracking_id', $btId)->where('status', '!=', 'paid')->sum('remaining_balance');
        $totalInvest  = (float) Investment::where('budget_tracking_id', $btId)->sum('current_value');

        $balance     = $income - $expenses - $debtPayments;
        $savingsRate = $income > 0 ? round(($balance / $income) * 100, 2) : 0;

        return [
            'period'            => (string) $year,
            'year'              => $year,
            'total_income'      => round($income, 2),
            'total_expenses'    => round($expenses, 2),
            'debt_payments'     => round($debtPayments, 2),
            'balance'           => round($balance, 2),
            'total_debt'        => round($totalDebt, 2),
            'total_investments' => round($totalInvest, 2),
            'balance_remaining' => round(max(0, $balance), 2),
            'savings_rate_pct'  => $savingsRate,
        ];
    }

    // ─── Monthly Trend ────────────────────────────────────────────────────────────

    private function getMonthlyData(BudgetTracking $budget): array
    {
        $btId   = $budget->id;
        $months = [];

        for ($i = 11; $i >= 0; $i--) {
            $date     = now()->subMonths($i);
            $monthNum = $date->format('m');
            $yearNum  = $date->format('Y');

            $income  = Income::where('budget_tracking_id', $btId)->whereYear('received_at', $yearNum)->whereMonth('received_at', $monthNum)->sum('amount');
            $expense = Expense::where('budget_tracking_id', $btId)->whereYear('spent_at', $yearNum)->whereMonth('spent_at', $monthNum)->sum('amount');

            $months[] = [
                'month'   => $date->format('Y-m'),
                'label'   => $date->format('M Y'),
                'income'  => round($income, 2),
                'expense' => round($expense, 2),
                'net'     => round($income - $expense, 2),
            ];
        }

        return $months;
    }

    // ─── Financial Health Score ───────────────────────────────────────────────────

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
            $savingsRate >= 20 => 25, $savingsRate >= 10 => 15, $savingsRate > 0 => 8, default => 0,
        };
        $dtiScore = match (true) {
            $dti < 20 => 25, $dti < 40 => 15, $dti < 80 => 8, default => 0,
        };
        $investmentScore = match (true) {
            $investmentRate >= 20 => 25, $investmentRate >= 10 => 15, $investmentRate > 0 => 8, default => 0,
        };
        $expenseScore = match (true) {
            $expenseRatio <= 50 => 25, $expenseRatio <= 70 => 15, $expenseRatio <= 90 => 8, default => 0,
        };

        $score = $savingsScore + $dtiScore + $investmentScore + $expenseScore;
        $grade = match (true) { $score >= 80 => 'A', $score >= 60 => 'B', $score >= 40 => 'C', default => 'D' };
        $gradeLabel = match ($grade) { 'A' => 'Excellent', 'B' => 'Good', 'C' => 'Fair', default => 'Needs Improvement' };

        return [
            'score'               => $score,
            'grade'               => $grade,
            'grade_label'         => $gradeLabel,
            'savings_rate_pct'    => round($savingsRate, 2),
            'dti_pct'             => round($dti, 2),
            'investment_rate_pct' => round($investmentRate, 2),
            'expense_ratio_pct'   => round($expenseRatio, 2),
            'breakdown'           => [
                ['dimension' => 'Savings Rate',         'value_pct' => round($savingsRate, 2),    'score' => $savingsScore,    'max' => 25],
                ['dimension' => 'Debt-to-Income (DTI)', 'value_pct' => round($dti, 2),            'score' => $dtiScore,        'max' => 25],
                ['dimension' => 'Investment Rate',      'value_pct' => round($investmentRate, 2), 'score' => $investmentScore, 'max' => 25],
                ['dimension' => 'Expense Ratio',        'value_pct' => round($expenseRatio, 2),   'score' => $expenseScore,    'max' => 25],
            ],
        ];
    }

    // ─── Budget Monitor ───────────────────────────────────────────────────────────

    private function getBudgetMonitor(
        BudgetTracking $budget,
        string         $dateFrom,
        string         $dateTo,
        float          $periodIncome
    ): array {
        $btId    = $budget->id;
        $budgets = Budget::with('category')
            ->where('budget_tracking_id', $btId)
            ->where('start_date', '<=', $dateTo)
            ->where('end_date',   '>=', $dateFrom)
            ->get();

        $budgetRows = $budgets->map(function (Budget $b) {
            $spent     = $b->spent_amount;
            $allocated = (float) $b->amount;
            $usagePct  = $allocated > 0 ? round(($spent / $allocated) * 100, 2) : 0;
            $remaining = max(0, $allocated - $spent);

            return [
                'id'               => $b->id,
                'name'             => $b->name,
                'category'         => $b->category?->name,
                'period'           => $b->period,
                'start_date'       => $b->start_date->toDateString(),
                'end_date'         => $b->end_date->toDateString(),
                'allocated_amount' => $allocated,
                'spent_amount'     => round($spent, 2),
                'remaining_amount' => round($remaining, 2),
                'usage_pct'        => $usagePct,
                'alert_threshold'  => $b->alert_threshold,
                'status'           => $this->budgetStatus($usagePct, $b->alert_threshold),
            ];
        })->values()->toArray();

        $totalBudgeted = (float) $budgets->sum('amount');
        $totalSpent    = array_sum(array_column($budgetRows, 'spent_amount'));
        $overallUsage  = $totalBudgeted > 0 ? round(($totalSpent / $totalBudgeted) * 100, 2) : 0;
        $overallStatus = $this->worstStatus(array_column($budgetRows, 'status'));

        if ($periodIncome > 0 && $totalSpent > $periodIncome) {
            $overallStatus = 'over_income';
        }

        // Budget tracking allocations summary — no end date, scope from start_date to today
        $txFrom  = $budget->start_date?->toDateString() ?? '1970-01-01';
        $txTo    = now()->toDateString();

        $txQuery     = BudgetTrackingTransaction::where('budget_tracking_id', $btId)
            ->whereBetween('date', [$txFrom, $txTo]);

        $btIncome    = (float) (clone $txQuery)->where('type', 'income')->sum('amount');
        $btExpense   = (float) (clone $txQuery)->where('type', 'expense')->sum('amount');
        $btAllocated = (float) $budget->allocations->sum('allocated_amount');
        $btUsagePct  = $btAllocated > 0 ? round(($btExpense / $btAllocated) * 100, 2) : 0;

        $allocationRows = $budget->allocations->map(function ($a) use ($btId, $txFrom, $txTo) {
            $spent    = (float) BudgetTrackingTransaction::where('budget_tracking_id', $btId)
                ->where('budget_tracking_allocation_id', $a->id)
                ->where('type', 'expense')
                ->whereBetween('date', [$txFrom, $txTo])
                ->sum('amount');
            $allocated = (float) $a->allocated_amount;
            $usagePct  = $allocated > 0 ? round(($spent / $allocated) * 100, 2) : 0;

            return [
                'id'               => $a->id,
                'name'             => $a->name,
                'color'            => $a->color,
                'allocated_amount' => $allocated,
                'spent_amount'     => round($spent, 2),
                'remaining_amount' => round(max(0, $allocated - $spent), 2),
                'usage_pct'        => $usagePct,
                'status'           => $this->budgetStatus($usagePct, 80),
            ];
        })->values()->toArray();

        return [
            // Personal budgets sub-section
            'total_budgeted'    => round($totalBudgeted, 2),
            'total_spent'       => round($totalSpent, 2),
            'total_remaining'   => round(max(0, $totalBudgeted - $totalSpent), 2),
            'usage_pct'         => $overallUsage,
            'within_budget'     => $totalSpent <= $totalBudgeted,
            'within_income'     => $periodIncome > 0 ? $totalSpent <= $periodIncome : true,
            'income_surplus'    => round($periodIncome - $totalSpent, 2),
            'status'            => $overallStatus,
            'budget_count'      => $budgets->count(),
            'budgets'           => $budgetRows,
            // Tracker allocations sub-section
            'tracker' => [
                'total_allocated'  => round($btAllocated, 2),
                'total_income'     => round($btIncome, 2),
                'total_expense'    => round($btExpense, 2),
                'usage_pct'        => $btUsagePct,
                'member_count'     => $budget->members->count(),
                'allocations'      => $allocationRows,
            ],
        ];
    }

    // ─── Status Helpers ───────────────────────────────────────────────────────────

    private function budgetStatus(float $usagePct, int $threshold = 80): string
    {
        return match (true) {
            $usagePct > 100         => 'over_budget',
            $usagePct >= $threshold => 'warning',
            default                 => 'on_track',
        };
    }

    private function worstStatus(array $statuses): string
    {
        $order = ['over_income' => 4, 'over_budget' => 3, 'warning' => 2, 'on_track' => 1];
        return collect($statuses)->sortByDesc(fn($s) => $order[$s] ?? 0)->first() ?? 'on_track';
    }

    // ─── Socioeconomic Class ──────────────────────────────────────────────────────

    private function getSocioeconomicClass(float $avgMonthlyIncome, int $monthsCount): array
    {
        $tiers = [
            ['key' => 'poor',            'label' => 'Poor',                    'range' => 'Below ₱10,957',        'color' => 'red',    'min' => 0,      'max' => 10956.99],
            ['key' => 'low_income',      'label' => 'Low Income',              'range' => '₱10,957 – ₱21,913',   'color' => 'orange', 'min' => 10957,  'max' => 21913.99],
            ['key' => 'lower_middle',    'label' => 'Lower Middle Income',     'range' => '₱21,914 – ₱43,827',   'color' => 'amber',  'min' => 21914,  'max' => 43827.99],
            ['key' => 'middle_class',    'label' => 'Middle Class',            'range' => '₱43,828 – ₱76,668',   'color' => 'blue',   'min' => 43828,  'max' => 76668.99],
            ['key' => 'upper_middle',    'label' => 'Upper Middle Income',     'range' => '₱76,669 – ₱131,483',  'color' => 'indigo', 'min' => 76669,  'max' => 131483.99],
            ['key' => 'upper_middle_nr', 'label' => 'Upper Middle (Not Rich)', 'range' => '₱131,484 – ₱219,139', 'color' => 'violet', 'min' => 131484, 'max' => 219139.99],
            ['key' => 'rich',            'label' => 'Rich',                    'range' => '₱219,140 and above',   'color' => 'green',  'min' => 219140, 'max' => PHP_FLOAT_MAX],
        ];

        $current = collect($tiers)->first(fn($t) => $avgMonthlyIncome >= $t['min'] && $avgMonthlyIncome <= $t['max']) ?? $tiers[0];
        $currentIndex = collect($tiers)->search(fn($t) => $t['key'] === $current['key']);
        $next         = ($currentIndex !== false && isset($tiers[$currentIndex + 1])) ? $tiers[$currentIndex + 1] : null;
        $gapToNext    = $next ? round($next['min'] - $avgMonthlyIncome, 2) : null;

        return [
            'key'                => $current['key'],
            'label'              => $current['label'],
            'range'              => $current['range'],
            'color'              => $current['color'],
            'avg_monthly_income' => $avgMonthlyIncome,
            'months_count'       => $monthsCount,
            'next_class'         => $next ? $next['label'] : null,
            'gap_to_next'        => $gapToNext,
            'all_tiers'          => $tiers,
        ];
    }
}
