<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutletAsset extends Model
{
    use HasFactory;

    protected $fillable = ['outlet_id', 'name', 'process'];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}