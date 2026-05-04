<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // ─── LAPORAN ────────────────────────────────────────────
            ['module' => 'Laporan', 'action' => 'Lihat rangkuman laporan'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan buku kas'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan pemasukan'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan pengeluaran'],
            ['module' => 'Laporan', 'action' => 'Lihat laporan transaksi'],
            ['module' => 'Laporan', 'action' => 'Export laporan'],

            // ─── TRANSAKSI ──────────────────────────────────────────
            ['module' => 'Transaksi', 'action' => 'Lihat daftar transaksi'],
            ['module' => 'Transaksi', 'action' => 'Buat transaksi baru'],
            ['module' => 'Transaksi', 'action' => 'Edit transaksi'],
            ['module' => 'Transaksi', 'action' => 'Hapus transaksi'],
            ['module' => 'Transaksi', 'action' => 'Proses pembayaran'],
            ['module' => 'Transaksi', 'action' => 'Cetak struk'],
            ['module' => 'Transaksi', 'action' => 'Beri diskon'],

            // ─── PEMASUKAN ──────────────────────────────────────────
            ['module' => 'Pemasukan', 'action' => 'Lihat daftar pemasukan'],
            ['module' => 'Pemasukan', 'action' => 'Tambah pemasukan'],
            ['module' => 'Pemasukan', 'action' => 'Edit pemasukan'],
            ['module' => 'Pemasukan', 'action' => 'Hapus pemasukan'],
            ['module' => 'Pemasukan', 'action' => 'Kelola kategori pemasukan'],

            // ─── PENGELUARAN ─────────────────────────────────────────
            ['module' => 'Pengeluaran', 'action' => 'Lihat daftar pengeluaran'],
            ['module' => 'Pengeluaran', 'action' => 'Tambah pengeluaran'],
            ['module' => 'Pengeluaran', 'action' => 'Edit pengeluaran'],
            ['module' => 'Pengeluaran', 'action' => 'Hapus pengeluaran'],
            ['module' => 'Pengeluaran', 'action' => 'Kelola kategori pengeluaran'],

            // ─── BUKU KAS ────────────────────────────────────────────
            ['module' => 'Buku Kas', 'action' => 'Lihat daftar buku kas'],
            ['module' => 'Buku Kas', 'action' => 'Tambah buku kas'],
            ['module' => 'Buku Kas', 'action' => 'Edit buku kas'],
            ['module' => 'Buku Kas', 'action' => 'Hapus buku kas'],

            // ─── PELANGGAN ───────────────────────────────────────────
            ['module' => 'Pelanggan', 'action' => 'Lihat daftar pelanggan'],
            ['module' => 'Pelanggan', 'action' => 'Tambah pelanggan'],
            ['module' => 'Pelanggan', 'action' => 'Edit pelanggan'],
            ['module' => 'Pelanggan', 'action' => 'Hapus pelanggan'],
            ['module' => 'Pelanggan', 'action' => 'Lihat riwayat transaksi pelanggan'],

            // ─── PRODUK & LAYANAN ────────────────────────────────────
            ['module' => 'Produk & Layanan', 'action' => 'Lihat daftar produk'],
            ['module' => 'Produk & Layanan', 'action' => 'Tambah produk'],
            ['module' => 'Produk & Layanan', 'action' => 'Edit produk'],
            ['module' => 'Produk & Layanan', 'action' => 'Hapus produk'],
            ['module' => 'Produk & Layanan', 'action' => 'Kelola kategori produk'],
            ['module' => 'Produk & Layanan', 'action' => 'Kelola harga produk'],

            // ─── KARYAWAN ────────────────────────────────────────────
            ['module' => 'Karyawan', 'action' => 'Lihat daftar karyawan'],
            ['module' => 'Karyawan', 'action' => 'Tambah karyawan'],
            ['module' => 'Karyawan', 'action' => 'Edit karyawan'],
            ['module' => 'Karyawan', 'action' => 'Hapus karyawan'],
            ['module' => 'Karyawan', 'action' => 'Kelola role karyawan'],
            ['module' => 'Karyawan', 'action' => 'Kelola permission karyawan'],
            ['module' => 'Karyawan', 'action' => 'Lihat data gaji karyawan'],

            // ─── PENGGAJIAN ──────────────────────────────────────────
            ['module' => 'Penggajian', 'action' => 'Lihat daftar penggajian'],
            ['module' => 'Penggajian', 'action' => 'Proses penggajian'],
            ['module' => 'Penggajian', 'action' => 'Edit penggajian'],
            ['module' => 'Penggajian', 'action' => 'Hapus penggajian'],
            ['module' => 'Penggajian', 'action' => 'Cetak slip gaji'],

            // ─── ABSENSI ─────────────────────────────────────────────
            ['module' => 'Absensi', 'action' => 'Lihat data absensi'],
            ['module' => 'Absensi', 'action' => 'Input absensi manual'],
            ['module' => 'Absensi', 'action' => 'Edit absensi'],
            ['module' => 'Absensi', 'action' => 'Hapus absensi'],

            // ─── PENGATURAN ──────────────────────────────────────────
            ['module' => 'Pengaturan', 'action' => 'Kelola profil outlet'],
            ['module' => 'Pengaturan', 'action' => 'Kelola metode pembayaran'],
            ['module' => 'Pengaturan', 'action' => 'Kelola pajak & diskon'],
            ['module' => 'Pengaturan', 'action' => 'Kelola printer & struk'],
        ];

        DB::table('permissions')->insert($permissions);
    }
}