<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalOutletCostCategory extends Model
{
    use HasFactory;

    protected $table = 'external_outlet_cost_categories';

    protected $fillable = ['outlet_id', 'name'];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}