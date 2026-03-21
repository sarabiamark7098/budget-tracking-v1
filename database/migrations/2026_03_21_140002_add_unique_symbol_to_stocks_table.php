<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hard-delete all soft-deleted stocks (pre-restructure legacy rows, lots already migrated)
        \Illuminate\Support\Facades\DB::statement('DELETE FROM stocks WHERE deleted_at IS NOT NULL');

        Schema::table('stocks', function (Blueprint $table) {
            $table->unique(['budget_tracking_id', 'symbol']);
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropUnique(['budget_tracking_id', 'symbol']);
        });
    }
};
