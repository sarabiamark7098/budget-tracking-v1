<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_lots', function (Blueprint $table) {
            $table->dropColumn('current_price');
        });
    }

    public function down(): void
    {
        Schema::table('stock_lots', function (Blueprint $table) {
            $table->decimal('current_price', 15, 4)->nullable()->after('buy_price');
        });
    }
};
