<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Add payment method: cash | credit_card | other
            $table->string('payment_method', 20)->default('cash')->after('purchase_date');

            // Drop description if it exists
            if (Schema::hasColumn('purchases', 'description')) {
                $table->dropColumn('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('payment_method');
            $table->text('description')->nullable();
        });
    }
};
