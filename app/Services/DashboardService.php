<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetTracking;
use App\Models\BudgetTrackingTransaction;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\Income;
use App\Models\CryptoAsset;
use App\Models\Investment;
use App\Models\Payment;
use App\Models\ModuleTransfer;
use App\Models\InsurancePayment;
use App\Models\Stock;
use App\Models\Purchase;
use App\Models\PurchasePayment;
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

        // Personal debt payments are OUTGOING (you pay out)
        $totalPersonalDebtPayments = (float) Payment::where('budget_tracking_id', $btId)
            ->whereHas('debt', fn($q) => $q->where('type', 'personal'))
            ->sum('amount');

        // Business debt payments are INCOMING (borrower pays you back)
        $totalBusinessDebtReceived = (float) Payment::where('budget_tracking_id', $btId)
            ->whereHas('debt', fn($q) => $q->where('type', 'business'))
            ->sum('amount');

        $totalDebtPayments = $totalPersonalDebtPayments; // for display (outgoing only)

        // CC installment payments recorded via "Pay Month" button
        $totalPurchasePayments = (float) PurchasePayment::where('budget_tracking_id', $btId)->sum('amount');

        // Cash / other purchases (paid in full on purchase date)
        $totalCashPurchases = (float) Purchase::where('budget_tracking_id', $btId)
            ->whereIn('payment_method', ['cash', 'other'])
            ->sum('total_cost');

        $totalInsurancePayments = (float) InsurancePayment::where('budget_tracking_id', $btId)->sum('amount');

        $totalOutgoingTransfers = (float) ModuleTransfer::where('budget_tracking_id', $btId)
            ->whereIn('module', ['investment', 'stock', 'crypto', 'saving'])->sum('total');

        $totalIncomingTransfers = (float) ModuleTransfer::where('budget_tracking_id', $btId)
            ->where('module', 'income')->sum('amount');

        // Deployed amounts per module (cost basis / amount invested)
        $deployedAmounts = [
            'investment' => (float) Investment::where('budget_tracking_id', $btId)->sum('amount_invested'),
            'stock'      => (float) (\App\Models\StockLot::whereHas('stock', fn($q) => $q->where('budget_tracking_id', $btId))->selectRaw('SUM(shares * buy_price) as total')->value('total') ?? 0),
            'crypto'     => (float) (\App\Models\CryptoLot::whereHas('cryptoAsset', fn($q) => $q->where('budget_tracking_id', $btId))->selectRaw('SUM(quantity * buy_price) as total')->value('total') ?? 0),
            'saving'     => 0,
        ];

        $transferSummary = [];
        foreach (['investment', 'stock', 'crypto', 'saving'] as $mod) {
            // All money transferred INTO this fund (from any source)
            $rows          = ModuleTransfer::where('budget_tracking_id', $btId)->where('module', $mod)->get();
            $transferredIn = round($rows->sum('amount'), 2);

            // All money transferred OUT of this fund (to any destination, full total incl. fee)
            $outRows        = ModuleTransfer::where('budget_tracking_id', $btId)->where('transfer_from', $mod)->get();
            $transferredOut = round($outRows->sum('total'), 2);

            // Deployed = money already committed to assets in this fund (cannot be re-transferred)
            $deployed = $deployedAmounts[$mod] ?? 0;

            // Available = incoming − outgoing − deployed (true spendable/transferable balance)
            $availableBalance = round($transferredIn - $transferredOut - $deployed, 2);

            // Latest single transfer INTO this fund
            $latest = $rows->sortByDesc('transfer_date')->first();

            $transferSummary[$mod] = [
                'total_transferred'   => $transferredIn,
                'total_outgoing'      => $transferredOut,
                'deployed'            => round($deployed, 2),
                'available_balance'   => $availableBalance,
                'count'               => $rows->count(),
                // Latest transfer details
                'latest_amount'       => $latest ? round((float) $latest->amount, 2) : null,
                'latest_fee'          => $latest ? round((float) $latest->transfer_fee, 2) : null,
                'latest_total'        => $latest ? round((float) $latest->total, 2) : null,
                'latest_date'         => $latest?->transfer_date?->toDateString(),
            ];
        }

        $balance = $totalIncome + $totalBusinessDebtReceived
                 - $totalExpenses - $totalPersonalDebtPayments
                 - $totalPurchasePayments - $totalCashPurchases
                 - $totalInsurancePayments
                 - $totalOutgoingTransfers + $totalIncomingTransfers;

        $totalDebt = (float) Debt::where('budget_tracking_id', $btId)
            ->where('status', '!=', 'paid')
            ->sum('remaining_balance');

        $totalInvestments = (float) Investment::where('budget_tracking_id', $btId)
            ->sum('current_value');

        // ── PERIOD-filtered income ────────────────────────────────────────────────
        $periodIncome = (float) Income::where('budget_tracking_id', $btId)
            ->whereBetween('received_at', [$dateFrom, $dateTo])
            ->sum('amount');

        // ── Total outgoing for health score (all spending) ───────────────────────
        $totalOutgoing = $totalExpenses + $totalPersonalDebtPayments + $totalPurchasePayments + $totalCashPurchases + $totalOutgoingTransfers - $totalIncomingTransfers;

        // ── Sub-sections ─────────────────────────────────────────────────────────
        $monthlyData        = $this->getMonthlyData($budget);
        $categoryBreakdown  = $this->getCategoryBreakdown($budget, $dateFrom, $dateTo);
        $recentTransactions = $this->buildTransactions($budget, 10);
        $healthScore        = $this->calculateFinancialHealthScore(
            $totalIncome, $totalOutgoing, $totalDebt, $totalInvestments
        );
        $budgetMonitor         = $this->getBudgetMonitor($budget, $dateFrom, $dateTo, $periodIncome);
        $budgetList            = $this->getBudgetList($budget, $dateFrom, $dateTo);
        $debtList              = $this->getDebtList($budget);
        $monthReport           = $this->getMonthReport($budget);
        $yearReport            = $this->getYearReport($budget);
        $purchaseList          = $this->getPurchaseList($budget);
        $incomeTransactions    = $this->getIncomeTransactions($budget, $dateFrom, $dateTo);
        $expenseTransactions   = $this->getExpenseTransactions($budget, $dateFrom, $dateTo);
        $otherTransactions     = $this->getOtherTransactions($budget, $dateFrom, $dateTo);

        return [
            'total_income'                  => round($totalIncome, 2),
            'total_expenses'                => round($totalExpenses, 2),
            'total_debt_payments'           => round($totalDebtPayments, 2),
            'total_business_debt_received'  => round($totalBusinessDebtReceived, 2),
            'total_purchase_payments'       => round($totalPurchasePayments, 2),
            'total_cash_purchases'          => round($totalCashPurchases, 2),
            'total_outgoing'                => round($totalOutgoing, 2),
            'balance'                       => round($balance, 2),
            'total_savings'                 => max(0, round($balance, 2)),
            'total_debt'                => round($totalDebt, 2),
            'total_investments'         => round($totalInvestments, 2),
            'financial_health'          => $healthScore,
            'budget_monitor'            => $budgetMonitor,
            'budget_list'               => $budgetList,
            'recent_transactions'       => $recentTransactions,
            'expense_breakdown'         => $categoryBreakdown,
            'category_breakdown'        => $categoryBreakdown,
            'debt_list'                 => $debtList,
            'purchase_list'             => $purchaseList,
            'transfer_summary'          => $transferSummary,
            'total_module_transfers'    => round($totalOutgoingTransfers, 2),
            'month_report'              => $monthReport,
            'year_report'               => $yearReport,
            'monthly_data'              => $monthlyData,
            'income_transactions'       => $incomeTransactions,
            'expense_transactions'      => $expenseTransactions,
            'other_transactions'        => $otherTransactions,
            'period'                    => ['from' => $dateFrom, 'to' => $dateTo],
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
            $total     = $b->total_budget;
            $spent     = $b->spent_amount;
            $remaining = $b->remaining_amount; // can be negative
            $usagePct  = $total > 0 ? round(($spent / $total) * 100, 2) : 0;

            return [
                'id'               => $b->id,
                'name'             => $b->name,
                'category'         => $b->category?->name,
                'category_color'   => $b->category?->color,
                'period'           => $b->period,
                'start_date'       => $b->start_date?->toDateString(),
                'amount'           => (float) $b->amount,
                'total_budget'     => round($total, 2),
                'spent_amount'     => round($spent, 2),
                'remaining_amount' => round($remaining, 2),
                'usage_pct'        => $usagePct,
                'status'           => $this->budgetStatus($usagePct),
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

        $incomes = Income::where('budget_tracking_id', $btId)
            ->get()
            ->map(fn($i) => [
                'id'         => $i->id,
                'type'       => 'income',
                'title'      => $i->title,
                'amount'     => (float) $i->amount,
                'date'       => $i->received_at,
                'category'   => $i->source,
                'user_name'  => $i->user?->name,
                'created_at' => $i->created_at,
            ]);

        $expenses = Expense::with('category')
            ->where('budget_tracking_id', $btId)
            ->get()
            ->map(fn($e) => [
                'id'         => $e->id,
                'type'       => 'expense',
                'title'      => $e->title,
                'amount'     => (float) $e->amount,
                'date'       => $e->spent_at,
                'category'   => $e->category?->name,
                'user_name'  => $e->user?->name,
                'created_at' => $e->created_at,
            ]);

        $payments = Payment::with('debt')
            ->where('budget_tracking_id', $btId)
            ->get()
            ->map(function ($p) {
                $isBusiness = $p->debt?->type === 'business';
                return [
                    'id'         => $p->id,
                    'type'       => $isBusiness ? 'business_debt_received' : 'debt_payment',
                    'title'      => $isBusiness
                        ? 'Received — ' . ($p->debt?->lender_name ?? 'Business Debt')
                        : 'Payment — ' . ($p->debt?->lender_name ?? 'Debt'),
                    'amount'     => (float) $p->amount,
                    'date'       => $p->payment_date,
                    'category'   => $isBusiness ? 'Business Debt Received' : 'Debt Payment',
                    'user_name'  => $p->user?->name,
                    'created_at' => $p->created_at,
                ];
            });

        // Cash / Other purchases — one transaction on the day of purchase
        $cashPurchases = Purchase::where('budget_tracking_id', $btId)
            ->whereIn('payment_method', ['cash', 'other'])
            ->get()
            ->map(fn($p) => [
                'id'         => $p->id,
                'type'       => 'purchase',
                'title'      => $p->item_name,
                'amount'     => (float) $p->total_cost,
                'date'       => $p->purchase_date,
                'category'   => ucfirst(str_replace('_', ' ', $p->payment_method)),
                'user_name'  => $p->user?->name,
                'created_at' => $p->created_at,
            ]);

        // CC installment payments — each "Pay Month" click is its own transaction
        $purchasePayments = PurchasePayment::with('purchase')
            ->where('budget_tracking_id', $btId)
            ->get()
            ->map(fn($pp) => [
                'id'         => $pp->id,
                'type'       => 'purchase_payment',
                'title'      => ($pp->purchase?->item_name ?? 'Purchase') . ' — Installment #' . $pp->installment_number,
                'amount'     => (float) $pp->amount,
                'date'       => $pp->paid_at,
                'category'   => 'Credit Card Installment',
                'user_name'  => $pp->user?->name,
                'created_at' => $pp->created_at,
            ]);

        $moduleTransfers = ModuleTransfer::where('budget_tracking_id', $btId)
            ->get()
            ->map(fn($t) => [
                'id'         => $t->id,
                'type'       => $t->module === 'income' ? 'module_transfer_back' : 'module_transfer',
                'title'      => 'Transfer ' . ucfirst($t->transfer_from) . ' → ' . ucfirst($t->module),
                'amount'     => $t->module === 'income' ? (float) $t->amount : (float) $t->total,
                'date'       => $t->transfer_date,
                'category'   => $t->module === 'income' ? ucfirst($t->transfer_from) : ucfirst($t->module),
                'user_name'  => $t->user?->name,
                'created_at' => $t->created_at,
            ]);

        return $incomes->concat($expenses)->concat($payments)->concat($cashPurchases)->concat($purchasePayments)->concat($moduleTransfers)
            ->sortByDesc('created_at')
            ->values();
    }

    // ─── Income Transactions ──────────────────────────────────────────────────────

    private function getIncomeTransactions(BudgetTracking $budget, string $dateFrom, string $dateTo): array
    {
        return Income::where('budget_tracking_id', $budget->id)
            ->whereBetween('received_at', [$dateFrom, $dateTo])
            ->orderByDesc('received_at')
            ->get()
            ->map(fn($i) => [
                'id'     => $i->id,
                'title'  => $i->title,
                'source' => $i->source,
                'amount' => (float) $i->amount,
                'date'   => $i->received_at?->toDateString(),
            ])
            ->values()
            ->toArray();
    }

    // ─── Expense Transactions ─────────────────────────────────────────────────────

    private function getExpenseTransactions(BudgetTracking $budget, string $dateFrom, string $dateTo): array
    {
        return Expense::with('category')
            ->where('budget_tracking_id', $budget->id)
            ->whereBetween('spent_at', [$dateFrom, $dateTo])
            ->orderByDesc('spent_at')
            ->get()
            ->map(fn($e) => [
                'id'             => $e->id,
                'description'    => $e->description ?? $e->title,
                'category'       => $e->category?->name ?? 'Uncategorized',
                'category_color' => $e->category?->color ?? '#6B7280',
                'amount'         => (float) $e->amount,
                'date'           => $e->spent_at?->toDateString(),
            ])
            ->values()
            ->toArray();
    }

    // ─── Other Transactions ───────────────────────────────────────────────────────

    private function getOtherTransactions(BudgetTracking $budget, string $dateFrom, string $dateTo): array
    {
        $btId = $budget->id;

        $debtPayments = Payment::with('debt')
            ->where('budget_tracking_id', $btId)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderByDesc('payment_date')
            ->get()
            ->map(function ($p) {
                $isBusiness = $p->debt?->type === 'business';
                return [
                    'id'     => $p->id,
                    'type'   => $isBusiness ? 'business_debt_received' : 'debt_payment',
                    'label'  => $isBusiness ? 'Biz Debt Received' : 'Debt Payment',
                    'title'  => $isBusiness
                        ? ($p->debt?->borrower_name ?? 'Business Debt')
                        : ($p->debt?->lender_name   ?? 'Debt'),
                    'amount' => (float) $p->amount,
                    'date'   => $p->payment_date?->toDateString(),
                ];
            });

        $cashPurchases = Purchase::where('budget_tracking_id', $btId)
            ->whereIn('payment_method', ['cash', 'other'])
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->orderByDesc('purchase_date')
            ->get()
            ->map(fn($p) => [
                'id'     => $p->id,
                'type'   => 'purchase',
                'label'  => 'Cash Purchase',
                'title'  => $p->item_name,
                'amount' => (float) $p->total_cost,
                'date'   => $p->purchase_date?->toDateString(),
            ]);

        $ccInstallments = PurchasePayment::with('purchase')
            ->where('budget_tracking_id', $btId)
            ->whereBetween('paid_at', [$dateFrom, $dateTo])
            ->orderByDesc('paid_at')
            ->get()
            ->map(fn($pp) => [
                'id'     => $pp->id,
                'type'   => 'purchase_payment',
                'label'  => 'CC Installment #' . $pp->installment_number,
                'title'  => $pp->purchase?->item_name ?? 'Purchase',
                'amount' => (float) $pp->amount,
                'date'   => $pp->paid_at?->toDateString(),
            ]);

        $transfers = ModuleTransfer::where('budget_tracking_id', $btId)
            ->whereBetween('transfer_date', [$dateFrom, $dateTo])
            ->orderByDesc('transfer_date')
            ->get()
            ->map(fn($t) => [
                'id'     => $t->id,
                'type'   => $t->module === 'income' ? 'module_transfer_back' : 'module_transfer',
                'label'  => $t->module === 'income' ? 'Transfer In' : 'Fund Transfer',
                'title'  => ucfirst($t->transfer_from) . ' → ' . ucfirst($t->module),
                'amount' => $t->module === 'income' ? (float) $t->amount : (float) $t->total,
                'date'   => $t->transfer_date?->toDateString(),
            ]);

        return $debtPayments
            ->concat($cashPurchases)
            ->concat($ccInstallments)
            ->concat($transfers)
            ->sortByDesc('date')
            ->values()
            ->toArray();
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
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(Debt $d) => [
                'id'                => $d->id,
                'lender_name'       => $d->lender_name,
                'borrower_name'     => $d->borrower_name,
                'type'              => $d->type,
                'personal_mode'     => $d->personal_mode,
                'original_amount'   => (float) $d->amount,
                'remaining_balance' => (float) $d->remaining_balance,
                'total_paid'        => round((float) $d->amount - (float) $d->remaining_balance, 2),
                'interest_rate'     => (float) $d->interest_rate,
                'status'            => $d->status,
                'user_name'         => $d->user?->name,
            ])
            ->values()
            ->toArray();
    }

    // ─── Purchase List ────────────────────────────────────────────────────────────

    /**
     * Up to 10 purchases that still have a remaining balance (CC not fully paid).
     * Shows: title, payment mode, total amount, unpaid (remaining_balance), paid (amount_paid).
     */
    private function getPurchaseList(BudgetTracking $budget): array
    {
        return Purchase::where('budget_tracking_id', $budget->id)
            ->where('payment_method', 'credit_card')
            ->whereColumn('installments_paid', '<', 'installment_count')
            ->orderBy('purchase_date', 'desc')
            ->limit(10)
            ->get()
            ->map(fn(Purchase $p) => [
                'id'               => $p->id,
                'title'            => $p->item_name,
                'mode'             => $p->payment_method,
                'total_amount'     => round((float) $p->total_cost, 2),
                'paid'             => round($p->amount_paid, 2),
                'unpaid'           => round($p->remaining_balance, 2),
                'installment_count'=> (int) $p->installment_count,
                'installments_paid'=> (int) $p->installments_paid,
                'purchase_date'    => $p->purchase_date?->toDateString(),
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

        $personalDebtPayments = (float) Payment::where('budget_tracking_id', $btId)
            ->whereHas('debt', fn($q) => $q->where('type', 'personal'))
            ->whereYear('payment_date', $year)->whereMonth('payment_date', $month)->sum('amount');

        $businessDebtReceived = (float) Payment::where('budget_tracking_id', $btId)
            ->whereHas('debt', fn($q) => $q->where('type', 'business'))
            ->whereYear('payment_date', $year)->whereMonth('payment_date', $month)->sum('amount');

        $debtPayments = $personalDebtPayments; // outgoing only, for display

        $purchasePayments = (float) PurchasePayment::where('budget_tracking_id', $btId)
            ->whereYear('paid_at', $year)->whereMonth('paid_at', $month)->sum('amount');

        $cashPurchases = (float) Purchase::where('budget_tracking_id', $btId)
            ->whereIn('payment_method', ['cash', 'other'])
            ->whereYear('purchase_date', $year)->whereMonth('purchase_date', $month)->sum('total_cost');

        $totalDebt        = (float) Debt::where('budget_tracking_id', $btId)->where('status', '!=', 'paid')->sum('remaining_balance');
        $totalInvestments = (float) Investment::where('budget_tracking_id', $btId)->sum('current_value');

        $balance     = $income + $businessDebtReceived - $expenses - $personalDebtPayments - $purchasePayments - $cashPurchases;
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
            'total_income'              => round($income, 2),
            'total_expenses'            => round($expenses, 2),
            'debt_payments'             => round($debtPayments, 2),
            'business_debt_received'    => round($businessDebtReceived, 2),
            'purchase_payments'         => round($purchasePayments, 2),
            'cash_purchases'            => round($cashPurchases, 2),
            'balance'                   => round($balance, 2),
            'total_debt'                => round($totalDebt, 2),
            'total_investments'         => round($totalInvestments, 2),
            'balance_remaining'         => round(max(0, $balance), 2),
            'savings_rate_pct'          => $savingsRate,
            'socioeconomic_class'       => $this->getSocioeconomicClass($avgMonthlyIncome, $monthsWithIncome),
        ];
    }

    // ─── Year Report ──────────────────────────────────────────────────────────────

    private function getYearReport(BudgetTracking $budget): array
    {
        $btId = $budget->id;
        $year = now()->year;

        $income               = (float) Income::where('budget_tracking_id', $btId)->whereYear('received_at', $year)->sum('amount');
        $expenses             = (float) Expense::where('budget_tracking_id', $btId)->whereYear('spent_at', $year)->sum('amount');
        $personalDebtPayments = (float) Payment::where('budget_tracking_id', $btId)
            ->whereHas('debt', fn($q) => $q->where('type', 'personal'))
            ->whereYear('payment_date', $year)->sum('amount');
        $businessDebtReceived = (float) Payment::where('budget_tracking_id', $btId)
            ->whereHas('debt', fn($q) => $q->where('type', 'business'))
            ->whereYear('payment_date', $year)->sum('amount');
        $purchasePayments     = (float) PurchasePayment::where('budget_tracking_id', $btId)->whereYear('paid_at', $year)->sum('amount');
        $cashPurchases        = (float) Purchase::where('budget_tracking_id', $btId)
            ->whereIn('payment_method', ['cash', 'other'])
            ->whereYear('purchase_date', $year)->sum('total_cost');
        $totalDebt   = (float) Debt::where('budget_tracking_id', $btId)->where('status', '!=', 'paid')->sum('remaining_balance');
        $totalInvest = (float) Investment::where('budget_tracking_id', $btId)->sum('current_value');

        $balance     = $income + $businessDebtReceived - $expenses - $personalDebtPayments - $purchasePayments - $cashPurchases;
        $savingsRate = $income > 0 ? round(($balance / $income) * 100, 2) : 0;

        return [
            'period'                 => (string) $year,
            'year'                   => $year,
            'total_income'           => round($income, 2),
            'total_expenses'         => round($expenses, 2),
            'debt_payments'          => round($personalDebtPayments, 2),
            'business_debt_received' => round($businessDebtReceived, 2),
            'purchase_payments'      => round($purchasePayments, 2),
            'cash_purchases'         => round($cashPurchases, 2),
            'balance'                => round($balance, 2),
            'total_debt'             => round($totalDebt, 2),
            'total_investments'      => round($totalInvest, 2),
            'balance_remaining'      => round(max(0, $balance), 2),
            'savings_rate_pct'       => $savingsRate,
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

            $income = Income::where('budget_tracking_id', $btId)
                ->whereYear('received_at', $yearNum)->whereMonth('received_at', $monthNum)->sum('amount');

            $expense = Expense::where('budget_tracking_id', $btId)
                ->whereYear('spent_at', $yearNum)->whereMonth('spent_at', $monthNum)->sum('amount');

            $personalDebtPmt = Payment::where('budget_tracking_id', $btId)
                ->whereHas('debt', fn($q) => $q->where('type', 'personal'))
                ->whereYear('payment_date', $yearNum)->whereMonth('payment_date', $monthNum)->sum('amount');

            $businessDebtRcv = Payment::where('budget_tracking_id', $btId)
                ->whereHas('debt', fn($q) => $q->where('type', 'business'))
                ->whereYear('payment_date', $yearNum)->whereMonth('payment_date', $monthNum)->sum('amount');

            $purchasePayments = PurchasePayment::where('budget_tracking_id', $btId)
                ->whereYear('paid_at', $yearNum)->whereMonth('paid_at', $monthNum)->sum('amount');

            $cashPurchases = Purchase::where('budget_tracking_id', $btId)
                ->whereIn('payment_method', ['cash', 'other'])
                ->whereYear('purchase_date', $yearNum)->whereMonth('purchase_date', $monthNum)->sum('total_cost');

            $totalOutflow = (float) $expense + (float) $personalDebtPmt + (float) $purchasePayments + (float) $cashPurchases;
            $net          = (float) $income + (float) $businessDebtRcv - $totalOutflow;

            $months[] = [
                'month'                  => $date->format('Y-m'),
                'label'                  => $date->format('M Y'),
                'income'                 => round((float) $income, 2),
                'expense'                => round((float) $expense, 2),
                'personal_debt_payments' => round((float) $personalDebtPmt, 2),
                'business_debt_received' => round((float) $businessDebtRcv, 2),
                'purchase_payments'      => round((float) $purchasePayments, 2),
                'cash_purchases'         => round((float) $cashPurchases, 2),
                'total_outflow'          => round($totalOutflow, 2),
                'net'                    => round($net, 2),
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
            ->get();

        $budgetRows = $budgets->map(function (Budget $b) {
            $total     = $b->total_budget;
            $spent     = $b->spent_amount;
            $remaining = $b->remaining_amount; // can be negative
            $usagePct  = $total > 0 ? round(($spent / $total) * 100, 2) : 0;

            return [
                'id'               => $b->id,
                'name'             => $b->name,
                'category'         => $b->category?->name,
                'period'           => $b->period,
                'start_date'       => $b->start_date?->toDateString(),
                'amount'           => (float) $b->amount,
                'total_budget'     => round($total, 2),
                'spent_amount'     => round($spent, 2),
                'remaining_amount' => round($remaining, 2),
                'usage_pct'        => $usagePct,
                'status'           => $this->budgetStatus($usagePct),
            ];
        })->values()->toArray();

        $totalBudgeted = array_sum(array_column($budgetRows, 'total_budget'));
        $totalSpent    = array_sum(array_column($budgetRows, 'spent_amount'));
        $overallUsage  = $totalBudgeted > 0 ? round(($totalSpent / $totalBudgeted) * 100, 2) : 0;
        $overallStatus = $this->worstStatus(array_column($budgetRows, 'status'));

        // Period purchase payments (CC installments + cash purchases) reduce available income
        $periodPurchasePayments = (float) PurchasePayment::where('budget_tracking_id', $btId)
            ->whereBetween('paid_at', [$dateFrom, $dateTo])
            ->sum('amount');

        $periodCashPurchases = (float) Purchase::where('budget_tracking_id', $btId)
            ->whereIn('payment_method', ['cash', 'other'])
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->sum('total_cost');

        $periodTotalOutgoing = $totalSpent + $periodPurchasePayments + $periodCashPurchases;

        if ($periodIncome > 0 && $periodTotalOutgoing > $periodIncome) {
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
                'status'           => $this->budgetStatus($usagePct),
            ];
        })->values()->toArray();

        return [
            // Personal budgets sub-section
            'total_budgeted'    => round($totalBudgeted, 2),
            'total_spent'       => round($totalSpent, 2),
            'total_remaining'   => round(max(0, $totalBudgeted - $totalSpent), 2),
            'usage_pct'         => $overallUsage,
            'within_budget'                => $totalSpent <= $totalBudgeted,
            'within_income'               => $periodIncome > 0 ? $periodTotalOutgoing <= $periodIncome : true,
            'income_surplus'              => round($periodIncome - $periodTotalOutgoing, 2),
            'period_purchase_payments'    => round($periodPurchasePayments, 2),
            'period_cash_purchases'       => round($periodCashPurchases, 2),
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

    private function budgetStatus(float $usagePct): string
    {
        return match (true) {
            $usagePct > 100  => 'over_budget',
            $usagePct >= 80  => 'warning',
            default          => 'on_track',
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
