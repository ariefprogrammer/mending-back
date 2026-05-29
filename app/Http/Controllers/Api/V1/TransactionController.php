<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\TransactionCashBook;
use App\Models\CashBookMapping;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    private ?bool $accessCache   = null;
    private ?int $cachedOutletId = null;

    private function checkAccess(int $outletId): bool
    {
        if ($this->accessCache !== null && $this->cachedOutletId === $outletId) {
            return $this->accessCache;
        }

        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->accessCache = false;
        }

        $this->cachedOutletId = $outletId;

        if ($user instanceof \App\Models\User) {
            return $this->accessCache = Outlet::where('id', $outletId)
                                              ->where('user_id', $user->id)
                                              ->exists();
        }

        if ($user instanceof \App\Models\Employee) {
            return $this->accessCache = (int) $user->outlet_id === (int) $outletId;
        }

        return $this->accessCache = false;
    }

    // ─── PRIVATE: PROCESS PAYMENT ────────────────────────────────

    private function processPayment(Transaction $transaction, array $paymentData): TransactionPayment
    {
        $amountPaid   = $paymentData['amount_paid'];
        $changeAmount = max(0, $amountPaid - $transaction->grand_total);

        // Buat record pembayaran
        $payment = TransactionPayment::create([
            'id'                => Str::uuid()->toString(),
            'transaction_id'    => $transaction->id,
            'payment_method_id' => $paymentData['payment_method_id'],
            'cash_book_id'      => $paymentData['cash_book_id'] ?? null,
            'amount_paid'       => $amountPaid,
            'change_amount'     => $changeAmount,
            'status'            => 'paid',
        ]);

        // Hitung total yang sudah dibayar — update payment_status
        $totalPaid = TransactionPayment::where('transaction_id', $transaction->id)
            ->where('status', 'paid')
            ->sum('amount_paid');

        $transaction->update([
            'payment_status' => $totalPaid >= $transaction->grand_total ? 'paid' : 'partial',
        ]);

        // Catat ke buku kas jika cash_book_id disertakan
        if (!empty($paymentData['cash_book_id'])) {
            $user = auth('sanctum')->user();

            TransactionCashBook::create([
                'outlet_cash_book_id'    => $paymentData['cash_book_id'],
                'outlet_id'              => $transaction->outlet_id,
                'type'                   => 'in',
                'amount'                 => $amountPaid - $changeAmount,
                'description'            => "Pembayaran transaksi {$transaction->transaction_code}",
                'transaction_date'       => now()->toDateString(),
                'created_by_user_id'     => $user instanceof \App\Models\User     ? $user->id : null,
                'created_by_employee_id' => $user instanceof \App\Models\Employee ? $user->id : null,
            ]);
        }

        return $payment;
    }

    // ─── INDEX ───────────────────────────────────────────────────

    public function index(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $query = Transaction::where('outlet_id', $outletId)
            ->with([
                'customer:id,name,phone',
                'createdByUser:id,name',
                'createdByEmployee:id,name',
                'items.service:id,name,satuan_id',
                'items.service.satuan:id,name',
                'items.service.flows:id,service_id,name,sequence,is_active',
                'items.processes:id,transaction_item_id,service_flow_id,status', 
                'payments.paymentMethod:id,name',
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date   . ' 23:59:59',
            ]);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_code', 'like', "%{$search}%")
                  ->orWhere('customer_name',  'like', "%{$search}%")
                  ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json(['status' => 'success', 'data' => $transactions]);
    }

    // ─── SHOW ────────────────────────────────────────────────────

    public function show($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $transaction = Transaction::where('outlet_id', $outletId)
            ->with([
                    'customer:id,name,phone',
                    'createdByUser:id,name',
                    'createdByEmployee:id,name',
                    'items.service:id,name,satuan_id',
                    'items.service.satuan:id,name',
                    'items.service.flows:id,service_id,name,sequence,is_active',
                    'items.processes:id,transaction_item_id,service_flow_id,status', 
                    'payments.paymentMethod:id,name',
                ])
            ->find($id);

        if (!$transaction) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $transaction]);
    }

    // ─── STORE ───────────────────────────────────────────────────

    public function store(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'customer_id'              => 'nullable|exists:customers,id',
            'customer_name'            => 'required_without:customer_id|nullable|string|max:100',
            'subtotal'                 => 'required|integer|min:0',
            'discount'                 => 'nullable|integer|min:0',
            'tax_amount'               => 'nullable|integer|min:0',
            'grand_total'              => 'required|integer|min:0',
            'pickup_rak_info'          => 'nullable|string|max:100',
            'total_packaging_qty'      => 'nullable|integer|min:0',
            'estimated_completion'     => 'nullable|date',

            // Items — opsional tapi jika ada harus valid
            'items'              => 'nullable|array',
            'items.*.service_id' => 'required_with:items|integer|exists:services,id',
            'items.*.qty'        => 'required_with:items|numeric|min:0.01',
            'items.*.price'      => 'required_with:items|integer|min:0',

            // Payment — opsional
            'payment'                       => 'nullable|array',
            'payment.payment_method_id'     => 'required_with:payment|integer|exists:payment_methods,id',
            'payment.amount_paid'           => 'required_with:payment|integer|min:0',
            'payment.cash_book_id'          => 'nullable|integer|exists:outlet_cash_books,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Jika customer_id diisi, ambil nama dari relasi
        $customerName = $request->customer_name;
        if ($request->filled('customer_id')) {
            $customer     = Customer::find($request->customer_id);
            $customerName = $customer?->name;
        }

        $user       = auth('sanctum')->user();
        $userId     = $user instanceof \App\Models\User     ? $user->id : null;
        $employeeId = $user instanceof \App\Models\Employee ? $user->id : null;

        try {
            DB::beginTransaction();

            $transaction = Transaction::create([
                'id'                   => Str::uuid()->toString(),
                'outlet_id'            => $outletId,
                'customer_id'          => $request->customer_id,
                'customer_name'        => $customerName,
                'user_id'              => $userId,    
                'employee_id'          => $employeeId,
                'transaction_code'     => $this->generateTransactionCode($outletId),
                'status'               => 'pending',
                'payment_status'       => 'unpaid',
                'subtotal'             => $request->subtotal,
                'discount'             => $request->discount        ?? 0,
                'tax_amount'           => $request->tax_amount      ?? 0,
                'grand_total'          => $request->grand_total,
                'pickup_rak_info'      => $request->pickup_rak_info,
                'total_packaging_qty'  => $request->total_packaging_qty ?? 0,
                'estimated_completion' => $request->estimated_completion,
            ]);

            // Insert transaction items
            if ($request->filled('items')) {
                $items = collect($request->items)->map(fn($item) => [
                    'id'             => Str::uuid()->toString(),
                    'transaction_id' => $transaction->id,
                    'service_id'     => $item['service_id'],
                    'qty'            => $item['qty'],
                    'price'          => $item['price'],
                    'subtotal'       => (int) ($item['price'] * $item['qty']),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ])->toArray();

                \App\Models\TransactionItem::insert($items);
            }

            // Proses payment jika disertakan
            if ($request->filled('payment')) {
                $this->processPayment($transaction, $request->payment);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi berhasil dibuat',
                'data'    => $transaction->load([
                    'customer:id,name,phone',
                    'items',
                    'payments.paymentMethod:id,name',
                ]),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Store Transaction Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menyimpan transaksi',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── PAY ─────────────────────────────────────────────────────

    /**
     * Endpoint pembayaran terpisah — untuk bayar belakangan
     * POST /outlets/{outletId}/transactions/{id}/pay
     */
    public function pay(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $transaction = Transaction::where('outlet_id', $outletId)->find($id);

        if (!$transaction) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        if ($transaction->payment_status === 'paid') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaksi ini sudah lunas',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'amount_paid'       => 'required|integer|min:1',
            'cash_book_id'      => 'nullable|integer|exists:outlet_cash_books,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            DB::beginTransaction();

            $this->processPayment($transaction, [
                'payment_method_id' => $request->payment_method_id,
                'amount_paid'       => $request->amount_paid,
                'cash_book_id'      => $request->cash_book_id,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Pembayaran berhasil dicatat',
                'data'    => $transaction->fresh()->load([
                    'customer:id,name,phone',
                    'items',
                    'payments.paymentMethod:id,name',
                ]),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Payment Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memproses pembayaran',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── UPDATE ──────────────────────────────────────────────────

    public function update(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $transaction = Transaction::where('outlet_id', $outletId)->find($id);

        if (!$transaction) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_id'          => 'nullable|exists:customers,id',
            'customer_name'        => 'nullable|string|max:100',
            'status'               => 'nullable|string|max:50',
            'payment_status'       => 'nullable|string|max:50',
            'subtotal'             => 'nullable|integer|min:0',
            'discount'             => 'nullable|integer|min:0',
            'tax_amount'           => 'nullable|integer|min:0',
            'grand_total'          => 'nullable|integer|min:0',
            'pickup_rak_info'      => 'nullable|string|max:100',
            'total_packaging_qty'  => 'nullable|integer|min:0',
            'estimated_completion' => 'nullable|date',
            'completed_at'         => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $customerName = $request->customer_name ?? $transaction->customer_name;
        if ($request->filled('customer_id') && $request->customer_id !== $transaction->customer_id) {
            $customer     = Customer::find($request->customer_id);
            $customerName = $customer?->name;
        }

        $transaction->update([
            'customer_id'          => $request->customer_id          ?? $transaction->customer_id,
            'customer_name'        => $customerName,
            'status'               => $request->status               ?? $transaction->status,
            'payment_status'       => $request->payment_status       ?? $transaction->payment_status,
            'subtotal'             => $request->subtotal             ?? $transaction->subtotal,
            'discount'             => $request->discount             ?? $transaction->discount,
            'tax_amount'           => $request->tax_amount           ?? $transaction->tax_amount,
            'grand_total'          => $request->grand_total          ?? $transaction->grand_total,
            'pickup_rak_info'      => $request->pickup_rak_info      ?? $transaction->pickup_rak_info,
            'total_packaging_qty'  => $request->total_packaging_qty  ?? $transaction->total_packaging_qty,
            'estimated_completion' => $request->estimated_completion ?? $transaction->estimated_completion,
            'completed_at'         => $request->completed_at         ?? $transaction->completed_at,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Transaksi berhasil diperbarui',
            'data'    => $transaction->load([
                'customer:id,name,phone',
                'items',
                'payments.paymentMethod:id,name',
            ]),
        ]);
    }

    // ─── DESTROY ─────────────────────────────────────────────────

    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $transaction = Transaction::where('outlet_id', $outletId)->find($id);

        if (!$transaction) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $transaction->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Transaksi berhasil dihapus',
        ]);
    }

    // ─── HELPER ──────────────────────────────────────────────────

    private function generateTransactionCode(int $outletId): string
    {
        $date   = now()->format('Ymd');
        $prefix = "TRX-{$outletId}-{$date}-";

        $last = Transaction::where('outlet_id', $outletId)
            ->where('transaction_code', 'like', "{$prefix}%")
            ->orderBy('transaction_code', 'desc')
            ->value('transaction_code');

        $nextNumber = $last
            ? (int) substr($last, strrpos($last, '-') + 1) + 1
            : 1;

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}