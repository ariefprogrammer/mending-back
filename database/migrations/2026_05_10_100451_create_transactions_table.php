<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('outlet_id')
                  ->constrained('outlets')
                  ->cascadeOnDelete();
            $table->foreignUuid('customer_id')
                  ->nullable()
                  ->constrained('customers')
                  ->nullOnDelete();
            $table->string('transaction_code')->unique();
            $table->string('status');
            $table->string('payment_status');
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('discount')->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('grand_total')->default(0);
            $table->string('pickup_rak_info')->nullable();
            $table->integer('total_packaging_qty')->default(0);
            $table->timestampTz('estimated_completion')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};