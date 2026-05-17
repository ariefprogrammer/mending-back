<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutletMaterialCategory extends Model
{
    protected $table = 'outlet_material_categories';

    protected $fillable = [
        'outlet_id',
        'name',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}