<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add new JSON column alongside existing string column
        Schema::table('insurance_plans', function (Blueprint $table) {
            $table->json('coverage_types')->nullable()->after('coverage_type');
            $table->text('notes')->nullable()->after('policy_number');
        });

        // Migrate existing string values into JSON array
        DB::statement('UPDATE insurance_plans SET coverage_types = JSON_ARRAY(coverage_type) WHERE coverage_type IS NOT NULL');

        // Drop old columns and rename new one
        Schema::table('insurance_plans', function (Blueprint $table) {
            $table->dropColumn(['coverage_type', 'next_payment_date', 'description']);
        });

        Schema::table('insurance_plans', function (Blueprint $table) {
            $table->renameColumn('coverage_types', 'coverage_type');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_plans', function (Blueprint $table) {
            $table->string('coverage_type_old')->nullable()->after('coverage_type');
            $table->date('next_payment_date')->nullable();
            $table->text('description')->nullable();
        });

        DB::statement("UPDATE insurance_plans SET coverage_type_old = JSON_UNQUOTE(JSON_EXTRACT(coverage_type, '$[0]')) WHERE coverage_type IS NOT NULL");

        Schema::table('insurance_plans', function (Blueprint $table) {
            $table->dropColumn(['coverage_type', 'notes']);
        });

        Schema::table('insurance_plans', function (Blueprint $table) {
            $table->renameColumn('coverage_type_old', 'coverage_type');
        });
    }
};
