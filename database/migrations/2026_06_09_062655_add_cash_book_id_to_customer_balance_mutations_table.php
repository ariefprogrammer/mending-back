<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_balance_mutations', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_method_id')->nullable()->after('type');
            $table->unsignedBigInteger('cash_book_id')->nullable()->after('payment_method_id');

            $table->foreign('payment_method_id')
                  ->references('id')
                  ->on('payment_methods')
                  ->onDelete('set null');

            $table->foreign('cash_book_id')
                  ->references('id')
                  ->on('outlet_cash_books')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('customer_balance_mutations', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropForeign(['cash_book_id']);
            $table->dropColumn(['payment_method_id', 'cash_book_id']);
        });
    }
};