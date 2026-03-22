<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crypto_sales', function (Blueprint $table) {
            $table->decimal('sell_fee', 15, 2)->default(0)->after('sell_price');
        });
    }

    public function down(): void
    {
        Schema::table('crypto_sales', function (Blueprint $table) {
            $table->dropColumn('sell_fee');
        });
    }
};
