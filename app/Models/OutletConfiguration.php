<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'allow_multiple_services',
        'allow_duplicate_service',
        'input_total_pcs_mandatory',
        'process_berurutan',
        'payment_first',
        'employee_update_data',
        'rounding_type',
        'rounding_multiple',
        'is_tax_enabled',
        'tax_type',
        'tax_percentage',
        'delivery_form_url',
    ];

    protected $casts = [
        'allow_multiple_services'   => 'boolean',
        'allow_duplicate_service'   => 'boolean',
        'input_total_pcs_mandatory' => 'boolean',
        'process_berurutan'         => 'boolean',
        'payment_first'             => 'boolean',
        'employee_update_data'      => 'boolean',
        'is_tax_enabled'            => 'boolean',
        'tax_percentage'            => 'float',
        'rounding_multiple'         => 'integer',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
