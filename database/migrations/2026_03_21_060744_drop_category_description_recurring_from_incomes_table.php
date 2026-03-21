<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            if (Schema::hasColumn('incomes', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }
            if (Schema::hasColumn('incomes', 'description'))         $table->dropColumn('description');
            if (Schema::hasColumn('incomes', 'is_recurring'))        $table->dropColumn('is_recurring');
            if (Schema::hasColumn('incomes', 'recurrence_interval')) $table->dropColumn('recurrence_interval');
            if (Schema::hasColumn('incomes', 'recurrence_end_date')) $table->dropColumn('recurrence_end_date');
        });
    }

    public function down(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->text('description')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_interval')->nullable();
            $table->date('recurrence_end_date')->nullable();
        });
    }
};
