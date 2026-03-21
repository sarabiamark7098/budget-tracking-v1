<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_tracking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('module', ['investment', 'stock', 'crypto']);
            $table->decimal('amount', 15, 2);
            $table->decimal('transfer_fee', 15, 2)->default(0);
            $table->decimal('total', 15, 2); // amount + transfer_fee
            $table->string('note')->nullable();
            $table->date('transfer_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_transfers');
    }
};
