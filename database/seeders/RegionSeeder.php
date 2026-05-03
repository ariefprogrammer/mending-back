<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. PROVINSI
        // Kita simpan ID-nya ke dalam variabel setelah insert
        $provinceId = DB::table('provinces')->insertGetId([
            'name' => 'LAMPUNG',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. KABUPATEN
        // Kita ambil beberapa contoh kabupaten dan simpan ID-nya untuk relasi ke kecamatan
        $kabLampungTengahId = DB::table('kabupatens')->insertGetId([
            'province_id' => $provinceId,
            'name' => 'LAMPUNG TENGAH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kabBandarLampungId = DB::table('kabupatens')->insertGetId([
            'province_id' => $provinceId,
            'name' => 'BANDAR LAMPUNG',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Tambah kabupaten lainnya (tanpa simpan ID karena tidak kita buat turunannya di sini)
        DB::table('kabupatens')->insert([
            ['province_id' => $provinceId, 'name' => 'LAMPUNG UTARA', 'created_at' => now()],
            ['province_id' => $provinceId, 'name' => 'LAMPUNG SELATAN', 'created_at' => now()],
            ['province_id' => $provinceId, 'name' => 'METRO', 'created_at' => now()],
        ]);

        // 3. KECAMATAN
        // Masuk ke Lampung Tengah
        $kecTerbanggiBesarId = DB::table('kecamatans')->insertGetId([
            'kabupaten_id' => $kabLampungTengahId,
            'name' => 'TERBANGGI BESAR',
            'created_at' => now(),
        ]);

        $kecGunungSugihId = DB::table('kecamatans')->insertGetId([
            'kabupaten_id' => $kabLampungTengahId,
            'name' => 'GUNUNG SUGIH',
            'created_at' => now(),
        ]);

        // Masuk ke Bandar Lampung
        DB::table('kecamatans')->insert([
            ['kabupaten_id' => $kabBandarLampungId, 'name' => 'KEDATON', 'created_at' => now()],
            ['kabupaten_id' => $kabBandarLampungId, 'name' => 'RAJABASA', 'created_at' => now()],
        ]);

        // 4. KELURAHAN
        // Masuk ke Terbanggi Besar
        DB::table('kelurahans')->insert([
            [
                'kecamatan_id' => $kecTerbanggiBesarId, 
                'name' => 'BANDAR JAYA BARAT', 
                'created_at' => now()
            ],
            [
                'kecamatan_id' => $kecTerbanggiBesarId, 
                'name' => 'BANDAR JAYA TIMUR', 
                'created_at' => now()
            ],
            [
                'kecamatan_id' => $kecTerbanggiBesarId, 
                'name' => 'YUKUM JAYA', 
                'created_at' => now()
            ],
        ]);

        // Masuk ke Gunung Sugih
        DB::table('kelurahans')->insert([
            [
                'kecamatan_id' => $kecGunungSugihId, 
                'name' => 'GUNUNG SUGIH RAYA', 
                'created_at' => now()
            ],
            [
                'kecamatan_id' => $kecGunungSugihId, 
                'name' => 'KOMERING AGUNG', 
                'created_at' => now()
            ],
        ]);
    }
}