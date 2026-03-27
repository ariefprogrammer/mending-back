<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlet_cash_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')
                  ->constrained('outlets')
                  ->onDelete('cascade');
            $table->string('name');
            $table->timestamps(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_cash_books');
    }
};
