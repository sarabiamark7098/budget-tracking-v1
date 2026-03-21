<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recalibration: budget_tracking_id becomes the primary foreign key for every
 * module table. user_id is retained on every table purely for attribution
 * (showing "added by <name>") but is no longer the scoping key.
 */
return new class extends Migration
{
    private array $tables = [
        'categories',
        'incomes',
        'expenses',
        'budgets',
        'debts',
        'payments',
        'investments',
        'stocks',
        'crypto_assets',
        'financial_plans',
        'financial_goals',
        'insurance_plans',
        'insurance_payments',
        'purchases',
        'mp2_plans',
        'files',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasColumn($table, 'budget_tracking_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    // Add after user_id so it sits logically next to it
                    $blueprint->foreignId('budget_tracking_id')
                        ->nullable()
                        ->after('user_id')
                        ->constrained('budget_trackings')
                        ->onDelete('cascade');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            if (Schema::hasColumn($table, 'budget_tracking_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropForeign(['budget_tracking_id']);
                    $blueprint->dropColumn('budget_tracking_id');
                });
            }
        }
    }
};
