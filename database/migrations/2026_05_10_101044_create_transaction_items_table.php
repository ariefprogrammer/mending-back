<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')
                  ->constrained('transactions')
                  ->cascadeOnDelete();
            $table->foreignId('service_id')
                  ->nullable()
                  ->constrained('services')
                  ->nullOnDelete();
            $table->float('qty')->default(0);
            $table->bigInteger('price')->default(0);
            $table->bigInteger('subtotal')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};