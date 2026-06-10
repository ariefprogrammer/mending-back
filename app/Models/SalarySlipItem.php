<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalarySlipItem extends Model
{
    protected $fillable = [
        'salary_slip_id',
        'salary_component_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function salarySlip(): BelongsTo
    {
        return $this->belongsTo(SalarySlip::class);
    }

    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class);
    }
}