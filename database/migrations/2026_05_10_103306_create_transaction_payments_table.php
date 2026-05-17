<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')
                  ->constrained('transactions')
                  ->cascadeOnDelete();
            $table->foreignId('payment_method_id')
                  ->nullable()
                  ->constrained('payment_methods')
                  ->nullOnDelete();
            $table->bigInteger('amount_paid')->default(0);
            $table->bigInteger('change_amount')->default(0);
            $table->string('status');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_payments');
    }
};