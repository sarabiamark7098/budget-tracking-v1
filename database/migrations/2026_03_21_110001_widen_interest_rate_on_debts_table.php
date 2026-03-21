<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            // Widen from decimal(5,2) → decimal(10,4) to support any realistic annual rate
            $table->decimal('interest_rate', 10, 4)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->decimal('interest_rate', 5, 2)->default(0)->change();
        });
    }
};
