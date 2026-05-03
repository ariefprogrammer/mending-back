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
        // Tabel Provinsi
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index(); // Tambah index untuk pencarian nama
            $table->timestamps();
        });

        // Tabel Kabupaten
        Schema::create('kabupatens', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index(); // Tambah index
            // ForeignId otomatis membuat index untuk relasi turunan
            $table->foreignId('province_id')->constrained('provinces')->onDelete('cascade');
            $table->timestamps();
        });

        // Tabel Kecamatan
        Schema::create('kecamatans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index(); // Tambah index
            $table->foreignId('kabupaten_id')->constrained('kabupatens')->onDelete('cascade');
            $table->timestamps();
        });

        // Tabel Kelurahan
        Schema::create('kelurahans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index(); // Tambah index
            $table->foreignId('kecamatan_id')->constrained('kecamatans')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelurahans');
        Schema::dropIfExists('kecamatans');
        Schema::dropIfExists('kabupatens');
        Schema::dropIfExists('provinces');
    }
};