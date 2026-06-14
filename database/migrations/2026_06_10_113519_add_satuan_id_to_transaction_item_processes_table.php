<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_item_processes', function (Blueprint $table) {
            $table->foreignId('satuan_id')
                  ->nullable()
                  ->after('pieces')
                  ->constrained('satuans')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaction_item_processes', function (Blueprint $table) {
            $table->dropForeign(['satuan_id']);
            $table->dropColumn('satuan_id');
        });
    }
};