<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitProcess extends Model
{
    protected $table = 'unit_process';

    protected $fillable = [
        'unit_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relasi ke model Unit
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}