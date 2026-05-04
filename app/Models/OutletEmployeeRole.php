<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutletEmployeeRole extends Model
{
    protected $fillable = ['name', 'outlet_id'];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}