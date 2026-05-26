<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_cash_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_cash_book_id')->constrained('outlet_cash_books')->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->enum('type', ['in', 'out']);
            $table->bigInteger('amount');
            $table->string('description', 255)->nullable();
            $table->date('transaction_date');
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('created_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();

            $table->index('outlet_cash_book_id');
            $table->index('outlet_id');
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_cash_books');
    }
};