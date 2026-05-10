<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_attendances', function (Blueprint $table) {
            $table->id();
            
            $table->string('employee_id', 36); 
            $table->foreign('employee_id')
                  ->references('id')
                  ->on('employees')
                  ->cascadeOnDelete();
            
            $table->foreignId('outlet_id')
                  ->constrained('outlets')
                  ->cascadeOnDelete();
            
            $table->date('work_date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            
            $table->index(['employee_id', 'work_date']);
            $table->index(['outlet_id', 'work_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_attendances');
    }
};