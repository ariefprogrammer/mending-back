<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')
                  ->constrained('outlets')
                  ->cascadeOnDelete();
            $table->foreignId('cash_book_id')
                  ->nullable()
                  ->constrained('outlet_cash_books')
                  ->nullOnDelete();
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('external_outlet_cost_categories')
                  ->nullOnDelete();
            $table->string('name');
            $table->string('unit_name');
            $table->decimal('quantity', 10, 2);
            $table->decimal('price', 15, 2);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costs');
    }
};