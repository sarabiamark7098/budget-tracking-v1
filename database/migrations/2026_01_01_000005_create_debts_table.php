<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('lender_name');
            $table->decimal('amount', 15, 2);
            $table->decimal('remaining_balance', 15, 2);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'paid', 'overdue'])->default('active');
            $table->enum('type', ['personal', 'business'])->default('personal');
            $table->string('business_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
