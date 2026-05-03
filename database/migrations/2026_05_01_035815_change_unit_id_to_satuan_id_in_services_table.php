<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->renameColumn('unit_id', 'satuan_id');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->foreign('satuan_id')
                  ->references('id')
                  ->on('satuans')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['satuan_id']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->renameColumn('satuan_id', 'unit_id');
        });
    }
};