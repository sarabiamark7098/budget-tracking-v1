<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->date('spent_at');
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_interval', ['daily', 'weekly', 'monthly', 'yearly'])->nullable();
            $table->date('recurrence_end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
