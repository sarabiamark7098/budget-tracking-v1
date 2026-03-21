<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('latest_price', 15, 4)->nullable()->after('company_name');
        });

        // Seed latest_price from existing current_price
        \Illuminate\Support\Facades\DB::statement(
            'UPDATE stocks SET latest_price = current_price WHERE current_price IS NOT NULL'
        );
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn('latest_price');
        });
    }
};
