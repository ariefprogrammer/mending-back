<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revenues', function (Blueprint $table) {
            $table->foreignId('transaction_cash_book_id')
                  ->nullable()
                  ->after('cash_book_id')
                  ->constrained('transaction_cash_books')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('revenues', function (Blueprint $table) {
            $table->dropForeign(['transaction_cash_book_id']);
            $table->dropColumn('transaction_cash_book_id');
        });
    }
};