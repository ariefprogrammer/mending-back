<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CustomerItemReport extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'outlet_id',
        'transaction_id',
        'description',
        'image',
        'pickup_proof_image',
        'status',
        'created_by_user_id',
        'created_by_employee_id',
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

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
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