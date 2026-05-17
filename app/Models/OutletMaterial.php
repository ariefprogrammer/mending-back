<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutletMaterial extends Model
{
    protected $table = 'outlet_materials';

    protected $fillable = [
        'outlet_id',
        'outlet_material_category_id',
        'name',
        'satuan_id',
        'min_stock_alert',
        'current_quantity',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function category()
    {
        return $this->belongsTo(OutletMaterialCategory::class, 'outlet_material_category_id');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }

    public function usages()
    {
        return $this->hasMany(OutletMaterialUsage::class, 'outlet_material_id');
    }
}