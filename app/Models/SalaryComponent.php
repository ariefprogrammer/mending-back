<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function details()
    {
        return $this->hasMany(DetailSalaryComponent::class, 'salary_component_id');
    }
}