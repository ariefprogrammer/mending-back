<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_attendance_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_attendance_id')
                  ->constrained('employee_attendances')
                  ->cascadeOnDelete();
            $table->enum('type', ['check_in', 'overtime', 'check_out']);
            $table->string('image_path');
            $table->timestamps();

            // Satu foto per jenis presensi per sesi
            $table->unique(['employee_attendance_id', 'type']);
            $table->index('employee_attendance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_attendance_images');
    }
};