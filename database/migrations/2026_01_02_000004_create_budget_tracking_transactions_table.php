<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_tracking_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_tracking_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');  // who added it
            $table->unsignedBigInteger('budget_tracking_allocation_id')->nullable();
            $table->foreign('budget_tracking_allocation_id', 'bt_transactions_allocation_fk')
                  ->references('id')->on('budget_tracking_allocations')->onDelete('set null');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['income', 'expense']);
            $table->string('title');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->date('date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_tracking_transactions');
    }
};
