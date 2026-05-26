<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OutletCashBook;
use App\Models\TransactionCashBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CashBookController extends Controller
{
    private function checkAccess(int $outletId): bool
    {
        $user = auth('sanctum')->user();

        if (!$user) return false;

        if ($user instanceof \App\Models\User) {
            return \App\Models\Outlet::where('id', $outletId)
                                     ->where('user_id', $user->id)
                                     ->exists();
        }

        if ($user instanceof \App\Models\Employee) {
            return (int) $user->outlet_id === (int) $outletId;
        }

        return false;
    }

    // LIST CASHBOOKS
    public function index(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        $now       = Carbon::now();
        $thisMonth = $now->format('Y-m');
        $lastMonth = $now->copy()->subMonth()->format('Y-m');

        $cashBooks = OutletCashBook::where('outlet_id', $outletId)
            ->get()
            ->map(function ($cashBook) use ($thisMonth, $lastMonth) {

                $transactions = $cashBook->transactions();

                // Total pendapatan (semua waktu)
                $totalIn  = (clone $transactions)->where('type', 'in')->sum('amount');

                // Total pengeluaran (semua waktu)
                $totalOut = (clone $transactions)->where('type', 'out')->sum('amount');

                // Saldo bulan ini
                $thisMonthIn  = (clone $transactions)->where('type', 'in')
                                    ->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$thisMonth])
                                    ->sum('amount');
                $thisMonthOut = (clone $transactions)->where('type', 'out')
                                    ->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$thisMonth])
                                    ->sum('amount');

                // Saldo bulan lalu
                $lastMonthIn  = (clone $transactions)->where('type', 'in')
                                    ->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$lastMonth])
                                    ->sum('amount');
                $lastMonthOut = (clone $transactions)->where('type', 'out')
                                    ->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$lastMonth])
                                    ->sum('amount');

                return [
                    'id'                 => $cashBook->id,
                    'name'               => $cashBook->name,
                    'balance'            => $totalIn - $totalOut,
                    'total_income'       => $totalIn,
                    'total_expense'      => $totalOut,
                    'this_month_balance' => $thisMonthIn - $thisMonthOut,
                    'last_month_balance' => $lastMonthIn - $lastMonthOut,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data'   => $cashBooks,
        ]);
    }

    // DETAIL CASHBOOK + TRANSAKSI
    public function show(Request $request, $outletId, $cashBookId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $cashBook = OutletCashBook::where('outlet_id', $outletId)->find($cashBookId);

        if (!$cashBook) {
            return response()->json(['message' => 'Buku kas tidak ditemukan'], 404);
        }

        $query = TransactionCashBook::where('outlet_cash_book_id', $cashBookId)
                    ->where('outlet_id', $outletId);

        // Filter by transaction_date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('transaction_date', [
                $request->start_date,
                $request->end_date,
            ]);
        } elseif ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        // Filter by bulan & tahun (opsional, alternatif dari start/end date)
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('transaction_date', $request->month)
                  ->whereYear('transaction_date', $request->year);
        }

        // Filter by type (in/out)
        if ($request->filled('type') && in_array($request->type, ['in', 'out'])) {
            $query->where('type', $request->type);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
                              ->orderBy('id', 'desc')
                              ->get();

        // Hitung summary dari hasil filter
        $totalIn  = $transactions->where('type', 'in')->sum('amount');
        $totalOut = $transactions->where('type', 'out')->sum('amount');

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'            => $cashBook->id,
                'name'          => $cashBook->name,
                'balance'       => $cashBook->balance, 
                'summary'       => [
                    'total_in'  => $totalIn,
                    'total_out' => $totalOut,
                    'net'       => $totalIn - $totalOut,
                ],
                'transactions'  => $transactions,
            ],
        ]);
    }
}