<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            
            // Relasi standar ke users.id
            // Menggunakan foreignId agar otomatis mengenali kolom ID di tabel users
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->string('phone');
            $table->string('province');
            $table->string('city');
            $table->string('kecamatan');
            $table->string('kelurahan');
            $table->text('address');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlets');
    }
};
