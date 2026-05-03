<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->string('text_image')->nullable()->default(null)->after('name');
            $table->string('blok')->nullable()->default(null)->after('address');
            $table->string('rt')->nullable()->default(null)->after('blok');
            $table->string('rw')->nullable()->default(null)->after('rt');
        });
    }

    public function down(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn(['text_image', 'blok', 'rt', 'rw']);
        });
    }
};
