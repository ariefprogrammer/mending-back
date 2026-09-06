<?php

namespace App\Console\Commands;

use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Province;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportWilayahData extends Command
{
    protected $signature = 'wilayah:import';

    protected $description = 'Import data wilayah dari tabel staging "wilayah" (hasil import wilayah.sql) ke tabel provinces, kabupatens, kecamatans, kelurahans';

    public function handle(): int
    {
        if (! Schema::hasTable('wilayah')) {
            $this->error('Tabel staging "wilayah" tidak ditemukan. Import wilayah.sql terlebih dahulu.');

            return self::FAILURE;
        }

        if (! $this->confirm('Ini akan MENGHAPUS seluruh data di provinces, kabupatens, kecamatans, dan kelurahans lalu mengganti dengan data baru. Lanjutkan?')) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        $this->info('Membaca data dari tabel staging...');

        $provinsiRows = [];
        $kabupatenRows = [];
        $kecamatanRows = [];
        $kelurahanRows = [];

        DB::table('wilayah')->orderBy('kode')->cursor()->each(function ($row) use (&$provinsiRows, &$kabupatenRows, &$kecamatanRows, &$kelurahanRows) {
            $dotCount = substr_count($row->kode, '.');

            match ($dotCount) {
                0 => $provinsiRows[] = $row,
                1 => $kabupatenRows[] = $row,
                2 => $kecamatanRows[] = $row,
                3 => $kelurahanRows[] = $row,
                default => null,
            };
        });

        $this->info(sprintf(
            'Ditemukan: %d provinsi, %d kabupaten, %d kecamatan, %d kelurahan.',
            count($provinsiRows),
            count($kabupatenRows),
            count($kecamatanRows),
            count($kelurahanRows)
        ));

        Schema::disableForeignKeyConstraints();
        Kelurahan::truncate();
        Kecamatan::truncate();
        Kabupaten::truncate();
        Province::truncate();
        Schema::enableForeignKeyConstraints();

        // 1. Provinsi
        $this->info('Mengimpor provinsi...');
        $this->bulkInsert('provinces', array_map(fn ($row) => [
            'code' => $row->kode,
            'name' => $row->nama,
        ], $provinsiRows));

        $provinceMap = Province::pluck('id', 'code')->all();

        // 2. Kabupaten
        $this->info('Mengimpor kabupaten...');
        $this->bulkInsert('kabupatens', array_map(function ($row) use ($provinceMap) {
            $parentCode = substr($row->kode, 0, strrpos($row->kode, '.'));

            return [
                'province_id' => $provinceMap[$parentCode] ?? null,
                'code' => $row->kode,
                'name' => $row->nama,
            ];
        }, $kabupatenRows));

        $kabupatenMap = Kabupaten::pluck('id', 'code')->all();

        // 3. Kecamatan
        $this->info('Mengimpor kecamatan...');
        $this->bulkInsert('kecamatans', array_map(function ($row) use ($kabupatenMap) {
            $parentCode = substr($row->kode, 0, strrpos($row->kode, '.'));

            return [
                'kabupaten_id' => $kabupatenMap[$parentCode] ?? null,
                'code' => $row->kode,
                'name' => $row->nama,
            ];
        }, $kecamatanRows));

        $kecamatanMap = Kecamatan::pluck('id', 'code')->all();

        // 4. Kelurahan
        $this->info('Mengimpor kelurahan...');
        $this->bulkInsert('kelurahans', array_map(function ($row) use ($kecamatanMap) {
            $parentCode = substr($row->kode, 0, strrpos($row->kode, '.'));

            return [
                'kecamatan_id' => $kecamatanMap[$parentCode] ?? null,
                'code' => $row->kode,
                'name' => $row->nama,
            ];
        }, $kelurahanRows));

        $this->info('Selesai.');

        return self::SUCCESS;
    }

    private function bulkInsert(string $table, array $rows): void
    {
        $bar = $this->output->createProgressBar(count($rows));

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table($table)->insert($chunk);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
    }
}