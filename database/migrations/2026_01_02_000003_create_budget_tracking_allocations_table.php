<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_tracking_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_tracking_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');                          // e.g. "Groceries", "Rent"
            $table->decimal('allocated_amount', 15, 2);
            $table->string('color', 10)->default('#6366f1');
            $table->string('icon', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_tracking_allocations');
    }
};
