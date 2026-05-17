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
        'asset_id',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
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
}