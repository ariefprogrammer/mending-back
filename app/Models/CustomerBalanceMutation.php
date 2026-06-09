<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerBalanceMutation extends Model
{
    protected $fillable = [
        'customer_id',
        'outlet_id',
        'type',
        'payment_method_id',
        'cash_book_id',
        'amount',
        'balance_before',
        'balance_after',
        'notes',
        'created_by_user_id',
        'created_by_employee_id',
    ];

    protected $casts = [
        'amount'         => 'float',
        'balance_before' => 'float',
        'balance_after'  => 'float',
    ];

    const TYPES = ['topup', 'deduction', 'refund', 'adjustment'];

    // Tipe yang menambah saldo
    const CREDIT_TYPES = ['topup', 'refund'];

    // Tipe yang mengurangi saldo
    const DEBIT_TYPES = ['deduction', 'adjustment'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function createdByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by_employee_id');
    }
}