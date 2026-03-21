<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE module_transfers MODIFY COLUMN module ENUM('investment','stock','crypto','saving') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE module_transfers MODIFY COLUMN module ENUM('investment','stock','crypto') NOT NULL");
    }
};
