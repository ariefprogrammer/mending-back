<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Employee extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'outlet_id',
        'role_id',
        'employee_code',
        'name',
        'phone',
        'email',
        'password',
        'default_base_salary',
        'overtime_salary_per_hour',
        'ktp_image_url',
        'npwp_image_url',
        'bpjs_kesehatan_image_url',
        'bpjs_ketenagakerjaan_image_url',
        'is_active', 
    ];

    protected $casts = [
        'default_base_salary'      => 'float',
        'overtime_salary_per_hour' => 'float',
        'is_active'                => 'boolean',
    ];

    protected $hidden = [
        'password',           
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

    public function role()
    {
        return $this->belongsTo(OutletEmployeeRole::class, 'role_id');
    }

    public function salaryComponents()
    {
        return $this->hasMany(SalaryComponent::class, 'employee_id');
    }

    public function permissions()
    {
        return $this->hasMany(EmployeePermission::class, 'employee_id');
    }
}