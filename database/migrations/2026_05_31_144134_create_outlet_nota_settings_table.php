<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlet_nota_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')
                  ->unique()
                  ->constrained('outlets')
                  ->onDelete('cascade');
            $table->string('logo_path', 255)->nullable();
            $table->enum('header_alignment', ['kiri', 'tengah', 'kanan'])->default('tengah');
            $table->text('header_note')->nullable();
            $table->boolean('show_logo')->default(false);
            $table->boolean('show_nama_outlet')->default(false);
            $table->boolean('show_alamat_outlet')->default(false);
            $table->boolean('show_nama_kasir')->default(false);
            $table->boolean('show_nama_pelanggan')->default(false);
            $table->boolean('show_kategori_layanan')->default(false);
            $table->boolean('show_jumlah_potong')->default(false);
            $table->boolean('show_estimasi_selesai')->default(false);
            $table->boolean('show_parfum')->default(false);
            $table->boolean('show_qr_code')->default(false);
            $table->boolean('show_powered_by')->default(false);
            $table->boolean('show_header_fisik')->default(false);
            $table->boolean('show_footer_fisik')->default(false);
            $table->boolean('auto_potong_nota')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_nota_settings');
    }
};