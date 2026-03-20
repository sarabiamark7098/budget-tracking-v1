<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_tracking_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_tracking_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');  // who made the change
            $table->string('action');                  // e.g. 'budget_created', 'transaction_added'
            $table->string('subject_type')->nullable(); // 'budget_tracking', 'transaction', 'allocation', 'member'
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('old_values')->nullable();    // snapshot before change
            $table->json('new_values')->nullable();    // snapshot after change
            $table->string('description');             // human-readable summary
            $table->timestamp('created_at');           // read-only log; no updated_at needed

            $table->index(['budget_tracking_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_tracking_histories');
    }
};
