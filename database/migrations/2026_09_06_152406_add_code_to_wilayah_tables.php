<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id');
        });

        Schema::table('kabupatens', function (Blueprint $table) {
            $table->string('code')->nullable()->after('province_id');
        });

        Schema::table('kecamatans', function (Blueprint $table) {
            $table->string('code')->nullable()->after('kabupaten_id');
        });

        Schema::table('kelurahans', function (Blueprint $table) {
            $table->string('code')->nullable()->after('kecamatan_id');
        });
    }

    public function down(): void
    {
        Schema::table('provinces', fn (Blueprint $table) => $table->dropColumn('code'));
        Schema::table('kabupatens', fn (Blueprint $table) => $table->dropColumn('code'));
        Schema::table('kecamatans', fn (Blueprint $table) => $table->dropColumn('code'));
        Schema::table('kelurahans', fn (Blueprint $table) => $table->dropColumn('code'));
    }
};