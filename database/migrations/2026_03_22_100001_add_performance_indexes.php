<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for high-frequency query columns.
 * Addresses finding P-02 from the QA/Stress audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Incomes — filtered by budget_tracking_id + date range
        Schema::table('incomes', function (Blueprint $table) {
            $table->index(['budget_tracking_id', 'received_at'], 'incomes_bt_date_idx');
        });

        // Expenses — filtered by budget_tracking_id + date range
        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['budget_tracking_id', 'spent_at'], 'expenses_bt_date_idx');
        });

        // Payments — filtered by budget_tracking_id + date range
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['budget_tracking_id', 'payment_date'], 'payments_bt_date_idx');
        });

        // Module transfers — filtered by budget_tracking_id + module (GROUP BY)
        Schema::table('module_transfers', function (Blueprint $table) {
            $table->index(['budget_tracking_id', 'module'], 'transfers_bt_module_idx');
            $table->index(['budget_tracking_id', 'transfer_from'], 'transfers_bt_from_idx');
        });

        // Stock lots — joined to stocks by stock_id
        Schema::table('stock_lots', function (Blueprint $table) {
            $table->index('stock_id', 'stock_lots_stock_id_idx');
        });

        // Crypto lots — joined to crypto_assets by crypto_asset_id
        Schema::table('crypto_lots', function (Blueprint $table) {
            $table->index('crypto_asset_id', 'crypto_lots_asset_id_idx');
        });

        // Investment dividends — summed by budget_tracking_id
        Schema::table('investment_dividends', function (Blueprint $table) {
            $table->index('budget_tracking_id', 'inv_dividends_bt_idx');
        });
    }

    public function down(): void
    {
        Schema::table('incomes',             fn($t) => $t->dropIndex('incomes_bt_date_idx'));
        Schema::table('expenses',            fn($t) => $t->dropIndex('expenses_bt_date_idx'));
        Schema::table('payments',            fn($t) => $t->dropIndex('payments_bt_date_idx'));
        Schema::table('module_transfers',    fn($t) => $t->dropIndex('transfers_bt_module_idx'));
        Schema::table('module_transfers',    fn($t) => $t->dropIndex('transfers_bt_from_idx'));
        Schema::table('stock_lots',          fn($t) => $t->dropIndex('stock_lots_stock_id_idx'));
        Schema::table('crypto_lots',         fn($t) => $t->dropIndex('crypto_lots_asset_id_idx'));
        Schema::table('investment_dividends',fn($t) => $t->dropIndex('inv_dividends_bt_idx'));
    }
};
