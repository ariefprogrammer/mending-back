<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_flows', function (Blueprint $table) {
            try {
                $table->dropForeign(['service_unit_id']);
            } catch (\Exception $e) {
                // Foreign key tidak ada, lanjut
            }

            $table->renameColumn('service_unit_id', 'satuan_id');
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