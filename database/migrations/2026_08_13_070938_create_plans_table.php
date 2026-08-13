<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                              
            $table->string('slug')->unique();                    
            $table->unsignedInteger('transaction_limit')->nullable(); 
            $table->unsignedInteger('trial_days')->nullable();   
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('is_default')->default(false);       
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};