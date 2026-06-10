<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalarySlip extends Model
{
    protected $fillable = [
        'employee_id',
        'outlet_id',
        'cash_book_id',
        'period_start',
        'period_end',
        'base_salary',
        'overtime_salary',
        'total_commission',
        'total_allowance',
        'total_deduction',
        'net_salary',
        'status',
    ];

    protected $casts = [
        'period_start'     => 'date',
        'period_end'       => 'date',
        'base_salary'      => 'integer',
        'overtime_salary'  => 'integer',
        'total_commission' => 'integer',
        'total_allowance'  => 'integer',
        'total_deduction'  => 'integer',
        'net_salary'       => 'integer',
    ];

    // Status yang tersedia
    const STATUS_DRAFT   = 'draft';
    const STATUS_PAID    = 'paid';
    const STATUS_PENDING = 'pending';

    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_PAID,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function cashBook(): BelongsTo
    {
        return $this->belongsTo(OutletCashBook::class, 'cash_book_id');
    }
}