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

    // Laporan Proses Pengerjaan
    public function prosesLaporan(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'status'     => 'nullable|in:proses,selesai',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $startDate = $request->start_date . ' 00:00:00';
        $endDate   = $request->end_date   . ' 23:59:59';

        // ── Ambil semua service flows milik outlet ────────────────────────────
        $allFlows = \App\Models\ServiceFlow::whereHas('service', function ($q) use ($outletId) {
            $q->where('outlet_id', $outletId);
        })->get(['id', 'name'])->unique('name');

        // ── Ambil semua satuan dari database ──────────────────────────────────
        $allSatuans = \App\Models\Satuan::all(['id', 'name']);

        // ── Ambil semua proses dalam rentang tanggal ──────────────────────────
        $query = \App\Models\TransactionItemProcess::with([
                'satuan:id,name',
                'serviceFlow:id,name',
            ])
            ->whereHas('transactionItem.transaction', function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId);
            })
            ->whereBetween('started_at', [$startDate, $endDate]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $processes = $query->get();

        // ── Kelompokkan proses per satuan_id ──────────────────────────────────
        $grouped = $processes->groupBy(fn($p) => $p->satuan_id ?? 'null');

        // ── Bangun result dari semua satuan (termasuk yang tidak punya data) ──
        $satuanGroups = $allSatuans->map(function ($satuan) use ($grouped, $allFlows) {
            $items = $grouped->get($satuan->id, collect());

            $piecesByFlow = $items
                ->groupBy(fn($p) => $p->serviceFlow?->name ?? 'Lainnya')
                ->map(fn($flowItems) => (int) $flowItems->sum('pieces'));

            $flowData = $allFlows->map(fn($flow) => [
                'flow_name' => $flow->name,
                'total'     => $piecesByFlow[$flow->name] ?? 0,
            ])->values();

            return [
                'satuan_name' => $satuan->name,
                'flows'       => $flowData,
                'grand_total' => (int) $items->sum('pieces'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => [
                'start_date'    => $request->start_date,
                'end_date'      => $request->end_date,
                'status_filter' => $request->status ?? 'semua',
                'total_records' => $processes->count(),
                'groups'        => $satuanGroups->values(),
            ],
        ]);
    }

    // Laporan Peralatan Produksi
    public function peralatanProduksi(Request $request, $outletId)
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

        // ── Ambil semua unit (mesin) milik outlet ─────────────────────────────
        $allUnits = \App\Models\Unit::where('outlet_id', $outletId)
            ->with('unitProcesses:id,unit_id,name,is_active')
            ->get();

        // ── Ambil semua satuan ────────────────────────────────────────────────
        $allSatuans = \App\Models\Satuan::all(['id', 'name']);

        // ── Ambil semua proses dalam rentang tanggal ──────────────────────────
        $processes = \App\Models\TransactionItemProcess::with([
                'satuan:id,name',
                'serviceFlow:id,name',
            ])
            ->whereNotNull('unit_id')
            ->whereHas('transactionItem.transaction', function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId);
            })
            ->whereBetween('started_at', [$startDate, $endDate])
            ->get();

        // ── Kelompokkan per unit_id ───────────────────────────────────────────
        $groupedByUnit = $processes->groupBy('unit_id');

        // ── Bangun result dari semua unit ─────────────────────────────────────
        $result = $allUnits->map(function ($unit) use ($groupedByUnit, $allSatuans) {
            $unitProcesses = $groupedByUnit->get($unit->id, collect());

            // Nama-nama proses yang bisa dikerjakan unit ini
            $processNames = $unit->unitProcesses
                ->where('is_active', true)
                ->pluck('name')
                ->implode(', ');

            // Hitung pieces per satuan
            $groupedBySatuan = $unitProcesses->groupBy('satuan_id');

            $satuanData = $allSatuans->map(function ($satuan) use ($groupedBySatuan) {
                $items = $groupedBySatuan->get($satuan->id, collect());
                return [
                    'satuan_id'   => $satuan->id,
                    'satuan_name' => $satuan->name,
                    'total'       => (int) $items->sum('pieces'),
                ];
            })->filter(fn($s) => $s['total'] > 0)->values();

            return [
                'unit_id'       => $unit->id,
                'unit_name'     => $unit->name,
                'process_names' => $processNames ?: '-',
                'total_jobs'    => $unitProcesses->count(),
                'satuan_data'   => $satuanData,
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date,
                'units'      => $result,
            ],
        ]);
    }

    // Laporan Komisi
    public function commissionReport(Request $request, $outletId)
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

        // ── Ambil semua master karyawan di outlet ini ────────────────────────
        // Di-load dari awal beserta jabatannya (role) agar karyawan yang belum 
        // memiliki komisi di rentang tanggal tersebut tetap muncul dengan total 0.
        $employees = \App\Models\Employee::with('role:id,name')
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->get(['id', 'employee_code', 'name', 'role_id']);

        // ── Ambil semua proses yang selesai/berjalan dalam rentang tanggal ────
        // Menggunakan commision_snapshot yang telah dicatat saat mulai proses.
        $processes = \App\Models\TransactionItemProcess::whereHas('transactionItem.transaction', function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId);
            })
            ->whereNotNull('employee_id')
            ->whereBetween('started_at', [$startDate, $endDate])
            ->get(['id', 'employee_id', 'commision_snapshot']);

        // Kelompokkan data proses berdasarkan employee_id
        $groupedProcesses = $processes->groupBy('employee_id');

        // ── Bangun Hasil Rekapitulasi ─────────────────────────────────────────
        $reportData = $employees->map(function ($employee) use ($groupedProcesses) {
            $employeeJobs = $groupedProcesses->get($employee->id, collect());
            
            // Mengalikan atau menjumlahkan seluruh nilai commision_snapshot dari pekerjaan karyawan
            $totalCommission = $employeeJobs->sum('commision_snapshot');

            return [
                'employee_code'    => $employee->employee_code,
                'name'             => $employee->name,
                'role_name'        => $employee->role->name ?? '-',
                'total_commission' => (int) $totalCommission,
            ];
        })->values();

        // Urutkan berdasarkan komisi tertinggi (opsional, bisa dihapus jika tidak diperlukan)
        $reportData = $reportData->sortByDesc('total_commission')->values();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'start_date'     => $request->start_date,
                'end_date'       => $request->end_date,
                'total_employees'=> $employees->count(),
                'grand_total_commission' => (int) $processes->sum('commision_snapshot'),
                'report'         => $reportData,
            ],
        ]);
    }

    // Laporan Karyawan
    public function byEmployee(Request $request, $outletId)
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
 
        $startDate = $request->start_date;
        $endDate   = $request->end_date;
 
        // ── 1. Ambil semua karyawan aktif milik outlet ────────────────────────
        $employees = \App\Models\Employee::with('role:id,name')
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->get();
 
        // ── 2. Ambil presensi dalam rentang tanggal ───────────────────────────
        $attendances = \App\Models\EmployeeAttendance::where('outlet_id', $outletId)
            ->whereBetween('work_date', [$startDate, $endDate])
            ->whereNotNull('check_in')
            ->whereNotNull('check_out')
            ->get()
            ->groupBy('employee_id');
 
        // ── 3. Ambil proses pengerjaan dalam rentang tanggal ──────────────────
        $processes = \App\Models\TransactionItemProcess::with([
                'serviceFlow:id,name',
                'satuan:id,name',
            ])
            ->where('status', 'selesai')
            ->whereNotNull('employee_id')
            ->whereBetween('completed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereHas('transactionItem.transaction', function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId);
            })
            ->get()
            ->groupBy('employee_id');
 
        // ── 4. Susun response per karyawan ────────────────────────────────────
        $data = $employees->map(function ($employee) use ($attendances, $processes) {
 
            // ── Hitung total jam kerja ────────────────────────────────────────
            $employeeAttendances = $attendances->get($employee->id, collect());
            $totalMinutes = $employeeAttendances->sum(function ($att) {
                return $att->check_in && $att->check_out
                    ? $att->check_in->diffInMinutes($att->check_out)
                    : 0;
            });
            $totalWorkHours = round($totalMinutes / 60, 2);
 
            // ── Hitung proses & komisi ────────────────────────────────────────
            $employeeProcesses = $processes->get($employee->id, collect());
            $totalCommission   = $employeeProcesses->sum('commision_snapshot');
 
            // Group proses: per flow_name → per satuan_name → sum pieces
            $processGroups = $employeeProcesses
                ->groupBy(fn($p) => $p->serviceFlow->name ?? 'Tidak diketahui')
                ->map(function ($flowProcesses, $flowName) {
                    $quantities = $flowProcesses
                        ->groupBy(fn($p) => $p->satuan->name ?? '-')
                        ->map(fn($satuanProcesses, $satuanName) => [
                            'satuan_name' => $satuanName,
                            'total_qty'   => (int) $satuanProcesses->sum('pieces'),
                        ])
                        ->values();
 
                    return [
                        'flow_name'  => $flowName,
                        'quantities' => $quantities,
                    ];
                })
                ->values();
 
            return [
                'employee_id'      => $employee->id,
                'employee_name'    => $employee->name,
                'employee_code'    => $employee->employee_code,
                'role_name'        => $employee->role->name ?? '-',
                'total_work_hours' => $totalWorkHours,
                'total_commission' => (int) $totalCommission,
                'processes'        => $processGroups,
            ];
        });
 
        $meta = [
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
            'total_employees' => $employees->count(),
        ];
 
        return response()->json([
            'status' => 'success',
            'meta'   => $meta,
            'data'   => $data->values(),
        ]);
    }

    public function deposits(Request $request, $outletId)
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
 
        // ── Ambil semua mutasi topup dalam rentang tanggal ────────────────────
        $mutations = \App\Models\CustomerBalanceMutation::with([
                'customer:id,name,phone,balance',
            ])
            ->where('outlet_id', $outletId)
            ->where('type', 'topup')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
 
        // ── Group per customer ────────────────────────────────────────────────
        $grouped = $mutations->groupBy('customer_id');
 
        $customers = $grouped->map(function ($customerMutations, $customerId) {
            $firstMutation  = $customerMutations->first();
            $customer       = $firstMutation->customer;
            $customerName   = $customer->name  ?? 'Pelanggan Dihapus';
            $customerPhone  = $customer->phone ?? '-';
            $currentBalance = $customer->balance ?? 0;
 
            $mutationList = $customerMutations->map(fn($m) => [
                'id'             => $m->id,
                'amount'         => (int) $m->amount,
                'balance_before' => (int) $m->balance_before,
                'balance_after'  => (int) $m->balance_after,
                'notes'          => $m->notes,
                'created_at'     => $m->created_at,
            ])->values();
 
            return [
                'customer_id'     => $customerId,
                'customer_name'   => $customerName,
                'customer_phone'  => $customerPhone,
                'topup_count'     => $customerMutations->count(),
                'total_topup'     => (int) $customerMutations->sum('amount'),
                'current_balance' => (int) $currentBalance,
                'mutations'       => $mutationList,
            ];
        })
        ->sortByDesc('total_topup')
        ->values();
 
        $meta = [
            'start_date'        => $request->start_date,
            'end_date'          => $request->end_date,
            'total_topup_count' => $mutations->count(),
            'grand_total_topup' => (int) $mutations->sum('amount'),
        ];
 
        return response()->json([
            'status' => 'success',
            'meta'   => $meta,
            'data'   => $customers,
        ]);
    }

    public function dashboardSummary(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
 
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date_format:Y-m-d',
        ]);
 
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }
 
        $date = $request->date ?? now()->format('Y-m-d');
 
        // ── Ambil SEMUA satuan dari master tabel (sumber kebenaran kategori) ───
        // Chart harus selalu menampilkan semua satuan ini, walau nilainya 0.
        $allSatuans = \App\Models\Satuan::orderBy('id')->get(['id', 'name']);
 
        // ── Ambil semua proses pada tanggal terkait, untuk outlet ini ─────────
        $processes = \App\Models\TransactionItemProcess::with(['satuan:id,name'])
            ->whereBetween('started_at', [$date . ' 00:00:00', $date . ' 23:59:59'])
            ->whereHas('transactionItem.transaction', function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId);
            })
            ->get();
 
        // ── Group per transaction_item_id, ambil SATU representasi (first) ────
        // agar item yang sama tidak dihitung berkali-kali tiap melewati flow.
        $uniqueItems = $processes
            ->groupBy('transaction_item_id')
            ->map(fn($group) => $group->first());
 
        // ── Sum qty per satuan_id dari item-item unik tersebut ─────────────────
        $totalsBySatuanId = $uniqueItems
            ->groupBy('satuan_id')
            ->map(fn($items) => (int) $items->sum('pieces'));
 
        // ── Map ke SEMUA satuan dari master, isi 0 jika tidak ada data ─────────
        $bySatuan = $allSatuans->map(fn($satuan) => [
            'satuan_id'   => $satuan->id,
            'satuan_name' => $satuan->name,
            'total_qty'   => $totalsBySatuanId->get($satuan->id, 0),
        ])->values();
 
        return response()->json([
            'status' => 'success',
            'meta'   => ['date' => $date],
            'data'   => $bySatuan,
        ]);
    }

}