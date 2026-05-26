<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionCashBook extends Model
{
    protected $table = 'transaction_cash_books';

    protected $fillable = [
        'outlet_cash_book_id',
        'outlet_id',
        'type',
        'amount',
        'description',
        'transaction_date',
        'created_by_user_id',
        'created_by_employee_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount'           => 'integer',
    ];

    public function cashBook()
    {
        return $this->belongsTo(OutletCashBook::class, 'outlet_cash_book_id');
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
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