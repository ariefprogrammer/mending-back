<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnameItem extends Model
{
    protected $table = 'stock_opname_items';

    protected $fillable = [
        'stock_opname_id',
        'outlet_material_id',
        'system_quantity',
        'physical_quantity',
        'difference',
    ];

    public function stockOpname()
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function material()
    {
        return $this->belongsTo(OutletMaterial::class, 'outlet_material_id');
    }
}