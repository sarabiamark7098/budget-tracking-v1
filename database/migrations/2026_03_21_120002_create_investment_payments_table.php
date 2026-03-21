<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_payments');
    }
};
