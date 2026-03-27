<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutletCashBook extends Model
{
    use HasFactory;

    protected $fillable = ['outlet_id', 'name'];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}