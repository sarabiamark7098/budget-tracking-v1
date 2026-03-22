<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->decimal('shares', 15, 4);
            $table->decimal('buy_price', 15, 4);
            $table->decimal('current_price', 15, 4)->default(0);
            $table->date('purchase_date');
            $table->timestamps();
        });

        // Migrate existing stock lot data — CURRENT_TIMESTAMP is supported by both MySQL and SQLite
        DB::statement("
            INSERT INTO stock_lots (stock_id, shares, buy_price, current_price, purchase_date, created_at, updated_at)
            SELECT id, shares, buy_price, current_price, purchase_date, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            FROM stocks
            WHERE shares IS NOT NULL AND shares > 0 AND deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_lots');
    }
};
