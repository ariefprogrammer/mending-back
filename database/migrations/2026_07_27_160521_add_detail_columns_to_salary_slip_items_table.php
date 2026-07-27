<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_slip_items', function (Blueprint $table) {
            $table->foreignId('detail_salary_component_id')->nullable()->after('salary_component_id')
                ->constrained('detail_salary_components')->nullOnDelete();
            $table->string('name')->nullable()->after('detail_salary_component_id');
            $table->enum('type', ['tunjangan', 'potongan'])->nullable()->after('name');
            $table->enum('duration', ['harian', 'bulanan'])->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('salary_slip_items', function (Blueprint $table) {
            $table->dropForeign(['detail_salary_component_id']);
            $table->dropColumn(['detail_salary_component_id', 'name', 'type', 'duration']);
        });
    }
};
