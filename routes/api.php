<?php

use App\Http\Controllers\API\V1\Auth\AuthController;
use App\Http\Controllers\API\V1\BudgetTracking\BudgetTrackingAllocationController;
use App\Http\Controllers\API\V1\BudgetTracking\BudgetTrackingController;
use App\Http\Controllers\API\V1\BudgetTracking\BudgetTrackingTransactionController;
use App\Http\Controllers\API\V1\Budget\BudgetController;
use App\Http\Controllers\API\V1\Category\CategoryController;
use App\Http\Controllers\API\V1\Dashboard\DashboardController;
use App\Http\Controllers\API\V1\Debt\DebtController;
use App\Http\Controllers\API\V1\Expense\ExpenseController;
use App\Http\Controllers\API\V1\File\FileController;
use App\Http\Controllers\API\V1\Income\IncomeController;
use App\Http\Controllers\API\V1\MP2\MP2Controller;
use App\Http\Controllers\API\V1\Payment\PaymentController;
use App\Http\Controllers\API\V1\Purchase\PurchaseController;
use App\Http\Controllers\API\V1\Report\ReportController;
use App\Http\Controllers\API\V1\Report\ReportExportController;
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

        Route::get('module-transfers', [ModuleTransferController::class, 'index']);
        Route::post('module-transfers', [ModuleTransferController::class, 'store']);

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
