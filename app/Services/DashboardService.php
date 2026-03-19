<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Income;
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

        $monthlyData = $this->getMonthlyData($user);
        $categoryBreakdown = $this->getCategoryBreakdown($user, $dateFrom, $dateTo);
        $recentTransactions = $this->getRecentTransactions($user);

        return [
            'total_income' => round($totalIncome, 2),
            'total_expenses' => round($totalExpenses, 2),
            'balance' => round($balance, 2),
            'total_savings' => max(0, round($balance, 2)),
            'total_debt' => round($totalDebt, 2),
            'total_investments' => round($totalInvestments, 2),
            'monthly_data' => $monthlyData,
            'category_breakdown' => $categoryBreakdown,
            'recent_transactions' => $recentTransactions,
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
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
