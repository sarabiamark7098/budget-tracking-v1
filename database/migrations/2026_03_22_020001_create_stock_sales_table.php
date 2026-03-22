<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('budget_tracking_id')->index();
            $table->decimal('shares_sold', 12, 4);
            $table->decimal('sell_price', 12, 4);
            $table->decimal('proceeds', 15, 2);
            $table->date('sold_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_sales');
    }
};
