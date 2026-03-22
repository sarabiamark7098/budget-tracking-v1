<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crypto_asset_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('budget_tracking_id')->index();
            $table->decimal('quantity_sold', 18, 8);
            $table->decimal('sell_price', 18, 8);
            $table->decimal('proceeds', 15, 2);
            $table->date('sold_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_sales');
    }
};
