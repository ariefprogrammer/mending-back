<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_components', function (Blueprint $table) {
            $table->dropForeign(['outlet_id']);
            $table->dropColumn(['outlet_id', 'code', 'type']);

            $table->foreignUuid('employee_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('employees')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('salary_components', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');

            $table->foreignId('outlet_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('outlets')
                  ->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('type');
        });
    }
};