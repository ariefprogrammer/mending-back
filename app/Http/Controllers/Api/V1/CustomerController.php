<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Imports\CustomersImport;
use App\Exports\CustomersExport;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
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

    // LIST CUSTOMER
    public function index(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $query = Customer::where('outlet_id', $outletId);

        if ($request->filled('type')) {
            $query->where('customer_type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query
            ->withCount('transactions')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $customers,
        ]);
    }

    // DETAIL CUSTOMER
    public function show($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $customer = Customer::where('outlet_id', $outletId)->find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer tidak ditemukan'], 404);
        }

        // Agregasi transaksi
        $transactions = Transaction::where('customer_id', $customer->id);

        $totalAmount    = $transactions->clone()->sum('grand_total');
        $unpaidAmount   = $transactions->clone()->where('payment_status', 'unpaid')->sum('grand_total');
        $paidAmount     = $transactions->clone()->where('payment_status', 'paid')->sum('grand_total');
        $totalCount     = $transactions->clone()->count();
        $firstTrxDate   = $transactions->clone()->orderBy('created_at')->value('created_at');

        return response()->json([
            'status' => 'success',
            'data'   => array_merge($customer->toArray(), [
                'total_amount'         => $totalAmount,
                'unpaid_amount'        => $unpaidAmount,
                'paid_amount'          => $paidAmount,
                'transactions_count'   => $totalCount,
                'first_transaction_at' => $firstTrxDate,
            ]),
        ]);
    }

    // SIMPAN CUSTOMER
    public function store(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $validator = Validator::make($request->all(), [
            'customer_type' => 'required|string|max:50',
            'name'          => 'required|string|max:255',
            'phone'         => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:255',
            'address'       => 'nullable|string',
            'url_address'   => 'nullable|url',
            'balance'       => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $customer = Customer::create([
            'outlet_id'     => $outletId,
            'customer_type' => $request->customer_type,
            'name'          => $request->name,
            'phone'         => $request->phone,
            'email'         => $request->email,
            'address'       => $request->address,
            'url_address'   => $request->url_address,
            'balance'       => $request->balance ?? 0,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer berhasil ditambahkan',
            'data'    => $customer,
        ], 201);
    }

    // UPDATE CUSTOMER
    public function update(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $customer = Customer::where('outlet_id', $outletId)->find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_type' => 'sometimes|required|string|max:50',
            'name'          => 'sometimes|required|string|max:255',
            'phone'         => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:255',
            'address'       => 'nullable|string',
            'url_address'   => 'nullable|url',
            'balance'       => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $customer->update([
            'customer_type' => $request->customer_type ?? $customer->customer_type,
            'name'          => $request->name          ?? $customer->name,
            'phone'         => $request->phone         ?? $customer->phone,
            'email'         => $request->email         ?? $customer->email,
            'address'       => $request->address       ?? $customer->address,
            'url_address'   => $request->url_address   ?? $customer->url_address,
            'balance'       => $request->balance       ?? $customer->balance,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer berhasil diperbarui',
            'data'    => $customer,
        ]);
    }

    // DELETE CUSTOMER
    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $customer = Customer::where('outlet_id', $outletId)->find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer tidak ditemukan'], 404);
        }

        $customer->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer berhasil dihapus',
        ]);
    }

    public function import(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new CustomersImport((int) $outletId);
        Excel::import($import, $request->file('file'));

        // Format failures dari validasi (maatwebsite)
        $validationFailures = collect($import->failures())->map(function ($failure) {
            return [
                'row'     => $failure->row(),
                'reason'  => implode(', ', $failure->errors()),
                'values'  => $failure->values(),
            ];
        });

        // Format duplikat
        $duplicateFailures = collect($import->getSkippedDuplicates())->map(function ($item) {
            return [
                'row'    => null, // optional: bisa di-track index aslinya kalau perlu
                'reason' => $item['reason'],
                'values' => $item['row'],
            ];
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Import selesai',
            'data'    => [
                'imported_count' => $import->getImportedCount(),
                'failed_count'   => $validationFailures->count() + $duplicateFailures->count(),
                'failed_rows'    => $validationFailures->merge($duplicateFailures)->values(),
            ],
        ]);
    }

    public function export(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $request->validate([
            'type' => 'nullable|string|in:individu,rekanan',
        ]);

        $fileName = 'pelanggan_' . ($request->type ?? 'semua') . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new CustomersExport((int) $outletId, $request->type),
            $fileName
        );
    }
    
}