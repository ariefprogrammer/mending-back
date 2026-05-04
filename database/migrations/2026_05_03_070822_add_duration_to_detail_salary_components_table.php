<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_salary_components', function (Blueprint $table) {
            $table->string('duration')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('detail_salary_components', function (Blueprint $table) {
            $table->dropColumn('duration');
        });
    }
};