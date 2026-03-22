<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE module_transfers MODIFY COLUMN transfer_from ENUM('income','investment','stock','crypto','saving') NOT NULL DEFAULT 'income'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE module_transfers MODIFY COLUMN transfer_from ENUM('income','investment','stock','crypto') NOT NULL DEFAULT 'income'");
        }
    }
};
