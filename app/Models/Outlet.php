<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\OutletConfiguration;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Outlet extends Model
{
    protected $fillable = [
        'outlet_code',
        'name',
        'user_id',
        'phone',
        'province',
        'city',
        'kecamatan',
        'kelurahan',
        'address',
    ];

    // Relasi ke User (Owner)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function configuration(): HasOne
    {
        return $this->hasOne(OutletConfiguration::class);
    }

}
