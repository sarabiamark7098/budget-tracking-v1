<?php

use App\Http\Controllers\API\V1\Auth\AuthController;
use App\Http\Controllers\API\V1\BudgetTracking\BudgetTrackingAllocationController;
use App\Http\Controllers\API\V1\BudgetTracking\BudgetTrackingController;
use App\Http\Controllers\API\V1\BudgetTracking\BudgetTrackingTransactionController;
use App\Http\Controllers\API\V1\Budget\BudgetController;
use App\Http\Controllers\API\V1\Category\CategoryController;
use App\Http\Controllers\API\V1\Crypto\CryptoController;
use App\Http\Controllers\API\V1\Dashboard\DashboardController;
use App\Http\Controllers\API\V1\Debt\DebtController;
use App\Http\Controllers\API\V1\Expense\ExpenseController;
use App\Http\Controllers\API\V1\File\FileController;
use App\Http\Controllers\API\V1\Income\IncomeController;
use App\Http\Controllers\API\V1\Insurance\InsurancePaymentController;
use App\Http\Controllers\API\V1\Insurance\InsurancePlanController;
use App\Http\Controllers\API\V1\Investment\InvestmentController;
use App\Http\Controllers\API\V1\MP2\MP2Controller;
use App\Http\Controllers\API\V1\Payment\PaymentController;
use App\Http\Controllers\API\V1\Plan\FinancialGoalController;
use App\Http\Controllers\API\V1\Plan\FinancialPlanController;
use App\Http\Controllers\API\V1\Purchase\PurchaseController;
use App\Http\Controllers\API\V1\Report\ReportController;
use App\Http\Controllers\API\V1\Report\ReportExportController;
use App\Http\Controllers\API\V1\Stock\StockController;
use App\Http\Controllers\API\V1\Transfer\ModuleTransferController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes — rate-limited to prevent brute-force (S-01 fix: 10 req/min)
    Route::middleware('throttle:10,1')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('register', [AuthController::class, 'register']);
            Route::post('login', [AuthController::class, 'login']);
        });

        // Public MP2 calculator
        Route::post('mp2/calculate', [MP2Controller::class, 'calculate']);
    });

    // Session probe — no auth middleware so an unauthenticated boot check returns 200,
    // not 401, avoiding a spurious red browser console error on every fresh page load.
    Route::get('auth/me', [AuthController::class, 'me']);

    // Protected routes — authentication only
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::put('password', [AuthController::class, 'changePassword']);
        });

        // ─── Budget Tracking setup (no tracker required — this is where you get one) ──
        Route::prefix('budget-tracking')->group(function () {
            Route::get('/',                   [BudgetTrackingController::class, 'show']);
            Route::post('/',                  [BudgetTrackingController::class, 'store']);
            Route::put('/',                   [BudgetTrackingController::class, 'update']);
            Route::delete('/',                [BudgetTrackingController::class, 'destroy']);
            Route::post('join',               [BudgetTrackingController::class, 'join']);
            Route::post('leave',              [BudgetTrackingController::class, 'leave']);
            Route::patch('archive',           [BudgetTrackingController::class, 'archive']);
            Route::post('code/regenerate',    [BudgetTrackingController::class, 'regenerateCode']);
            Route::delete('members/{userId}', [BudgetTrackingController::class, 'removeMember']);

            // These require an active tracker — apply middleware only here
            Route::middleware('require.budget_tracking')->group(function () {
                Route::get('summary',     [BudgetTrackingController::class, 'summary']);
                Route::get('consolidated',[BudgetTrackingController::class, 'consolidated']);
                Route::get('history',     [BudgetTrackingController::class, 'history']);

                Route::get('allocations',                 [BudgetTrackingAllocationController::class, 'index']);
                Route::post('allocations',                [BudgetTrackingAllocationController::class, 'store']);
                Route::put('allocations/{allocation}',    [BudgetTrackingAllocationController::class, 'update']);
                Route::delete('allocations/{allocation}', [BudgetTrackingAllocationController::class, 'destroy']);

                Route::get('transactions',                  [BudgetTrackingTransactionController::class, 'index']);
                Route::post('transactions',                 [BudgetTrackingTransactionController::class, 'store']);
                Route::put('transactions/{transaction}',    [BudgetTrackingTransactionController::class, 'update']);
                Route::delete('transactions/{transaction}', [BudgetTrackingTransactionController::class, 'destroy']);
            });
        });
    });

    // Protected routes — authentication + active budget tracker required
    Route::middleware(['auth:sanctum', 'require.budget_tracking'])->group(function () {
        Route::get('dashboard/transactions', [DashboardController::class, 'transactions']);
        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::apiResource('categories', CategoryController::class);

        Route::get('incomes/monthly', [IncomeController::class, 'monthly']);
        Route::apiResource('incomes', IncomeController::class);

        Route::get('expenses/monthly', [ExpenseController::class, 'monthly']);
        Route::apiResource('expenses', ExpenseController::class);

        Route::get('budgets/summary', [BudgetController::class, 'summary']);
        Route::apiResource('budgets', BudgetController::class);

        Route::get('debts/{debt}/balance', [DebtController::class, 'balance']);
        Route::post('debts/{debt}/pay', [DebtController::class, 'pay']);
        Route::apiResource('debts', DebtController::class);
        Route::apiResource('payments', PaymentController::class)->except(['update']);

        Route::get('investments/portfolio', [InvestmentController::class, 'portfolio']);
        Route::get('investments/{investment}/payments', [InvestmentController::class, 'getPayments']);
        Route::post('investments/{investment}/payments', [InvestmentController::class, 'storePayment']);
        Route::patch('investments/{investment}/done', [InvestmentController::class, 'markDone']);
        Route::get('investments/{investment}/dividends', [InvestmentController::class, 'getDividends']);
        Route::post('investments/{investment}/dividends', [InvestmentController::class, 'storeDividend']);
        Route::apiResource('investments', InvestmentController::class);

        Route::get('stocks/portfolio', [StockController::class, 'portfolio']);
        Route::get('stocks/{stock}/lots', [StockController::class, 'getLots']);
        Route::post('stocks/{stock}/lots', [StockController::class, 'storeLot']);
        Route::patch('stocks/{stock}/price', [StockController::class, 'updateLatestPrice']);
        Route::post('stocks/{stock}/sell', [StockController::class, 'sell']);
        Route::get('stocks/{stock}/dividends', [StockController::class, 'getDividends']);
        Route::post('stocks/{stock}/dividends', [StockController::class, 'storeDividend']);
        Route::apiResource('stocks', StockController::class);

        Route::get('crypto/portfolio', [CryptoController::class, 'portfolio']);
        Route::get('crypto/{crypto}/lots', [CryptoController::class, 'getLots']);
        Route::post('crypto/{crypto}/lots', [CryptoController::class, 'storeLot']);
        Route::patch('crypto/{crypto}/price', [CryptoController::class, 'updateLatestPrice']);
        Route::post('crypto/{crypto}/sell', [CryptoController::class, 'sell']);
        Route::get('crypto/{crypto}/dividends', [CryptoController::class, 'getDividends']);
        Route::post('crypto/{crypto}/dividends', [CryptoController::class, 'storeDividend']);
        Route::apiResource('crypto', CryptoController::class);

        Route::get('module-transfers', [ModuleTransferController::class, 'index']);
        Route::post('module-transfers', [ModuleTransferController::class, 'store']);

        Route::apiResource('financial-plans', FinancialPlanController::class);
        Route::apiResource('financial-goals', FinancialGoalController::class);
        Route::patch('financial-goals/{financialGoal}/progress', [FinancialGoalController::class, 'updateProgress']);

        Route::post('insurance-plans/{insurancePlan}/pay', [InsurancePlanController::class, 'pay']);
        Route::get('insurance-plans/{insurancePlan}/payments', [InsurancePlanController::class, 'getPayments']);
        Route::apiResource('insurance-plans', InsurancePlanController::class);
        Route::apiResource('insurance-payments', InsurancePaymentController::class)->except(['update']);

        Route::get('purchases/summary', [PurchaseController::class, 'summary']);
        Route::apiResource('purchases', PurchaseController::class);
        Route::patch('purchases/{purchase}/installment', [PurchaseController::class, 'payInstallment']);

        Route::apiResource('mp2-plans', MP2Controller::class)->except(['show']);

        Route::apiResource('files', FileController::class)->only(['index', 'store', 'destroy']);
        Route::get('files/{file}/download', [FileController::class, 'download']);

        Route::prefix('reports')->group(function () {
            Route::get('income-expense', [ReportController::class, 'incomeExpense']);
            Route::get('net-worth', [ReportController::class, 'netWorth']);
            Route::get('export/csv',   [ReportController::class, 'exportCsv']);
            Route::get('export/excel', [ReportController::class, 'exportCsv']); // alias
            Route::get('export/pdf', [ReportController::class, 'exportPdf']);

            // Queue-based async export (P-05)
            Route::post('export/queue',                       [ReportExportController::class, 'queue']);
            Route::get('export/{exportId}/status',            [ReportExportController::class, 'status']);
            Route::get('export/{exportId}/download',          [ReportExportController::class, 'download']);
        });
    });
});
