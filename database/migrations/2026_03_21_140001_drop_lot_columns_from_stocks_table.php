<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['shares', 'buy_price', 'current_price', 'purchase_date', 'notes']);
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('shares', 15, 4)->nullable();
            $table->decimal('buy_price', 15, 4)->nullable();
            $table->decimal('current_price', 15, 4)->nullable();
            $table->date('purchase_date')->nullable();
            $table->text('notes')->nullable();
        });
    }
};
