<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Revenue;
use App\Models\Cost;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionReportController extends Controller
{
    // ─── Access Guard ─────────────────────────────────────────────────────────

    private function checkAccess(int $outletId): bool
    {
        $user = auth('sanctum')->user();
        if (!$user) return false;

        if ($user instanceof \App\Models\User) {
            return Outlet::where('id', $outletId)
                         ->where('user_id', $user->id)
                         ->exists();
        }

        if ($user instanceof \App\Models\Employee) {
            return (int) $user->outlet_id === (int) $outletId;
        }

        return false;
    }

    // laporan transaksi layanan
    public function byService(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $startDate = $request->start_date . ' 00:00:00';
        $endDate   = $request->end_date   . ' 23:59:59';

        // ── Ambil semua transaction_items dalam rentang tanggal ───────────────
        // Join ke transactions untuk filter outlet & tanggal,
        // load service & satuan untuk grouping label.

        $items = TransactionItem::with([
                'service:id,name,satuan_id,outlet_service_category_id',
                'service.satuan:id,name',
                'service.category:id,name',
                'transaction:id,transaction_code,customer_id,customer_name,payment_status,created_at,estimated_completion',
                'transaction.customer:id,name,phone',
            ])
            ->whereHas('transaction', function ($q) use ($outletId, $startDate, $endDate) {
                $q->where('outlet_id', $outletId)
                  ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->get();

        // ── Group by service_id ───────────────────────────────────────────────

        $grouped = $items->groupBy('service_id');

        $services = $grouped->map(function ($serviceItems, $serviceId) {

            $firstItem   = $serviceItems->first();
            $serviceName    = $firstItem->service->name             ?? 'Layanan Dihapus';
            $satuanName     = $firstItem->service->satuan->name     ?? '-';
            $categoryName   = $firstItem->service->category->name  ?? '-';

            $totalQty    = $serviceItems->sum('qty');
            $totalAmount = $serviceItems->sum(fn($i) => $i->qty * $i->price);

            $transactions = $serviceItems->map(function ($item) {
                $trx          = $item->transaction;
                $customer     = $trx->customer;
                $customerName = $customer?->name ?? $trx->customer_name ?? 'Tamu';
                $customerPhone= $customer?->phone ?? null;

                return [
                    'id'                   => $trx->id,
                    'transaction_code'     => $trx->transaction_code,
                    'customer_name'        => $customerName,
                    'customer_phone'       => $customerPhone,
                    'payment_status'       => $trx->payment_status,
                    'qty'                  => (float) $item->qty,
                    'item_total'           => (int) ($item->qty * $item->price),
                    'created_at'           => $trx->created_at,
                    'estimated_completion' => $trx->estimated_completion,
                ];
            })->values();

            return [
                'service_id'        => $serviceId,
                'service_name'      => $serviceName,
                'satuan_name'       => $satuanName,
                'category_name'     => $categoryName,
                'total_qty'         => round((float) $totalQty, 2),
                'total_amount'      => (int) $totalAmount,
                'transaction_count' => $serviceItems->count(),
                'transactions'      => $transactions,
            ];
        })->values();

        // ── Meta summary ──────────────────────────────────────────────────────
        // Hitung dari transaksi unik (bukan items) agar tidak double count

        $uniqueTransactionIds = $items->pluck('transaction_id')->unique();

        $grandTotal = Transaction::whereIn('id', $uniqueTransactionIds)
                                 ->sum('grand_total');

        $meta = [
            'start_date'         => $request->start_date,
            'end_date'           => $request->end_date,
            'total_transactions' => $uniqueTransactionIds->count(),
            'grand_total'        => (int) $grandTotal,
        ];

        return response()->json([
            'status' => 'success',
            'meta'   => $meta,
            'data'   => $services,
        ]);
    }

    // laporan transaksi pembayaran
    public function byPaymentMethod(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
 
        $validator = Validator::make($request->all(), [
            'start_date'     => 'required|date_format:Y-m-d',
            'end_date'       => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'payment_status' => 'nullable|in:unpaid,partial,paid',
        ]);
 
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }
 
        $startDate = $request->start_date . ' 00:00:00';
        $endDate   = $request->end_date   . ' 23:59:59';
 
        $query = TransactionPayment::with([
                'paymentMethod:id,name',
                'transaction:id,transaction_code,customer_id,customer_name,payment_status',
                'transaction.customer:id,name,phone',
            ])
            ->whereHas('transaction', function ($q) use ($outletId, $startDate, $endDate) {
                $q->where('outlet_id', $outletId)
                  ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->whereBetween('created_at', [$startDate, $endDate]);
 
        if ($request->filled('payment_status')) {
            $query->whereHas('transaction', function ($q) use ($request) {
                $q->where('payment_status', $request->payment_status);
            });
        }
 
        $payments = $query->get();
 
        $grouped = $payments->groupBy('payment_method_id');
 
        $methods = $grouped->map(function ($methodPayments, $methodId) {
            $firstPayment      = $methodPayments->first();
            $paymentMethodName = $firstPayment->paymentMethod->name ?? 'Metode Dihapus';
 
            $paymentList = $methodPayments->map(function ($payment) {
                $trx           = $payment->transaction;
                $customer      = $trx->customer;
                $customerName  = $customer?->name ?? $trx->customer_name ?? 'Tamu';
                $customerPhone = $customer?->phone ?? null;
 
                return [
                    'id'               => $payment->id,
                    'transaction_code' => $trx->transaction_code,
                    'customer_name'    => $customerName,
                    'customer_phone'   => $customerPhone,
                    'payment_status'   => $trx->payment_status,
                    'amount_paid'      => (int) $payment->amount_paid,
                    'change_amount'    => (int) $payment->change_amount,
                    'paid_at'          => $payment->created_at,
                ];
            })->values();
 
            return [
                'payment_method_id'   => $methodId,
                'payment_method_name' => $paymentMethodName,
                'payment_count'       => $methodPayments->count(),
                'total_amount'        => (int) $methodPayments->sum('amount_paid'),
                'payments'            => $paymentList,
            ];
        })->values();
 
        $meta = [
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
            'payment_status' => $request->payment_status ?? null,
            'total_payments' => $payments->count(),
            'grand_total'    => (int) $payments->sum('amount_paid'),
        ];
 
        return response()->json([
            'status' => 'success',
            'meta'   => $meta,
            'data'   => $methods,
        ]);
    }

    // laporan buku kas besar
    public function cashBookLedger(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'cash_book_id' => 'required|integer|exists:outlet_cash_books,id',
            'month'        => 'required|integer|min:1|max:12',
            'year'         => 'required|integer|min:2020|max:2030',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $cashBook = \App\Models\OutletCashBook::where('id', $request->cash_book_id)
                        ->where('outlet_id', $outletId)
                        ->first();

        if (!$cashBook) {
            return response()->json(['status' => 'error', 'message' => 'Buku kas tidak ditemukan'], 404);
        }

        $month     = (int) $request->month;
        $year      = (int) $request->year;
        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate   = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

        // ── Opening balance: semua transaksi sebelum bulan ini ───────────────
        $openingIn  = \App\Models\TransactionCashBook::where('outlet_cash_book_id', $cashBook->id)
                        ->where('type', 'in')
                        ->where('transaction_date', '<', $startDate->toDateString())
                        ->sum('amount');

        $openingOut = \App\Models\TransactionCashBook::where('outlet_cash_book_id', $cashBook->id)
                        ->where('type', 'out')
                        ->where('transaction_date', '<', $startDate->toDateString())
                        ->sum('amount');

        $openingBalance = $openingIn - $openingOut;

        // ── Transaksi bulan ini ───────────────────────────────────────────────
        $transactions = \App\Models\TransactionCashBook::with([
                            'createdByUser:id,name',
                            'createdByEmployee:id,name',
                        ])
                        ->where('outlet_cash_book_id', $cashBook->id)
                        ->whereBetween('transaction_date', [
                            $startDate->toDateString(),
                            $endDate->toDateString(),
                        ])
                        ->orderBy('transaction_date', 'asc')
                        ->get();

        $totalIn  = $transactions->where('type', 'in')->sum('amount');
        $totalOut = $transactions->where('type', 'out')->sum('amount');
        $net      = $totalIn - $totalOut;

        $transactionList = $transactions->map(function ($trx) {
            $createdBy = $trx->createdByUser?->name
                    ?? $trx->createdByEmployee?->name
                    ?? 'Sistem';

            return [
                'id'               => $trx->id,
                'description'      => $trx->description ?? '-',
                'type'             => $trx->type,
                'amount'           => $trx->amount,
                'transaction_date' => $trx->transaction_date->toDateString(),
                'created_by'       => $createdBy,
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'cash_book_id'    => $cashBook->id,
                'name'            => $cashBook->name,
                'month'           => $month,
                'year'            => $year,
                'summary'         => [
                    'opening_balance' => $openingBalance,
                    'total_in'        => (int) $totalIn,
                    'total_out'       => (int) $totalOut,
                    'net'             => (int) $net,
                    'closing_balance' => $openingBalance + $net,
                ],
                'transactions'    => $transactionList,
            ],
        ]);
    }

    // Laporan rangkuman
    public function rangkuman(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $startDate = $request->start_date . ' 00:00:00';
        $endDate   = $request->end_date   . ' 23:59:59';

        // ── Pendapatan: Layanan (transactions paid) ───────────────────────────
        $totalLayanan = Transaction::where('outlet_id', $outletId)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('grand_total');

        // ── Pendapatan: Revenues per kategori ────────────────────────────────
        $revenues = \App\Models\Revenue::with('category:id,name')
            ->where('outlet_id', $outletId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $revenueByCategory = $revenues
            ->groupBy('category_id')
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'category_name' => $first->category?->name ?? 'Lain-lain',
                    'total'         => (int) $items->sum(fn($i) => $i->quantity * $i->price),
                ];
            })->values();

        $totalRevenue = (int) $revenues->sum(fn($i) => $i->quantity * $i->price);

        $totalPendapatan = (int) $totalLayanan + $totalRevenue;

        // ── Pengeluaran: Costs per kategori ──────────────────────────────────
        $costs = \App\Models\Cost::with('category:id,name')
            ->where('outlet_id', $outletId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $costByCategory = $costs
            ->groupBy('category_id')
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'category_name' => $first->category?->name ?? 'Lain-lain',
                    'total'         => (int) $items->sum(fn($i) => $i->quantity * $i->price),
                ];
            })->values();

        $totalPengeluaran = (int) $costs->sum(fn($i) => $i->quantity * $i->price);

        // ── Metode Pembayaran (dari transaction payments) ─────────────────────
        $payments = \App\Models\TransactionPayment::with('paymentMethod:id,name')
            ->whereHas('transaction', function ($q) use ($outletId, $startDate, $endDate) {
                $q->where('outlet_id', $outletId)
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $paymentByMethod = $payments
            ->groupBy('payment_method_id')
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'method_name' => $first->paymentMethod?->name ?? 'Lain-lain',
                    'total'       => (int) $items->sum('amount_paid'),
                ];
            })->values();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'start_date'       => $request->start_date,
                'end_date'         => $request->end_date,
                'laba_rugi'        => [
                    'total_pendapatan'  => $totalPendapatan,
                    'total_pengeluaran' => $totalPengeluaran,
                    'total_keuntungan'  => $totalPendapatan - $totalPengeluaran,
                ],
                'pendapatan'       => [
                    'layanan' => (int) $totalLayanan,
                    'lainnya' => $revenueByCategory,
                    'total'   => $totalPendapatan,
                ],
                'pengeluaran'      => [
                    'per_kategori' => $costByCategory,
                    'total'        => $totalPengeluaran,
                ],
                'metode_pembayaran' => $paymentByMethod,
            ],
        ]);
    }

    // Laporan Pola Transaksi
    public function polaTransaksi(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $startDate = $request->start_date . ' 00:00:00';
        $endDate   = $request->end_date   . ' 23:59:59';

        $transactions = Transaction::where('outlet_id', $outletId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get(['id', 'created_at']);

        $isEmpty = $transactions->isEmpty();

        // ── Per jam (0-23) ────────────────────────────────────────────────────
        $perJam = array_fill(0, 24, 0);
        foreach ($transactions as $trx) {
            $hour = (int) $trx->created_at->format('H');
            $perJam[$hour]++;
        }
        $perJamResult = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $perJamResult[] = [
                'label' => str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00',
                'count' => $perJam[$hour],
            ];
        }
        if (!$isEmpty) {
            $maxJam      = max($perJam);
            $jamIndex    = array_search($maxJam, $perJam);
            $jamTerramai = str_pad($jamIndex, 2, '0', STR_PAD_LEFT) . ':00';
        } else {
            $jamTerramai = '-';
        }

        // ── Per hari (Minggu-Sabtu) ───────────────────────────────────────────
        $hariMap = [
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa',
            3 => 'Rabu',   4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu',
        ];
        $perHari = array_fill(0, 7, 0);
        foreach ($transactions as $trx) {
            $day = (int) $trx->created_at->dayOfWeek;
            $perHari[$day]++;
        }
        $perHariResult = [];
        for ($day = 0; $day < 7; $day++) {
            $perHariResult[] = [
                'label' => $hariMap[$day],
                'count' => $perHari[$day],
            ];
        }
        if (!$isEmpty) {
            $maxHari      = max($perHari);
            $hariIndex    = array_search($maxHari, $perHari);
            $hariTerramai = $hariMap[$hariIndex];
        } else {
            $hariTerramai = '-';
        }

        // ── Per minggu dalam rentang ──────────────────────────────────────────
        $perMinggu = [];
        foreach ($transactions as $trx) {
            // PERBAIKAN: Tambahkan \W agar formatnya menjadi YYYY-Www (contoh: 2026-W23)
            $key = $trx->created_at->format('Y-\WW');
            $perMinggu[$key] = ($perMinggu[$key] ?? 0) + 1;
        }
        
        $mingguTerramai = '-';
        if (!empty($perMinggu)) {
            arsort($perMinggu);
            $mingguTerramaiKey = array_key_first($perMinggu);
            
            // Pemecahan string yang lebih aman dengan fallback
            $parts = explode('-W', $mingguTerramaiKey);
            $mgYear = $parts[0] ?? '-';
            $mgWeek = $parts[1] ?? '-';
            
            $mingguTerramai    = 'Minggu ' . $mgWeek . ' (' . $mgYear . ')';
            ksort($perMinggu);
        }
        
        $perMingguResult = [];
        foreach ($perMinggu as $key => $count) {
            // Pemecahan string aman untuk looping data per minggu
            $parts = explode('-W', $key);
            $year  = $parts[0] ?? '-';
            $week  = $parts[1] ?? '-';
            
            $perMingguResult[] = [
                'label' => 'Minggu ' . $week . ' (' . $year . ')',
                'count' => $count,
                ];
        }

        // ── Per bulan ─────────────────────────────────────────────────────────
        $bulanMap = [
            1  => 'Jan', 2  => 'Feb', 3  => 'Mar', 4  => 'Apr',
            5  => 'Mei', 6  => 'Jun', 7  => 'Jul', 8  => 'Ags',
            9  => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        $perBulan = [];
        foreach ($transactions as $trx) {
            $key = $trx->created_at->format('Y-m');
            $perBulan[$key] = ($perBulan[$key] ?? 0) + 1;
        }
        $bulanTerramai = '-';
        if (!empty($perBulan)) {
            arsort($perBulan);
            $bulanTerramaiKey = array_key_first($perBulan);
            $bulanParts       = explode('-', $bulanTerramaiKey);
            $bulanTerramai    = $bulanMap[(int) ($bulanParts[1] ?? 1)] . ' ' . ($bulanParts[0] ?? '-');
            ksort($perBulan);
        }
        $perBulanResult = [];
        foreach ($perBulan as $key => $count) {
            $parts            = explode('-', $key);
            $perBulanResult[] = [
                'label' => $bulanMap[(int) ($parts[1] ?? 1)] . ' ' . ($parts[0] ?? '-'),
                'count' => $count,
            ];
        }

        // ── Per quartal ───────────────────────────────────────────────────────
        $perQuartal = [];
        foreach ($transactions as $trx) {
            $month   = (int) $trx->created_at->format('m');
            $year    = $trx->created_at->format('Y');
            $quarter = 'Q' . ceil($month / 3) . ' ' . $year;
            $perQuartal[$quarter] = ($perQuartal[$quarter] ?? 0) + 1;
        }
        $quartalTerramai = '-';
        if (!empty($perQuartal)) {
            arsort($perQuartal);
            $quartalTerramai = array_key_first($perQuartal);
            ksort($perQuartal);
        }
        $perQuartalResult = [];
        foreach ($perQuartal as $key => $count) {
            $perQuartalResult[] = [
                'label' => $key,
                'count' => $count,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'start_date'      => $request->start_date,
                'end_date'        => $request->end_date,
                'total_transaksi' => $transactions->count(),
                'per_jam'         => [
                    'terramai' => $jamTerramai,
                    'data'     => $perJamResult,
                ],
                'per_hari'        => [
                    'terramai' => $hariTerramai,
                    'data'     => $perHariResult,
                ],
                'per_minggu'      => [
                    'terramai' => $mingguTerramai,
                    'data'     => $perMingguResult,
                ],
                'per_bulan'       => [
                    'terramai' => $bulanTerramai,
                    'data'     => $perBulanResult,
                ],
                'per_quartal'     => [
                    'terramai' => $quartalTerramai,
                    'data'     => $perQuartalResult,
                ],
            ],
        ]);
    }


}