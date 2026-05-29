<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_book_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')
                ->constrained('outlets')
                ->cascadeOnDelete();
            $table->foreignId('payment_method_id')
                ->constrained('payment_methods')
                ->cascadeOnDelete(); 
            $table->foreignId('cash_book_id')
                ->constrained('outlet_cash_books')
                ->cascadeOnDelete(); 
            $table->timestamps();

            $table->index('outlet_id');
            $table->index('payment_method_id');
            $table->index('cash_book_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_book_mappings');
    }
};
