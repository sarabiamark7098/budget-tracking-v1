<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            if (Schema::hasColumn('budgets', 'end_date')) {
                $table->dropColumn('end_date');
            }
            if (Schema::hasColumn('budgets', 'alert_threshold')) {
                $table->dropColumn('alert_threshold');
            }
            if (Schema::hasColumn('budgets', 'description')) {
                $table->dropColumn('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('start_date');
            $table->integer('alert_threshold')->nullable()->after('end_date');
            $table->text('description')->nullable()->after('alert_threshold');
        });
    }
};
