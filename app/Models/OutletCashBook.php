<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutletCashBook extends Model
{
    use HasFactory;

    protected $fillable = ['outlet_id', 'name'];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function transactions()
    {
        return $this->hasMany(TransactionCashBook::class, 'outlet_cash_book_id');
    }

    // Hitung saldo buku kas
    public function getBalanceAttribute(): int
    {
        $in  = $this->transactions()->where('type', 'in')->sum('amount');
        $out = $this->transactions()->where('type', 'out')->sum('amount');
        return $in - $out;
    }
}