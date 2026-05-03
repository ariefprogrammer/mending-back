<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = ['outlet_id', 'name'];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function unitProcesses()
    {
        return $this->hasMany(UnitProcess::class);
    }
}