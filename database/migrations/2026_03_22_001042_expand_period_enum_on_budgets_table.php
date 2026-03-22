<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Expand the `period` ENUM on the budgets table to include daily, weekdays, weekends.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE budgets MODIFY period ENUM('daily','weekdays','weekends','weekly','monthly','yearly') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE budgets MODIFY period ENUM('weekly','monthly','yearly') NOT NULL");
    }
};
