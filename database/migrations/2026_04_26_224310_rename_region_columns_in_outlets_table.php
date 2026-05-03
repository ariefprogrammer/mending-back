<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->renameColumn('province', 'province_id');
            $table->renameColumn('city', 'kabupaten_id');
            $table->renameColumn('kecamatan', 'kecamatan_id');
            $table->renameColumn('kelurahan', 'kelurahan_id');
        });

        Schema::table('outlets', function (Blueprint $table) {
            $table->unsignedBigInteger('province_id')->change();
            $table->unsignedBigInteger('kabupaten_id')->change();
            $table->unsignedBigInteger('kecamatan_id')->change();
            $table->unsignedBigInteger('kelurahan_id')->change();

            $table->foreign('province_id')->references('id')->on('provinces');
            $table->foreign('kabupaten_id')->references('id')->on('kabupatens');
            $table->foreign('kecamatan_id')->references('id')->on('kecamatans');
            $table->foreign('kelurahan_id')->references('id')->on('kelurahans');
        });
    }

    public function down(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['kabupaten_id']);
            $table->dropForeign(['kecamatan_id']);
            $table->dropForeign(['kelurahan_id']);

            $table->renameColumn('province_id', 'province');
            $table->renameColumn('kabupaten_id', 'city');
            $table->renameColumn('kecamatan_id', 'kecamatan');
            $table->renameColumn('kelurahan_id', 'kelurahan');
        });
    }
};
