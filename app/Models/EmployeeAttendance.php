<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAttendance extends Model
{
    protected $fillable = [
        'employee_id',
        'outlet_id',
        'work_date',
        'check_in',
        'overtime',
        'check_out',
        'status',
    ];

    protected $casts = [
        'work_date' => 'date',
        'check_in'  => 'datetime',
        'overtime'  => 'datetime',
        'check_out' => 'datetime',
    ];

    // Relasi ke Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Relasi ke Outlet
    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    // Scope: Hari ini
    public function scopeToday($query)
    {
        return $query->whereDate('work_date', today());
    }

    // Scope: Bulan ini
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('work_date', now()->month)
                     ->whereYear('work_date', now()->year);
    }

    // Scope: Status check-in
    public function scopeCheckedIn($query)
    {
        return $query->whereNotNull('check_in');
    }

    // Scope: Status check-out
    public function scopeCheckedOut($query)
    {
        return $query->whereNotNull('check_out');
    }
}