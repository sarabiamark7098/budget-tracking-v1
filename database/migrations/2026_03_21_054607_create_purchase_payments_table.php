<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            $table->foreignId('budget_tracking_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('paid_at');
            $table->unsignedInteger('installment_number'); // which installment # this is
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};
