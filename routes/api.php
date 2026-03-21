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
use App\Http\Controllers\API\V1\Stock\StockController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // Public MP2 calculator
    Route::post('mp2/calculate', [MP2Controller::class, 'calculate']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::put('password', [AuthController::class, 'changePassword']);
        });

        Route::get('dashboard/transactions', [DashboardController::class, 'transactions']);
        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::apiResource('categories', CategoryController::class);

        // Income - custom routes BEFORE apiResource to avoid route conflict
        Route::get('incomes/monthly', [IncomeController::class, 'monthly']);
        Route::apiResource('incomes', IncomeController::class);

        // Expense - custom routes BEFORE apiResource
        Route::get('expenses/monthly', [ExpenseController::class, 'monthly']);
        Route::apiResource('expenses', ExpenseController::class);

        // Budget - custom routes BEFORE apiResource
        Route::get('budgets/summary', [BudgetController::class, 'summary']);
        Route::apiResource('budgets', BudgetController::class);

        // Debts — amortization and accrual BEFORE apiResource
        Route::get('debts/{debt}/amortization', [DebtController::class, 'amortization']);
        Route::get('debts/{debt}/accrual', [DebtController::class, 'accrual']);
        Route::apiResource('debts', DebtController::class);
        Route::apiResource('payments', PaymentController::class)->except(['update']);

        // Investment - portfolio BEFORE apiResource
        Route::get('investments/portfolio', [InvestmentController::class, 'portfolio']);
        Route::apiResource('investments', InvestmentController::class);

        // Stocks - portfolio BEFORE apiResource
        Route::get('stocks/portfolio', [StockController::class, 'portfolio']);
        Route::apiResource('stocks', StockController::class);

        // Crypto - portfolio BEFORE apiResource
        Route::get('crypto/portfolio', [CryptoController::class, 'portfolio']);
        Route::apiResource('crypto', CryptoController::class);

        Route::apiResource('financial-plans', FinancialPlanController::class);
        Route::apiResource('financial-goals', FinancialGoalController::class);
        Route::patch('financial-goals/{financialGoal}/progress', [FinancialGoalController::class, 'updateProgress']);

        Route::apiResource('insurance-plans', InsurancePlanController::class);
        Route::apiResource('insurance-payments', InsurancePaymentController::class)->except(['update']);

        Route::apiResource('purchases', PurchaseController::class);
        Route::patch('purchases/{purchase}/installment', [PurchaseController::class, 'payInstallment']);

        Route::apiResource('mp2-plans', MP2Controller::class)->except(['show']);

        Route::apiResource('files', FileController::class)->only(['index', 'store', 'destroy']);
        Route::get('files/{file}/download', [FileController::class, 'download']);

        // ─── Budget Tracking (collaborative / shared) ───────────────────────────
        Route::prefix('budget-tracking')->group(function () {
            // Core budget tracking — one per user (owned or shared)
            Route::get('/',                      [BudgetTrackingController::class, 'show']);
            Route::post('/',                     [BudgetTrackingController::class, 'store']);
            Route::put('/',                      [BudgetTrackingController::class, 'update']);
            Route::delete('/',                   [BudgetTrackingController::class, 'destroy']);

            // Join & leave via unique code
            Route::post('join',                  [BudgetTrackingController::class, 'join']);
            Route::post('leave',                 [BudgetTrackingController::class, 'leave']);

            // Summary dashboard
            Route::get('summary',                [BudgetTrackingController::class, 'summary']);

            // Consolidated member data (all modules, attributed by user)
            Route::get('consolidated',           [BudgetTrackingController::class, 'consolidated']);

            // Change history log
            Route::get('history',                [BudgetTrackingController::class, 'history']);

            // Join code management (owner only)
            Route::post('code/regenerate',       [BudgetTrackingController::class, 'regenerateCode']);

            // Member management (owner only)
            Route::delete('members/{userId}',    [BudgetTrackingController::class, 'removeMember']);

            // Budget allocations (owner manages, all members view)
            Route::get('allocations',            [BudgetTrackingAllocationController::class, 'index']);
            Route::post('allocations',           [BudgetTrackingAllocationController::class, 'store']);
            Route::put('allocations/{allocation}',    [BudgetTrackingAllocationController::class, 'update']);
            Route::delete('allocations/{allocation}', [BudgetTrackingAllocationController::class, 'destroy']);

            // Transactions (all members can add/edit own; owner can edit/delete any)
            Route::get('transactions',                    [BudgetTrackingTransactionController::class, 'index']);
            Route::post('transactions',                   [BudgetTrackingTransactionController::class, 'store']);
            Route::put('transactions/{transaction}',      [BudgetTrackingTransactionController::class, 'update']);
            Route::delete('transactions/{transaction}',   [BudgetTrackingTransactionController::class, 'destroy']);
        });

        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('income-expense', [ReportController::class, 'incomeExpense']);
            Route::get('net-worth', [ReportController::class, 'netWorth']);
            Route::get('export/csv', [ReportController::class, 'exportCsv']);
            Route::get('export/pdf', [ReportController::class, 'exportPdf']);
        });
    });
});
