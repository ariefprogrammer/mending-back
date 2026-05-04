<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailSalaryComponent extends Model
{
    protected $table = 'detail_salary_components';

    protected $fillable = [
        'salary_component_id',
        'name',
        'amount',
        'type',
        'duration',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function salaryComponent()
    {
        return $this->belongsTo(SalaryComponent::class, 'salary_component_id');
    }
}