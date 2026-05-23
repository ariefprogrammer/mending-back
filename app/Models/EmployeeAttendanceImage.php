<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAttendanceImage extends Model
{
    protected $table = 'employee_attendance_images';

    protected $fillable = [
        'employee_attendance_id',
        'type',
        'image_path',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    public function attendance()
    {
        return $this->belongsTo(EmployeeAttendance::class, 'employee_attendance_id');
    }
}