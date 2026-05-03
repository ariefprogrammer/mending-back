<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'customer_type',
        'name',
        'phone',
        'email',
        'address',
        'url_address',
        'balance'
    ];

    protected $casts = [
        'balance' => 'integer',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}