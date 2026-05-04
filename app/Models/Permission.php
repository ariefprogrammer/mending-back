<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'module',
        'action',
    ];

    public function employeePermissions()
    {
        return $this->hasMany(EmployeePermission::class, 'permission_id');
    }
}