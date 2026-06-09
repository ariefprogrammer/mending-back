<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\OutletNotaSetting;

class NotaController extends Controller
{
    public function show(string $transactionCode)
    {
        $transaction = Transaction::where('transaction_code', $transactionCode)
            ->with([
                'outlet', 
                'customer:id,name,phone',
                'items.service:id,name',
                'payments.paymentMethod:id,name',
                'createdByUser:id,name',
                'createdByEmployee:id,name',
            ])
            ->firstOrFail();

        $setting = OutletNotaSetting::where('outlet_id', $transaction->outlet_id)->first();

        return view('nota.show', compact('transaction', 'setting'));
    }
}