<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('budget_trackings', function (Blueprint $table) {
            if (Schema::hasColumn('budget_trackings', 'end_date')) {
                $table->dropColumn('end_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('budget_trackings', function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('start_date');
        });
    }
};
