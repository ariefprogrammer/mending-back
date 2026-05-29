<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TransactionItem extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'transaction_id',
        'service_id',
        'qty',
        'price',
        'subtotal',
    ];

    protected $casts = [
        'qty'      => 'float',
        'price'    => 'integer',
        'subtotal' => 'integer',
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

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function processes()
    {
        return $this->hasMany(TransactionItemProcess::class, 'transaction_item_id');
    }
}