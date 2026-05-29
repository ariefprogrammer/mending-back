<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // ─── DASHBOARD ──────────────────────────────────────────
            ['module' => 'Dashboard', 'action' => 'Lihat Outlet'],
            ['module' => 'Dashboard', 'action' => 'Pindah Outlet'],

            // ─── PELANGGAN ───────────────────────────────────────────
            ['module' => 'Pelanggan', 'action' => 'Lihat pelanggan individu'],
            ['module' => 'Pelanggan', 'action' => 'Buat pelanggan individu'],
            ['module' => 'Pelanggan', 'action' => 'Ubah pelanggan individu'],
            ['module' => 'Pelanggan', 'action' => 'Lihat pelanggan rekanan'],
            ['module' => 'Pelanggan', 'action' => 'Buat pelanggan rekanan'],
            ['module' => 'Pelanggan', 'action' => 'Ubah pelanggan rekanan'],
            ['module' => 'Pelanggan', 'action' => 'Hapus pelanggan rekanan'],
            ['module' => 'Pelanggan', 'action' => 'Lihat deposit'],
            ['module' => 'Pelanggan', 'action' => 'Buat deposit'],
            ['module' => 'Pelanggan', 'action' => 'Ubah deposit'],
            ['module' => 'Pelanggan', 'action' => 'Batal deposit'],
            ['module' => 'Pelanggan', 'action' => 'Hapus deposit'],

            // ─── LAYANAN ────────────────────────────────────────────
            ['module' => 'Layanan', 'action' => 'Lihat kategori layanan'],
            ['module' => 'Layanan', 'action' => 'Buat kategori layanan'],
            ['module' => 'Layanan', 'action' => 'Ubah kategori layanan'],
            ['module' => 'Layanan', 'action' => 'Hapus kategori layanan'],
            ['module' => 'Layanan', 'action' => 'Lihat layanan'],
            ['module' => 'Layanan', 'action' => 'Buat layanan'],
            ['module' => 'Layanan', 'action' => 'Ubah layanan'],
            ['module' => 'Layanan', 'action' => 'Hapus layanan'],

            // ─── KARYAWAN ────────────────────────────────────────────
            ['module' => 'Karyawan', 'action' => 'Lihat karyawan'],
            ['module' => 'Karyawan', 'action' => 'Buat karyawan'],
            ['module' => 'Karyawan', 'action' => 'Ubah karyawan'],
            ['module' => 'Karyawan', 'action' => 'Hapus karyawan'],
            ['module' => 'Karyawan', 'action' => 'Buat upah karyawan'],
            ['module' => 'Karyawan', 'action' => 'Buat izin akses karyawan'],
            ['module' => 'Karyawan', 'action' => 'Ubah izin akses karyawan'],
            ['module' => 'Karyawan', 'action' => 'Ubah upah karyawan'],
            ['module' => 'Karyawan', 'action' => 'Lihat jabatan/divisi'],
            ['module' => 'Karyawan', 'action' => 'Buat jabatan/divisi'],
            ['module' => 'Karyawan', 'action' => 'Ubah jabatan/divisi'],
            ['module' => 'Karyawan', 'action' => 'Hapus jabatan/divisi'],

            // ─── KEHADIRAN ───────────────────────────────────────────
            ['module' => 'Kehadiran', 'action' => 'Lihat absensi karyawan lain'],
            ['module' => 'Kehadiran', 'action' => 'Keputusan permintaan'],
            ['module' => 'Kehadiran', 'action' => 'Lihat Permintaan'],
            ['module' => 'Kehadiran', 'action' => 'Absen masuk'],
            ['module' => 'Kehadiran', 'action' => 'Absen pulang'],
            ['module' => 'Kehadiran', 'action' => 'Ajukan lembur'],

            // ─── MESIN ──────────────────────────────────────────────
            ['module' => 'Mesin', 'action' => 'Lihat Mesin'],
            ['module' => 'Mesin', 'action' => 'Buat Mesin'],
            ['module' => 'Mesin', 'action' => 'Ubah Mesin'],
            ['module' => 'Mesin', 'action' => 'Hapus Mesin'],

            // ─── BAHAN ──────────────────────────────────────────────
            ['module' => 'Bahan', 'action' => 'Lihat stock opname'],
            ['module' => 'Bahan', 'action' => 'Buat stock opname'],
            ['module' => 'Bahan', 'action' => 'Ubah stock opname'],
            ['module' => 'Bahan', 'action' => 'Hapus stock opname'],
            ['module' => 'Bahan', 'action' => 'Lihat bahan'],
            ['module' => 'Bahan', 'action' => 'Buat bahan'],
            ['module' => 'Bahan', 'action' => 'Ubah bahan'],
            ['module' => 'Bahan', 'action' => 'Hapus bahan'],

            // ─── PEMASUKAN ──────────────────────────────────────────
            /* Catatan: Di gambar terdapat 2 blok Pemasukan dengan isi yang sedikit dinamis, 
               ini digabungkan agar daftarnya unik & mencakup semua checkbox yang ada */
            ['module' => 'Pemasukan', 'action' => 'Lihat kategori pemasukan'],
            ['module' => 'Pemasukan', 'action' => 'Buat kategori pemasukan'],
            ['module' => 'Pemasukan', 'action' => 'Ubah kategori pemasukan'],
            ['module' => 'Pemasukan', 'action' => 'Hapus kategori pemasukan'],
            ['module' => 'Pemasukan', 'action' => 'Batal pemasukan'],
            ['module' => 'Pemasukan', 'action' => 'Lihat pemasukan'],
            ['module' => 'Pemasukan', 'action' => 'Buat pemasukan'],
            ['module' => 'Pemasukan', 'action' => 'Ubah pemasukan'],
            ['module' => 'Pemasukan', 'action' => 'Hapus pemasukan'],

            // ─── PENGELUARAN ─────────────────────────────────────────
            ['module' => 'Pengeluaran', 'action' => 'Lihat kategori pengeluaran'],
            ['module' => 'Pengeluaran', 'action' => 'Buat kategori pengeluaran'],
            ['module' => 'Pengeluaran', 'action' => 'Ubah kategori pengeluaran'],
            ['module' => 'Pengeluaran', 'action' => 'Hapus kategori pengeluaran'],
            ['module' => 'Pengeluaran', 'action' => 'Lihat pengeluaran'],
            ['module' => 'Pengeluaran', 'action' => 'Buat pengeluaran'],
            ['module' => 'Pengeluaran', 'action' => 'Ubah pengeluaran'],
            ['module' => 'Pengeluaran', 'action' => 'Hapus pengeluaran'],

            // ─── BUKU KAS ────────────────────────────────────────────
            ['module' => 'Buku Kas', 'action' => 'Lihat buku kas'],
            ['module' => 'Buku Kas', 'action' => 'Ubah buku kas'],
            ['module' => 'Buku Kas', 'action' => 'Hapus buku kas'],
            ['module' => 'Buku Kas', 'action' => 'Buat buku kas'],
            ['module' => 'Buku Kas', 'action' => 'Pindahkan saldo buku kas'],

            // ─── PESAN MASAL ─────────────────────────────────────────
            ['module' => 'Pesan Masal', 'action' => 'Lihat pesan masal'],
            ['module' => 'Pesan Masal', 'action' => 'Buat pesan masal'],
            ['module' => 'Pesan Masal', 'action' => 'Ubah pesan masal'],
            ['module' => 'Pesan Masal', 'action' => 'Hapus pesan masal'],
            ['module' => 'Pesan Masal', 'action' => 'Batal pesan masal'],

            // ─── OUTLET ─────────────────────────────────────────────
            ['module' => 'Outlet', 'action' => 'Ubah data outlet'],
            ['module' => 'Outlet', 'action' => 'Template pesan pemberitahuan'],
            ['module' => 'Outlet', 'action' => 'Pengaturan nota/struk'],
            ['module' => 'Outlet', 'action' => 'Pengaturan outlet'],

            // ─── TRANSAKSI ──────────────────────────────────────────
            ['module' => 'Transaksi', 'action' => 'Lihat transaksi'],
            ['module' => 'Transaksi', 'action' => 'Buat transaksi'],
            ['module' => 'Transaksi', 'action' => 'Pilih waktu transaksi'],
            ['module' => 'Transaksi', 'action' => 'Batalkan pembayaran'],
            ['module' => 'Transaksi', 'action' => 'Batal transaksi'],
            ['module' => 'Transaksi', 'action' => 'Ubah transaksi'],
            ['module' => 'Transaksi', 'action' => 'Hapus transaksi'],

            // ─── LAPORAN ────────────────────────────────────────────
            ['module' => 'Laporan', 'action' => 'Lihat rangkuman laporan'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan pola transaksi'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan buku kas besar'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan transaksi layanan'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan transaksi deposit'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan transaksi pemasukan lain-lain'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan pembayaran'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan pendapatan'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan layanan'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan pengeluaran'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan komisi'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan pelanggan'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan penggunaan bahan'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan mesin'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan karyawan'],
        ];

        DB::table('permissions')->insert($permissions);
    }
}