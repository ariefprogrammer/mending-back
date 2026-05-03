<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')
                  ->constrained('outlets')
                  ->onDelete('cascade');
            
            $table->string('customer_type')->default('regular'); 
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('url_address')->nullable(); 
            
            $table->bigInteger('balance')->default(0);
            
            $table->timestamps();
            
            $table->index(['outlet_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
