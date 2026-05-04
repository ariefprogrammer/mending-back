<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('outlet_id')
                  ->nullable()
                  ->constrained('outlets')
                  ->nullOnDelete();
            $table->foreignId('role_id')
                  ->nullable()
                  ->constrained('outlet_employee_roles')
                  ->nullOnDelete();
            $table->string('employee_code')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('default_base_salary', 15, 2)->default(0);
            $table->decimal('overtime_salary_per_hour', 15, 2)->default(0);
            $table->string('ktp_image_url')->nullable();
            $table->string('npwp_image_url')->nullable();
            $table->string('bpjs_kesehatan_image_url')->nullable();
            $table->string('bpjs_ketenagakerjaan_image_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};