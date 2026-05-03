<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = ['outlet_id', 'name', 'type'];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}