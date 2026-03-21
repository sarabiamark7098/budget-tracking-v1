<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('interest_paid', 15, 2)->nullable()->after('amount');
            $table->decimal('principal_paid', 15, 2)->nullable()->after('interest_paid');
            $table->unsignedInteger('days_elapsed')->nullable()->after('principal_paid');
            $table->unsignedInteger('installment_number')->nullable()->after('days_elapsed');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach (['interest_paid', 'principal_paid', 'days_elapsed', 'installment_number'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
