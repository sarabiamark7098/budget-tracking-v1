<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->decimal('total_value', 15, 2)->nullable()->after('current_value');
            $table->string('period')->nullable()->after('total_value');          // monthly, quarterly, semi_annual, annual
            $table->integer('months_of_payment')->nullable()->after('period');
            $table->decimal('amount_per_payment', 15, 2)->nullable()->after('months_of_payment');
            $table->date('date_started')->nullable()->after('amount_per_payment');
            $table->string('other_investment_title')->nullable()->after('date_started');
            $table->string('payment_status')->default('active')->after('other_investment_title'); // active, paid, done
        });
    }

    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn([
                'total_value',
                'period',
                'months_of_payment',
                'amount_per_payment',
                'date_started',
                'other_investment_title',
                'payment_status',
            ]);
        });
    }
};
