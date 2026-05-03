<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')
                  ->constrained('outlets')
                  ->onDelete('cascade');
            
            $table->string('service_code')->index();
            $table->string('name');
            
            $table->foreignId('outlet_service_category_id')
                  ->constrained('outlet_service_categories')
                  ->onDelete('cascade');

            $table->string('satuan'); 
            $table->bigInteger('price')->default(0);
            $table->string('duration_unit'); 
            $table->integer('duration')->default(0);
            $table->integer('minimum_qty')->default(1);
            
            $table->timestamps();

            $table->index(['outlet_id', 'service_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};