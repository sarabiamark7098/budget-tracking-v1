<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add latest_price to crypto_assets
        Schema::table('crypto_assets', function (Blueprint $table) {
            $table->decimal('latest_price', 15, 8)->nullable()->after('symbol');
        });

        // Seed latest_price from current_price (MySQL and SQLite compatible)
        DB::statement('UPDATE crypto_assets SET latest_price = current_price WHERE current_price IS NOT NULL');

        // 2. Create crypto_lots table
        Schema::create('crypto_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crypto_asset_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 8);
            $table->decimal('buy_price', 15, 8);
            $table->date('purchase_date');
            $table->timestamps();
        });

        // 3. Migrate existing lot data — use CURRENT_TIMESTAMP / CURRENT_DATE (cross-DB compatible)
        DB::statement("
            INSERT INTO crypto_lots (crypto_asset_id, quantity, buy_price, purchase_date, created_at, updated_at)
            SELECT id, quantity, buy_price,
                   COALESCE(purchase_date, CURRENT_DATE),
                   CURRENT_TIMESTAMP,
                   CURRENT_TIMESTAMP
            FROM crypto_assets
            WHERE quantity IS NOT NULL AND quantity > 0 AND deleted_at IS NULL
        ");

        // 4. Hard-delete soft-deleted records to allow unique constraint
        DB::statement('DELETE FROM crypto_assets WHERE deleted_at IS NOT NULL');

        // 5. Drop old lot columns
        Schema::table('crypto_assets', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'buy_price', 'current_price', 'purchase_date', 'notes', 'wallet_address']);
        });

        // 6. Add unique constraint on (budget_tracking_id, symbol)
        Schema::table('crypto_assets', function (Blueprint $table) {
            $table->unique(['budget_tracking_id', 'symbol']);
        });
    }

    public function down(): void
    {
        Schema::table('crypto_assets', function (Blueprint $table) {
            $table->dropUnique(['budget_tracking_id', 'symbol']);
            $table->dropColumn('latest_price');
            $table->decimal('quantity', 15, 8)->nullable();
            $table->decimal('buy_price', 15, 8)->nullable();
            $table->decimal('current_price', 15, 8)->nullable();
            $table->date('purchase_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('wallet_address')->nullable();
        });

        Schema::dropIfExists('crypto_lots');
    }
};
