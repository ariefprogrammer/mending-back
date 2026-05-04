<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('employee_salary_components', 'detail_salary_components');
    }

    public function down(): void
    {
        Schema::rename('detail_salary_components', 'employee_salary_components');
    }
};