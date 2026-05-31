<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_cash_books', function (Blueprint $table) {
            $table->uuid('transaction_payment_id')
                  ->nullable()
                  ->after('outlet_cash_book_id');

            $table->foreign('transaction_payment_id')
                  ->references('id')
                  ->on('transaction_payments')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_cash_books', function (Blueprint $table) {
            $table->dropForeign(['transaction_payment_id']);
            $table->dropColumn('transaction_payment_id');
        });
    }
};