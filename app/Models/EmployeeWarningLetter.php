<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmployeeWarningLetter extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'outlet_id',
        'employee_id',
        'date_effective',
        'description',
        'status',
        'created_by_user_id',
        'created_by_employee_id',
    ];

    protected $casts = [
        'date_effective' => 'date',
    ];

    const STATUSES = ['draft', 'terkirim', 'selesai'];

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

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function createdByEmployee()
    {
        return $this->belongsTo(Employee::class, 'created_by_employee_id');
    }
}