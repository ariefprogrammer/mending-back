<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->foreignId('cash_book_id')
                  ->nullable()
                  ->after('payment_method_id')
                  ->constrained('outlet_cash_books')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->dropForeign(['cash_book_id']);
            $table->dropColumn('cash_book_id');
        });
    }
};