<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('customers');

        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('outlet_id')
                  ->nullable()
                  ->constrained('outlets')
                  ->cascadeOnDelete();
            $table->string('customer_type')->nullable();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('url_address')->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')
                  ->nullable()
                  ->constrained('outlets')
                  ->cascadeOnDelete();
            $table->string('customer_type')->nullable();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('url_address')->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();
        });
    }
};