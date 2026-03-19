<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->decimal('total_cost', 15, 2);
            $table->boolean('is_installment')->default(false);
            $table->integer('installment_count')->nullable();
            $table->decimal('installment_amount', 15, 2)->nullable();
            $table->integer('installments_paid')->default(0);
            $table->date('purchase_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
