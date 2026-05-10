<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'outlet_id',
        'customer_type',
        'name',
        'phone',
        'email',
        'address',
        'url_address',
        'balance',
    ];

    protected $casts = [
        'balance' => 'float',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}