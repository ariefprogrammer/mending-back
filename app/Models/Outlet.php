<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\OutletConfiguration;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Outlet extends Model
{
    protected $fillable = [
        'outlet_code',
        'name',
        'text_image',
        'user_id',
        'phone',
        'province_id',  
        'kabupaten_id', 
        'kecamatan_id', 
        'kelurahan_id',
        'address',
        'blok',
        'rt',  
        'rw',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function kabupaten(): BelongsTo
    {
        return $this->belongsTo(Kabupaten::class, 'kabupaten_id');
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class, 'kelurahan_id');
    }

    public function configuration(): HasOne
    {
        return $this->hasOne(OutletConfiguration::class);
    }

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if ($this->text_image) {
            return asset('storage/outlets/' . $this->text_image);
        }
        return asset('images/default-outlet.png'); // Gambar default jika kosong
    }

}
