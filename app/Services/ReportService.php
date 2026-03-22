<?php

namespace App\Services;

use App\Models\BudgetTracking;
use App\Models\CryptoAsset;
use App\Models\CryptoDividend;
use App\Models\CryptoLot;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Investment;
use App\Models\InvestmentDividend;
use App\Models\ModuleTransfer;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Stock;
use App\Models\StockDividend;
use App\Models\StockLot;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    public function generateIncomeExpenseReport(BudgetTracking $budget, array $filters = []): array
    {
        $btId     = $budget->id;
        $dateFrom = $filters['date_from'] ?? now()->startOfYear()->toDateString();
        $dateTo   = $filters['date_to']   ?? now()->endOfYear()->toDateString();

        // ── Income ────────────────────────────────────────────────────────────
        $incomes = Income::where('budget_tracking_id', $btId)
            ->whereBetween('received_at', [$dateFrom, $dateTo])
            ->orderBy('received_at', 'desc')
            ->get();

        // ── Expenses ──────────────────────────────────────────────────────────
        $expenses = Expense::with('category')
            ->where('budget_tracking_id', $btId)
            ->whereBetween('spent_at', [$dateFrom, $dateTo])
            ->orderBy('spent_at', 'desc')
            ->get();

        // ── Debt Payments ─────────────────────────────────────────────────────
        $personalDebtPayments = (float) Payment::where('budget_tracking_id', $btId)
            ->whereHas('debt', fn($q) => $q->where('type', 'personal'))
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->sum('amount');

        $businessDebtReceived = (float) Payment::where('budget_tracking_id', $btId)
            ->whereHas('debt', fn($q) => $q->where('type', 'business'))
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->sum('amount');

        // ── Purchase Payments (CC installments) ───────────────────────────────
        $purchasePayments = (float) PurchasePayment::where('budget_tracking_id', $btId)
            ->whereBetween('paid_at', [$dateFrom, $dateTo])
            ->sum('amount');

        // ── Cash / Other Purchases ────────────────────────────────────────────
        $cashPurchases = (float) Purchase::where('budget_tracking_id', $btId)
            ->whereIn('payment_method', ['cash', 'other'])
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->sum('total_cost');

        // ── Totals ────────────────────────────────────────────────────────────
        $totalIncome   = (float) $incomes->sum('amount');
        $totalExpenses = (float) $expenses->sum('amount');
        $totalOutflow  = $totalExpenses + $personalDebtPayments + $purchasePayments + $cashPurchases;
        $net           = $totalIncome + $businessDebtReceived - $totalOutflow;

        $savingsRate  = $totalIncome > 0 ? ($net / $totalIncome) * 100 : 0;
        $expenseRatio = $totalIncome > 0 ? ($totalExpenses / $totalIncome) * 100 : 0;
        $outflowRatio = $totalIncome > 0 ? ($totalOutflow / $totalIncome) * 100 : 0;

        // ── Expense breakdown by category (with colour, count, %) ─────────────
        $expenseBreakdown  = $this->buildExpenseBreakdown($btId, $dateFrom, $dateTo, $totalExpenses);
        // Legacy flat map for backward compat
        $expenseByCategory = collect($expenseBreakdown)->pluck('total', 'name')->toArray();

        // ── Monthly trend (income, all outflows, net) ─────────────────────────
        $monthlyTrend = $this->buildMonthlyTrend($budget, $dateFrom, $dateTo);

        // ── Burn rate & savings runway ────────────────────────────────────────
        $periodDays    = max(1, (int) (new \DateTime($dateFrom))->diff(new \DateTime($dateTo))->days + 1);
        $dailyBurnRate = $totalOutflow / $periodDays;
        $runway        = $dailyBurnRate > 0 ? (int) ($net / $dailyBurnRate) : PHP_INT_MAX;

        return [
            'period'                  => ['from' => $dateFrom, 'to' => $dateTo],
            'total_income'            => round($totalIncome, 2),
            'total_expenses'          => round($totalExpenses, 2),
            'total_debt_payments'     => round($personalDebtPayments, 2),
            'business_debt_received'  => round($businessDebtReceived, 2),
            'total_purchase_payments' => round($purchasePayments, 2),
            'total_cash_purchases'    => round($cashPurchases, 2),
            'total_outflow'           => round($totalOutflow, 2),
            'net'                     => round($net, 2),
            'savings_rate_pct'        => round($savingsRate, 2),
            'expense_ratio_pct'       => round($expenseRatio, 2),
            'outflow_ratio_pct'       => round($outflowRatio, 2),
            'daily_burn_rate'         => round($dailyBurnRate, 2),
            'savings_runway_days'     => $runway >= PHP_INT_MAX ? null : $runway,
            'expense_breakdown'       => $expenseBreakdown,
            'expense_by_category'     => $expenseByCategory,
            'monthly_trend'           => $monthlyTrend,
            'incomes'                 => $incomes,
            'expenses'                => $expenses,
        ];
    }

    /**
     * Expense breakdown grouped by category with colour, count, and percentage.
     * Uncategorised expenses appear under "Uncategorized" with a neutral colour.
     */
    private function buildExpenseBreakdown(int $btId, string $dateFrom, string $dateTo, float $totalExpenses): array
    {
        $rows = DB::table('expenses')
            ->leftJoin('categories', 'expenses.category_id', '=', 'categories.id')
            ->where('expenses.budget_tracking_id', $btId)
            ->whereNull('expenses.deleted_at')
            ->whereBetween('expenses.spent_at', [$dateFrom, $dateTo])
            ->select(
                DB::raw('COALESCE(categories.id, 0) as id'),
                DB::raw('COALESCE(categories.name, "Uncategorized") as name'),
                DB::raw('COALESCE(categories.color, "#6B7280") as color'),
                DB::raw('SUM(expenses.amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->get();

        return $rows->map(fn($r) => [
            'id'    => (int) $r->id,
            'name'  => $r->name,
            'color' => $r->color,
            'total' => round((float) $r->total, 2),
            'count' => (int) $r->count,
            'pct'   => $totalExpenses > 0 ? round(($r->total / $totalExpenses) * 100, 2) : 0,
        ])->values()->toArray();
    }

    public function generateNetWorthReport(BudgetTracking $budget): array
    {
        // ── Investments ───────────────────────────────────────────────────────
        $totalInvestments = (float) Investment::where('budget_tracking_id', $budget->id)->sum('current_value');
        $investmentCost   = (float) Investment::where('budget_tracking_id', $budget->id)->sum('amount_invested');

        // ── Stocks ────────────────────────────────────────────────────────────
        $totalStocks = (float) Stock::where('budget_tracking_id', $budget->id)
            ->get()->sum(fn($s) => (float) $s->current_value);
        $stockCost   = (float) StockLot::whereHas('stock', fn($q) => $q->where('budget_tracking_id', $budget->id))
            ->selectRaw('SUM(shares * buy_price) as total')->value('total');

        // ── Crypto ────────────────────────────────────────────────────────────
        $totalCrypto = (float) CryptoAsset::where('budget_tracking_id', $budget->id)
            ->get()->sum(fn($a) => (float) $a->current_value);
        $cryptoCost  = (float) CryptoLot::whereHas('cryptoAsset', fn($q) => $q->where('budget_tracking_id', $budget->id))
            ->selectRaw('SUM(quantity * buy_price) as total')->value('total');

        // ── Liabilities ───────────────────────────────────────────────────────
        $totalDebt = (float) Debt::where('budget_tracking_id', $budget->id)
            ->where('status', '!=', 'paid')->sum('remaining_balance');

        // ── Available income balance (cash) ───────────────────────────────────
        $availableCash = round($budget->availableBalance(), 2);

        $totalAssets = $totalInvestments + $totalStocks + $totalCrypto;
        $netWorth    = $availableCash + $totalAssets - $totalDebt;

        return [
            'available_cash'    => $availableCash,
            'total_investments' => round($totalInvestments, 2),
            'investment_cost'   => round($investmentCost, 2),
            'investment_gain'   => round($totalInvestments - $investmentCost, 2),
            'total_stocks'      => round($totalStocks, 2),
            'stock_cost'        => round($stockCost, 2),
            'stock_gain'        => round($totalStocks - $stockCost, 2),
            'total_crypto'      => round($totalCrypto, 2),
            'crypto_cost'       => round($cryptoCost, 2),
            'crypto_gain'       => round($totalCrypto - $cryptoCost, 2),
            'total_assets'      => round($totalAssets, 2),
            'total_liabilities' => round($totalDebt, 2),
            'net_worth'         => round($netWorth, 2),
        ];
    }

    /**
     * Month-by-month trend: income vs ALL outflows (expenses, debt payments,
     * CC installments, cash purchases), aligned with the dashboard monthly data.
     */
    private function buildMonthlyTrend(BudgetTracking $budget, string $dateFrom, string $dateTo): array
    {
        $btId    = $budget->id;
        $start   = new \DateTime($dateFrom);
        $end     = new \DateTime($dateTo);
        $cursor  = (clone $start)->modify('first day of this month');
        $trend   = [];
        $prevNet = null;

        while ($cursor <= $end) {
            $y = $cursor->format('Y');
            $m = $cursor->format('m');

            $income = (float) Income::where('budget_tracking_id', $btId)
                ->whereYear('received_at', $y)->whereMonth('received_at', $m)->sum('amount');

            $expense = (float) Expense::where('budget_tracking_id', $btId)
                ->whereYear('spent_at', $y)->whereMonth('spent_at', $m)->sum('amount');

            $personalDebtPmt = (float) Payment::where('budget_tracking_id', $btId)
                ->whereHas('debt', fn($q) => $q->where('type', 'personal'))
                ->whereYear('payment_date', $y)->whereMonth('payment_date', $m)->sum('amount');

            $businessDebtRcv = (float) Payment::where('budget_tracking_id', $btId)
                ->whereHas('debt', fn($q) => $q->where('type', 'business'))
                ->whereYear('payment_date', $y)->whereMonth('payment_date', $m)->sum('amount');

            $purchasePayments = (float) PurchasePayment::where('budget_tracking_id', $btId)
                ->whereYear('paid_at', $y)->whereMonth('paid_at', $m)->sum('amount');

            $cashPurchases = (float) Purchase::where('budget_tracking_id', $btId)
                ->whereIn('payment_method', ['cash', 'other'])
                ->whereYear('purchase_date', $y)->whereMonth('purchase_date', $m)->sum('total_cost');

            $totalOutflow = $expense + $personalDebtPmt + $purchasePayments + $cashPurchases;
            $net          = $income + $businessDebtRcv - $totalOutflow;
            $savingsRate  = $income > 0 ? ($net / $income) * 100 : 0;
            $momChange    = ($prevNet !== null && $prevNet != 0)
                ? (($net - $prevNet) / abs($prevNet)) * 100
                : null;

            $trend[] = [
                'month'                  => $cursor->format('Y-m'),
                'label'                  => $cursor->format('M Y'),
                'income'                 => round($income, 2),
                'expense'                => round($expense, 2),
                'debt_payments'          => round($personalDebtPmt, 2),
                'business_debt_received' => round($businessDebtRcv, 2),
                'purchase_payments'      => round($purchasePayments, 2),
                'cash_purchases'         => round($cashPurchases, 2),
                'total_outflow'          => round($totalOutflow, 2),
                'net'                    => round($net, 2),
                'savings_rate_pct'       => round($savingsRate, 2),
                'mom_net_change_pct'     => $momChange !== null ? round($momChange, 2) : null,
            ];

            $prevNet = $net;
            $cursor->modify('+1 month');
        }

        return $trend;
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Full multi-sheet XLSX export
    // ──────────────────────────────────────────────────────────────────────────

    public function exportFullCsv(BudgetTracking $budget, array $filters = []): StreamedResponse
    {
        $ie       = $this->generateIncomeExpenseReport($budget, $filters);
        $nw       = $this->generateNetWorthReport($budget);
        $btId     = $budget->id;
        $dateFrom = $filters['date_from'] ?? now()->startOfYear()->toDateString();
        $dateTo   = $filters['date_to']   ?? now()->endOfYear()->toDateString();

        $debts = Debt::where('budget_tracking_id', $btId)->get();

        $debtPayments = Payment::with('debt')
            ->where('budget_tracking_id', $btId)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderBy('payment_date', 'desc')
            ->get();

        $purchases = Purchase::where('budget_tracking_id', $btId)
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->orderBy('purchase_date', 'desc')
            ->get();

        $purchasePayments = PurchasePayment::with('purchase')
            ->where('budget_tracking_id', $btId)
            ->whereBetween('paid_at', [$dateFrom, $dateTo])
            ->orderBy('paid_at', 'desc')
            ->get();

        $transfers = ModuleTransfer::where('budget_tracking_id', $btId)
            ->orderBy('transfer_date', 'desc')
            ->get();

        $investments = Investment::where('budget_tracking_id', $btId)->get();
        $stocks      = Stock::with('lots')->where('budget_tracking_id', $btId)->get();
        $cryptos     = CryptoAsset::with('lots')->where('budget_tracking_id', $btId)->get();

        // ── Build workbook ────────────────────────────────────────────────────
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // remove default blank sheet

        // 1. Summary
        $sh = $spreadsheet->createSheet();
        $sh->setTitle('Summary');
        $this->xlsxHeader($sh, ['Metric', 'Value'], 1);
        $rows = [
            ['Period',                $dateFrom . ' to ' . $dateTo],
            ['Total Income',          $ie['total_income']],
            ['Total Expenses',        $ie['total_expenses']],
            ['Total Debt Payments',   $ie['total_debt_payments']],
            ['Business Debt Received',$ie['business_debt_received']],
            ['Total CC Installments', $ie['total_purchase_payments']],
            ['Total Cash Purchases',  $ie['total_cash_purchases']],
            ['Total Outflow',         $ie['total_outflow']],
            ['Net',                   $ie['net']],
            ['Savings Rate (%)',      $ie['savings_rate_pct']],
            ['Expense Ratio (%)',     $ie['expense_ratio_pct']],
            ['Outflow Ratio (%)',     $ie['outflow_ratio_pct']],
            ['Daily Burn Rate',       $ie['daily_burn_rate']],
            ['Savings Runway (days)', $ie['savings_runway_days'] ?? 'N/A'],
        ];
        $this->xlsxRows($sh, $rows, 2);
        $sh->getColumnDimension('A')->setWidth(28);
        $sh->getColumnDimension('B')->setWidth(22);

        // 2. Net Worth
        $sh = $spreadsheet->createSheet();
        $sh->setTitle('Net Worth');
        $this->xlsxHeader($sh, ['Metric', 'Value'], 1);
        $nwRows = [
            ['Available Cash',     $nw['available_cash']],
            ['Total Assets',       $nw['total_assets']],
            ['Total Liabilities',  $nw['total_liabilities']],
            ['Net Worth',          $nw['net_worth']],
            [],
        ];
        $this->xlsxRows($sh, $nwRows, 2);
        $r = count($nwRows) + 2;
        $this->xlsxHeader($sh, ['Asset Class', 'Current Value', 'Cost Basis', 'Unrealized Gain/Loss'], $r);
        $this->xlsxRows($sh, [
            ['Investments', $nw['total_investments'], $nw['investment_cost'], $nw['investment_gain']],
            ['Stocks',      $nw['total_stocks'],      $nw['stock_cost'],      $nw['stock_gain']],
            ['Crypto',      $nw['total_crypto'],      $nw['crypto_cost'],     $nw['crypto_gain']],
        ], $r + 1);
        foreach (['A','B','C','D'] as $col) {
            $sh->getColumnDimension($col)->setWidth(22);
        }

        // 3. Monthly Trend
        $sh = $spreadsheet->createSheet();
        $sh->setTitle('Monthly Trend');
        $this->xlsxHeader($sh, [
            'Month','Income','Expenses','Debt Payments','Business Received',
            'CC Installments','Cash Purchases','Total Outflow','Net','Savings Rate (%)','MoM Net Change (%)',
        ], 1);
        $trendRows = [];
        foreach ($ie['monthly_trend'] as $row) {
            $trendRows[] = [
                $row['label'], $row['income'], $row['expense'],
                $row['debt_payments'], $row['business_debt_received'],
                $row['purchase_payments'], $row['cash_purchases'],
                $row['total_outflow'], $row['net'],
                $row['savings_rate_pct'], $row['mom_net_change_pct'] ?? '',
            ];
        }
        $this->xlsxRows($sh, $trendRows, 2);
        $sh->getColumnDimension('A')->setWidth(16);
        foreach (['B','C','D','E','F','G','H','I','J','K'] as $col) {
            $sh->getColumnDimension($col)->setWidth(18);
        }

        // 4. Expense Breakdown
        $sh = $spreadsheet->createSheet();
        $sh->setTitle('Expense Breakdown');
        $this->xlsxHeader($sh, ['Rank', 'Category', 'Total', 'Transactions', 'Share (%)'], 1);
        $catRows = [];
        foreach ($ie['expense_breakdown'] as $i => $cat) {
            $catRows[] = [$i + 1, $cat['name'], $cat['total'], $cat['count'], $cat['pct']];
        }
        $this->xlsxRows($sh, $catRows, 2);
        $sh->getColumnDimension('A')->setWidth(8);
        $sh->getColumnDimension('B')->setWidth(24);
        foreach (['C','D','E'] as $col) {
            $sh->getColumnDimension($col)->setWidth(16);
        }

        // 5. Income
        $sh = $spreadsheet->createSheet();
        $sh->setTitle('Income');
        $this->xlsxHeader($sh, ['Date', 'Title', 'Source', 'Amount'], 1);
        $incRows = [];
        foreach ($ie['incomes'] as $inc) {
            $incRows[] = [
                (string) $inc->received_at,
                $inc->title ?? '',
                $inc->source ?? '',
                (float) $inc->amount,
            ];
        }
        $this->xlsxRows($sh, $incRows, 2);
        $sh->getColumnDimension('A')->setWidth(14);
        $sh->getColumnDimension('B')->setWidth(28);
        $sh->getColumnDimension('C')->setWidth(22);
        $sh->getColumnDimension('D')->setWidth(16);

        // 6. Expenses
        $sh = $spreadsheet->createSheet();
        $sh->setTitle('Expenses');
        $this->xlsxHeader($sh, ['Date', 'Description', 'Category', 'Amount'], 1);
        $expRows = [];
        foreach ($ie['expenses'] as $exp) {
            $expRows[] = [
                (string) $exp->spent_at,
                $exp->description ?? '',
                $exp->category?->name ?? 'Uncategorized',
                (float) $exp->amount,
            ];
        }
        $this->xlsxRows($sh, $expRows, 2);
        $sh->getColumnDimension('A')->setWidth(14);
        $sh->getColumnDimension('B')->setWidth(32);
        $sh->getColumnDimension('C')->setWidth(22);
        $sh->getColumnDimension('D')->setWidth(16);

        // 7. Debts
        $sh = $spreadsheet->createSheet();
        $sh->setTitle('Debts');
        $this->xlsxHeader($sh, [
            'Type','Mode','Lender / Borrower','Business',
            'Original Amount','Remaining Balance','Interest Rate (%)','Monthly Payment','Months to Pay','Status',
        ], 1);
        $debtRows = [];
        foreach ($debts as $d) {
            $debtRows[] = [
                $d->type, $d->personal_mode ?? '',
                $d->type === 'personal' ? ($d->lender_name ?? '') : ($d->borrower_name ?? ''),
                $d->business_name ?? '',
                (float) $d->amount, (float) $d->remaining_balance,
                (float) $d->interest_rate, (float) $d->monthly_payment,
                $d->months_to_pay, $d->status,
            ];
        }
        $this->xlsxRows($sh, $debtRows, 2);
        foreach (['A','B','C','D','E','F','G','H','I','J'] as $col) {
            $sh->getColumnDimension($col)->setWidth(18);
        }

        // 8. Debt Payments
        $sh = $spreadsheet->createSheet();
        $sh->setTitle('Debt Payments');
        $this->xlsxHeader($sh, [
            'Date','Debt Type','Lender / Borrower','Installment #',
            'Amount','Principal Paid','Interest Paid','Note',
        ], 1);
        $dpRows = [];
        foreach ($debtPayments as $p) {
            $d = $p->debt;
            $dpRows[] = [
                (string) $p->payment_date,
                $d?->type ?? '',
                $d ? ($d->type === 'personal' ? ($d->lender_name ?? '') : ($d->borrower_name ?? '')) : '',
                $p->installment_number ?? '',
                (float) $p->amount,
                $p->principal_paid !== null ? (float) $p->principal_paid : '',
                $p->interest_paid  !== null ? (float) $p->interest_paid  : '',
                $p->note ?? '',
            ];
        }
        $this->xlsxRows($sh, $dpRows, 2);
        foreach (['A','B','C','D','E','F','G','H'] as $col) {
            $sh->getColumnDimension($col)->setWidth(18);
        }

        // 9. Purchases
        $sh = $spreadsheet->createSheet();
        $sh->setTitle('Purchases');
        $this->xlsxHeader($sh, [
            'Date','Item','Payment Method','Total Cost',
            'Installments','Monthly Amount','Amount Paid','Remaining',
        ], 1);
        $purRows = [];
        foreach ($purchases as $pu) {
            $purRows[] = [
                (string) $pu->purchase_date,
                $pu->item_name, $pu->payment_method,
                (float) $pu->total_cost,
                $pu->is_installment ? $pu->installment_count : 'N/A',
                $pu->is_installment ? (float) $pu->installment_amount : 'N/A',
                (float) $pu->amount_paid,
                (float) $pu->remaining_balance,
            ];
        }
        $this->xlsxRows($sh, $purRows, 2);
        foreach (['A','B','C','D','E','F','G','H'] as $col) {
            $sh->getColumnDimension($col)->setWidth(18);
        }

        // 10. CC Installments
        $sh = $spreadsheet->createSheet();
        $sh->setTitle('CC Installments');
        $this->xlsxHeader($sh, ['Date', 'Item', 'Installment #', 'Amount'], 1);
        $ccRows = [];
        foreach ($purchasePayments as $pp) {
            $ccRows[] = [
                (string) $pp->paid_at,
                $pp->purchase?->item_name ?? '',
                $pp->installment_number,
                (float) $pp->amount,
            ];
        }
        $this->xlsxRows($sh, $ccRows, 2);
        $sh->getColumnDimension('A')->setWidth(14);
        $sh->getColumnDimension('B')->setWidth(32);
        $sh->getColumnDimension('C')->setWidth(16);
        $sh->getColumnDimension('D')->setWidth(16);

        // 11. Fund Transfers
        $sh = $spreadsheet->createSheet();
        $sh->setTitle('Fund Transfers');
        $this->xlsxHeader($sh, ['Date', 'Module', 'From', 'Amount', 'Transfer Fee', 'Total Deducted', 'Note'], 1);
        $trRows = [];
        foreach ($transfers as $t) {
            $trRows[] = [
                (string) $t->transfer_date,
                strtoupper($t->module),
                $t->transfer_from ?? 'Income',
                (float) $t->amount,
                (float) $t->transfer_fee,
                (float) $t->total,
                $t->note ?? '',
            ];
        }
        $this->xlsxRows($sh, $trRows, 2);
        foreach (['A','B','C','D','E','F','G'] as $col) {
            $sh->getColumnDimension($col)->setWidth(18);
        }

        // 12. Investments
        $sh = $spreadsheet->createSheet();
        $sh->setTitle('Investments');
        $this->xlsxHeader($sh, [
            'Name','Type','Amount Invested','Current Value','Gain/Loss','ROI (%)','Total Paid','Payment Status','Date Started',
        ], 1);
        $invRows = [];
        foreach ($investments as $inv) {
            $invRows[] = [
                $inv->name, $inv->type,
                (float) $inv->amount_invested, (float) $inv->current_value,
                (float) $inv->roi_amount, (float) $inv->roi,
                (float) $inv->total_paid,
                $inv->payment_status ?? '',
                (string) ($inv->date_started ?? $inv->purchase_date ?? ''),
            ];
        }
        $this->xlsxRows($sh, $invRows, 2);
        $sh->getColumnDimension('A')->setWidth(28);
        foreach (['B','C','D','E','F','G','H','I'] as $col) {
            $sh->getColumnDimension($col)->setWidth(18);
        }

        // 13. Stocks
        $sh = $spreadsheet->createSheet();
        $sh->setTitle('Stocks');
        $this->xlsxHeader($sh, [
            'Symbol','Company','Total Shares','Avg Buy Price','Latest Price','Current Value','Cost Basis','Gain/Loss','ROI (%)',
        ], 1);
        $stRows = [];
        foreach ($stocks as $s) {
            $latestPrice = (float) ($s->latest_price ?? 0);
            $totalShares = $s->lots->sum(fn($l) => (float) $l->shares);
            $costBasis   = $s->lots->sum(fn($l) => (float) $l->shares * (float) $l->buy_price);
            $currVal     = $totalShares * $latestPrice;
            $gain        = $currVal - $costBasis;
            $stRows[]    = [
                $s->symbol, $s->company_name,
                round($totalShares, 4),
                $totalShares > 0 ? round($costBasis / $totalShares, 4) : 0,
                $latestPrice,
                round($currVal, 2), round($costBasis, 2), round($gain, 2),
                $costBasis > 0 ? round($gain / $costBasis * 100, 2) : 0,
            ];
        }
        $this->xlsxRows($sh, $stRows, 2);
        $sh->getColumnDimension('A')->setWidth(12);
        $sh->getColumnDimension('B')->setWidth(28);
        foreach (['C','D','E','F','G','H','I'] as $col) {
            $sh->getColumnDimension($col)->setWidth(16);
        }

        // 14. Crypto
        $sh = $spreadsheet->createSheet();
        $sh->setTitle('Crypto');
        $this->xlsxHeader($sh, [
            'Symbol','Coin Name','Total Quantity','Avg Buy Price','Latest Price','Current Value','Cost Basis','Gain/Loss','ROI (%)',
        ], 1);
        $crRows = [];
        foreach ($cryptos as $c) {
            $latestPrice = (float) ($c->latest_price ?? 0);
            $totalQty    = $c->lots->sum(fn($l) => (float) $l->quantity);
            $costBasis   = $c->lots->sum(fn($l) => (float) $l->quantity * (float) $l->buy_price);
            $currVal     = $totalQty * $latestPrice;
            $gain        = $currVal - $costBasis;
            $crRows[]    = [
                $c->symbol, $c->coin_name,
                round($totalQty, 8),
                $totalQty > 0 ? round($costBasis / $totalQty, 6) : 0,
                $latestPrice,
                round($currVal, 2), round($costBasis, 2), round($gain, 2),
                $costBasis > 0 ? round($gain / $costBasis * 100, 2) : 0,
            ];
        }
        $this->xlsxRows($sh, $crRows, 2);
        $sh->getColumnDimension('A')->setWidth(12);
        $sh->getColumnDimension('B')->setWidth(28);
        foreach (['C','D','E','F','G','H','I'] as $col) {
            $sh->getColumnDimension($col)->setWidth(16);
        }

        // 15. Investment Dividends
        $investmentDividends = InvestmentDividend::with('investment')
            ->where('budget_tracking_id', $btId)
            ->orderBy('paid_at', 'desc')
            ->get();

        $sh = $spreadsheet->createSheet();
        $sh->setTitle('Investment Dividends');
        $this->xlsxHeader($sh, ['Date', 'Investment Name', 'Type', 'Amount', 'Notes'], 1);
        $idRows = [];
        foreach ($investmentDividends as $div) {
            $idRows[] = [
                (string) $div->paid_at,
                $div->investment?->name ?? '',
                $div->investment?->type ?? '',
                (float) $div->amount,
                $div->notes ?? '',
            ];
        }
        $this->xlsxRows($sh, $idRows, 2);
        $sh->getColumnDimension('A')->setWidth(14);
        $sh->getColumnDimension('B')->setWidth(30);
        $sh->getColumnDimension('C')->setWidth(18);
        $sh->getColumnDimension('D')->setWidth(16);
        $sh->getColumnDimension('E')->setWidth(28);

        // 16. Stock Dividends
        $stockDividends = StockDividend::with('stock')
            ->where('budget_tracking_id', $btId)
            ->orderBy('paid_at', 'desc')
            ->get();

        $sh = $spreadsheet->createSheet();
        $sh->setTitle('Stock Dividends');
        $this->xlsxHeader($sh, ['Date', 'Symbol', 'Company', 'Amount', 'Notes'], 1);
        $sdRows = [];
        foreach ($stockDividends as $div) {
            $sdRows[] = [
                (string) $div->paid_at,
                $div->stock?->symbol ?? '',
                $div->stock?->company_name ?? '',
                (float) $div->amount,
                $div->notes ?? '',
            ];
        }
        $this->xlsxRows($sh, $sdRows, 2);
        $sh->getColumnDimension('A')->setWidth(14);
        $sh->getColumnDimension('B')->setWidth(12);
        $sh->getColumnDimension('C')->setWidth(28);
        $sh->getColumnDimension('D')->setWidth(16);
        $sh->getColumnDimension('E')->setWidth(28);

        // 17. Crypto Rewards
        $cryptoRewards = CryptoDividend::with('cryptoAsset')
            ->where('budget_tracking_id', $btId)
            ->orderBy('paid_at', 'desc')
            ->get();

        $sh = $spreadsheet->createSheet();
        $sh->setTitle('Crypto Rewards');
        $this->xlsxHeader($sh, ['Date', 'Symbol', 'Coin Name', 'Qty Rewarded', 'Price at Reward', 'Est. Value (PHP)', 'Notes'], 1);
        $crwRows = [];
        foreach ($cryptoRewards as $rwd) {
            $estValue = (float) $rwd->quantity_rewarded * (float) $rwd->price_at_reward;
            $crwRows[] = [
                (string) $rwd->paid_at,
                $rwd->cryptoAsset?->symbol ?? '',
                $rwd->cryptoAsset?->coin_name ?? '',
                round((float) $rwd->quantity_rewarded, 8),
                round((float) $rwd->price_at_reward, 8),
                round($estValue, 2),
                $rwd->notes ?? '',
            ];
        }
        $this->xlsxRows($sh, $crwRows, 2);
        $sh->getColumnDimension('A')->setWidth(14);
        $sh->getColumnDimension('B')->setWidth(12);
        $sh->getColumnDimension('C')->setWidth(24);
        $sh->getColumnDimension('D')->setWidth(16);
        $sh->getColumnDimension('E')->setWidth(18);
        $sh->getColumnDimension('F')->setWidth(20);
        $sh->getColumnDimension('G')->setWidth(28);

        // ── Stream XLSX ────────────────────────────────────────────────────────
        $filename = 'full_report_' . now()->format('Y_m_d') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /** Write a styled header row (dark blue bg, white bold text). */
    private function xlsxHeader(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $cols, int $row): void
    {
        $sheet->fromArray([$cols], null, 'A' . $row);

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($cols));
        $range   = 'A' . $row . ':' . $lastCol . $row;

        $sheet->getStyle($range)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    /** Write data rows with alternating row shading. */
    private function xlsxRows(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $rows, int $startRow): void
    {
        $r = $startRow;
        foreach ($rows as $row) {
            if (empty($row)) { $r++; continue; }

            $sheet->fromArray([$row], null, 'A' . $r);

            $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($row));
            $shade   = ($r % 2 === 0) ? 'F0F4FA' : 'FFFFFF';
            $sheet->getStyle('A' . $r . ':' . $lastCol . $r)
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($shade);

            $r++;
        }
    }
}
