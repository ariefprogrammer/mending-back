<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Hapus foreign key lama yang salah ke units
            $table->dropForeign(['satuan_id']);

            // Tambahkan foreign key baru yang benar ke satuans
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

            // Rollback ke foreign key lama
            $table->foreign('satuan_id')
                  ->references('id')
                  ->on('units')
                  ->nullOnDelete();
        });
    }
};