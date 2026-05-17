<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutletMaterialUsage extends Model
{
    protected $table = 'outlet_material_usages';

    protected $fillable = [
        'outlet_material_id',
        'satuan_id',
        'quantity_used_per_unit',
    ];

    public function material()
    {
        return $this->belongsTo(OutletMaterial::class, 'outlet_material_id');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }
}