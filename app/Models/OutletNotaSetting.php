<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OutletNotaSetting extends Model
{
    protected $table = 'outlet_nota_settings';

    protected $fillable = [
        'outlet_id',
        'logo_path',
        'header_alignment',
        'header_note',
        'show_logo',
        'show_nama_outlet',
        'show_alamat_outlet',
        'show_nama_kasir',
        'show_nama_pelanggan',
        'show_kategori_layanan',
        'show_jumlah_potong',
        'show_estimasi_selesai',
        'show_parfum',
        'show_qr_code',
        'show_powered_by',
        'show_header_fisik',
        'show_footer_fisik',
        'auto_potong_nota',
    ];

    protected $casts = [
        'show_logo'             => 'boolean',
        'show_nama_outlet'      => 'boolean',
        'show_alamat_outlet'    => 'boolean',
        'show_nama_kasir'       => 'boolean',
        'show_nama_pelanggan'   => 'boolean',
        'show_kategori_layanan' => 'boolean',
        'show_jumlah_potong'    => 'boolean',
        'show_estimasi_selesai' => 'boolean',
        'show_parfum'           => 'boolean',
        'show_qr_code'          => 'boolean',
        'show_powered_by'       => 'boolean',
        'show_header_fisik'     => 'boolean',
        'show_footer_fisik'     => 'boolean',
        'auto_potong_nota'      => 'boolean',
    ];

    // Append logo_url agar Flutter langsung dapat URL lengkap
    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) return null;
        return Storage::disk('public')->url($this->logo_path);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}