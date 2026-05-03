<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceFlow extends Model
{
    protected $fillable = [
        'service_id',
        'name',
        'sequence',
        'is_active',
        'service_unit_id',
        'commission',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'sequence'       => 'integer',
        'commission'     => 'integer',
        'service_id'     => 'integer',
        'service_unit_id'=> 'integer',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function serviceUnit()
    {
        return $this->belongsTo(ServiceUnit::class);
    }
}