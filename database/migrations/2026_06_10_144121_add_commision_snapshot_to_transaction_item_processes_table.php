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
        Schema::table('transaction_item_processes', function (Blueprint $blueprint) {
            $blueprint->bigInteger('commision_snapshot')
                ->nullable()
                ->after('employee_id')
                ->comment('Menyimpan snapshot nominal komisi karyawan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_item_processes', function (Blueprint $blueprint) {
            $blueprint->dropColumn('commision_snapshot');
        });
    }
};