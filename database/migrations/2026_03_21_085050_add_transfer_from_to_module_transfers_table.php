<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_transfers', function (Blueprint $table) {
            $table->enum('transfer_from', ['income', 'investment', 'stock', 'crypto'])->default('income')->after('module');
        });
    }

    public function down(): void
    {
        Schema::table('module_transfers', function (Blueprint $table) {
            $table->dropColumn('transfer_from');
        });
    }
};
