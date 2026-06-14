<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TransactionItemProcess extends Model
{
    public $incrementing = false;
    public $timestamps   = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'transaction_item_id',
        'service_flow_id',
        'employee_id',
        'commision_snapshot',
        'asset_id',
        'unit_id', 
        'pieces',
        'satuan_id',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'commision_snapshot' => 'integer',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
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

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function transactionItem()
    {
        return $this->belongsTo(TransactionItem::class, 'transaction_item_id');
    }

    public function serviceFlow()
    {
        return $this->belongsTo(ServiceFlow::class, 'service_flow_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function asset()
    {
        return $this->belongsTo(OutletAsset::class, 'asset_id');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }
}