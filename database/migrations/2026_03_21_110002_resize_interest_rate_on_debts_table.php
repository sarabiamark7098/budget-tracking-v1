<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            // decimal(6,3) → max 999.999 — more than enough for 0.000–100.000 %
            $table->decimal('interest_rate', 6, 3)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->decimal('interest_rate', 10, 4)->default(0)->change();
        });
    }
};
