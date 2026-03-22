<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crypto_dividends', function (Blueprint $table) {
            $table->dropColumn('amount');
            $table->decimal('quantity_rewarded', 18, 8)->after('budget_tracking_id');
            $table->decimal('price_at_reward', 18, 8)->default(0)->after('quantity_rewarded');
        });
    }

    public function down(): void
    {
        Schema::table('crypto_dividends', function (Blueprint $table) {
            $table->dropColumn(['quantity_rewarded', 'price_at_reward']);
            $table->decimal('amount', 15, 2)->after('budget_tracking_id');
        });
    }
};
