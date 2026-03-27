<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalOutletRevenueCategory extends Model
{
    use HasFactory;

    protected $table = 'external_outlet_revenue_categories';

    protected $fillable = ['outlet_id', 'name'];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}