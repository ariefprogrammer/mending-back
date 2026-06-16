<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign keys dengan DB::statement langsung agar bisa di-ignore manual
        try {
            DB::statement('ALTER TABLE service_flows DROP FOREIGN KEY service_flows_service_unit_id_foreign');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE service_flows DROP FOREIGN KEY service_flows_satuan_id_foreign');
        } catch (\Exception $e) {}

        Schema::table('service_flows', function (Blueprint $table) {
            if (Schema::hasColumn('service_flows', 'service_unit_id')) {
                $table->renameColumn('service_unit_id', 'satuan_id');
            }
        });

        Schema::table('service_flows', function (Blueprint $table) {
            $table->unsignedBigInteger('satuan_id')->nullable()->change();
        });

        Schema::table('service_flows', function (Blueprint $table) {
            $table->foreign('satuan_id')
                ->references('id')
                ->on('satuans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_flows', function (Blueprint $table) {
            $table->dropForeign(['satuan_id']);
        });

        Schema::table('service_flows', function (Blueprint $table) {
            $table->renameColumn('satuan_id', 'service_unit_id');
        });

        Schema::table('service_flows', function (Blueprint $table) {
            $table->foreign('service_unit_id')
                  ->references('id')
                  ->on('service_units')
                  ->nullOnDelete();
        });
    }
};