<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'service_code',
        'name',
        'outlet_service_category_id',
        'satuan_id',
        'price',
        'duration_unit',
        'duration',
        'minimum_qty'
    ];

    protected $casts = [
        'price' => 'integer',
        'duration' => 'integer',
        'minimum_qty' => 'integer',
        'satuan_id' => 'integer',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function category()
    {
        return $this->belongsTo(OutletServiceCategory::class, 'outlet_service_category_id');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }

    public function flows()
    {
        return $this->hasMany(ServiceFlow::class);
    }
}