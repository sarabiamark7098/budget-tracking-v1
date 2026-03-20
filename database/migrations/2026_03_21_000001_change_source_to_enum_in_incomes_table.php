<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Valid income source values.
     * Defined here so the up/down methods stay in sync.
     */
    private const SOURCES = [
        'Compensation Income',
        'Business Income',
        'Passive Income',
        'Property Gains',
        'Other Sources',
    ];

    public function up(): void
    {
        // Nullify any existing free-text values that are not in the new enum
        DB::table('incomes')
            ->whereNotNull('source')
            ->whereNotIn('source', self::SOURCES)
            ->update(['source' => null]);

        Schema::table('incomes', function (Blueprint $table) {
            $table->enum('source', self::SOURCES)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->string('source')->nullable()->change();
        });
    }
};
