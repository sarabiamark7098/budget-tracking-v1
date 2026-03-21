<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            // Personal debt mode
            $table->enum('personal_mode', ['shop_pay_later', 'pay_installment'])
                  ->nullable()
                  ->after('type');

            // Business: borrower name
            $table->string('borrower_name')->nullable()->after('business_name');

            // Pay-installment fields
            $table->unsignedInteger('months_to_pay')->nullable()->after('borrower_name');
            $table->decimal('monthly_payment', 15, 2)->nullable()->after('months_to_pay');

            // Drop unused columns
            if (Schema::hasColumn('debts', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('debts', 'due_date')) {
                $table->dropColumn('due_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            foreach (['personal_mode', 'borrower_name', 'months_to_pay', 'monthly_payment'] as $col) {
                if (Schema::hasColumn('debts', $col)) {
                    $table->dropColumn($col);
                }
            }
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
        });
    }
};
