<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cost extends Model
{
    protected $fillable = [
        'outlet_id',
        'cash_book_id',
        'transaction_cash_book_id',
        'payment_method_id',
        'category_id',
        'name',
        'unit_name',
        'quantity',
        'price',
        'catatan',
    ];

    protected $casts = [
        'quantity' => 'float',
        'price'    => 'float',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function cashBook()
    {
        return $this->belongsTo(OutletCashBook::class, 'cash_book_id');
    }

    public function category()
    {
        return $this->belongsTo(ExternalOutletCostCategory::class, 'category_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function cashBookTransaction()
    {
        return $this->belongsTo(TransactionCashBook::class, 'transaction_cash_book_id');
    }
}