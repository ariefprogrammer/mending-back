<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_slips', function (Blueprint $table) {
            $table->id();

            $table->uuid('employee_id');
            $table->foreign('employee_id')
                  ->references('id')
                  ->on('employees')
                  ->onDelete('cascade');

            $table->foreignId('outlet_id')
                  ->constrained('outlets')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('cash_book_id')->nullable();
            $table->foreign('cash_book_id')
                  ->references('id')
                  ->on('outlet_cash_books')
                  ->onDelete('set null');

            $table->date('period_start');
            $table->date('period_end');
            $table->bigInteger('base_salary')->default(0);
            $table->bigInteger('overtime_salary')->default(0);
            $table->bigInteger('total_commission')->default(0);
            $table->bigInteger('total_allowance')->default(0);
            $table->bigInteger('total_deduction')->default(0);
            $table->bigInteger('net_salary')->default(0);
            $table->string('status')->default('draft');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_slips');
    }
};