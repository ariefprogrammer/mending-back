<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('employee_id')
                  ->constrained('employees')
                  ->cascadeOnDelete();
            $table->foreignId('outlet_id')
                  ->constrained('outlets')
                  ->cascadeOnDelete();
            $table->foreignId('permission_id')
                  ->nullable()
                  ->constrained('permissions')
                  ->nullOnDelete();
            $table->boolean('allowed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_permissions');
    }
};