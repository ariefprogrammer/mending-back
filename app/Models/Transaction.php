<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'outlet_id',
        'customer_id',
        'transaction_code',
        'status',
        'payment_status',
        'subtotal',
        'discount',
        'tax_amount',
        'grand_total',
        'pickup_rak_info',
        'total_packaging_qty',
        'estimated_completion',
        'completed_at',
    ];

    protected $casts = [
        'subtotal'             => 'integer',
        'discount'             => 'integer',
        'tax_amount'           => 'integer',
        'grand_total'          => 'integer',
        'total_packaging_qty'  => 'integer',
        'estimated_completion' => 'datetime',
        'completed_at'         => 'datetime',
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

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id');
    }

    public function processes()
    {
        return $this->hasMany(TransactionItemProcess::class, 'transaction_item_id');
    }

    public function payments()
    {
        return $this->hasMany(TransactionPayment::class, 'transaction_id');
    }

}