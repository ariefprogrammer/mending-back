<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePermission extends Model
{
    protected $fillable = [
        'employee_id',
        'outlet_id',
        'permission_id',
        'allowed',
    ];

    protected $casts = [
        'allowed' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }
}