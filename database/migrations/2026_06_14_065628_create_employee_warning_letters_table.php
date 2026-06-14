<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_warning_letters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('outlet_id');
            $table->uuid('employee_id');
            $table->date('date_effective');
            $table->text('description');               
            $table->enum('status', ['draft', 'terkirim', 'selesai'])->default('draft');

            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('created_by_employee_id')->nullable();

            $table->timestamps();

            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by_employee_id')->references('id')->on('employees')->onDelete('set null');

            $table->index(['outlet_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_warning_letters');
    }
};