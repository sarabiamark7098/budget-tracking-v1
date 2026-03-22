<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'saving' to the transfer_from enum so saving fund can be a transfer source
        DB::statement("ALTER TABLE module_transfers MODIFY COLUMN transfer_from ENUM('income','investment','stock','crypto','saving') NOT NULL DEFAULT 'income'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE module_transfers MODIFY COLUMN transfer_from ENUM('income','investment','stock','crypto') NOT NULL DEFAULT 'income'");
    }
};
