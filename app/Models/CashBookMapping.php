<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashBookMapping extends Model
{
    protected $table = 'cash_book_mappings';

    protected $fillable = [
        'outlet_id',
        'payment_method_id',
        'cash_book_id',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function cashBook()
    {
        return $this->belongsTo(OutletCashBook::class, 'cash_book_id');
    }
}