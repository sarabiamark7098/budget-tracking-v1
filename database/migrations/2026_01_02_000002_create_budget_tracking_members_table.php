<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_tracking_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_tracking_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['owner', 'member'])->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            // One membership record per user per budget tracking
            $table->unique(['budget_tracking_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_tracking_members');
    }
};
