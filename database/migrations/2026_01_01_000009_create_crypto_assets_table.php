<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('coin_name');
            $table->string('symbol');
            $table->string('wallet_address')->nullable();
            $table->decimal('quantity', 20, 8);
            $table->decimal('buy_price', 15, 8);
            $table->decimal('current_price', 15, 8);
            $table->date('purchase_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_assets');
    }
};
