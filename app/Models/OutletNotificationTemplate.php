<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletNotificationTemplate extends Model
{
    protected $fillable = [
        'outlet_id',
        'type',
        'is_active',
        'message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Daftar type yang valid
    const TYPES = [
        'sedang_dikerjakan',
        'siap_diambil',
        'tagihan_belum_terbayar',
        'pengingat_belum_diambil',
        'selesai',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}