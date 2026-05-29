<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\CashBookMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CashBookMappingController extends Controller
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

    public function index($outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $mappings = CashBookMapping::where('outlet_id', $outletId)
            ->with([
                'paymentMethod:id,name,type',
                'cashBook:id,name',
            ])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $mappings]);
    }

    public function store(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'cash_book_id'      => 'required|integer|exists:outlet_cash_books,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Hindari duplikasi — satu payment method hanya boleh dipetakan ke satu buku kas
        $existing = CashBookMapping::where('outlet_id', $outletId)
            ->where('payment_method_id', $request->payment_method_id)
            ->first();

        if ($existing) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Metode pembayaran ini sudah dipetakan ke buku kas lain. Gunakan update untuk mengubahnya.',
                'data'    => $existing->load(['paymentMethod:id,name,type', 'cashBook:id,name']),
            ], 422);
        }

        $mapping = CashBookMapping::create([
            'outlet_id'         => $outletId,
            'payment_method_id' => $request->payment_method_id,
            'cash_book_id'      => $request->cash_book_id,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Pemetaan buku kas berhasil dibuat',
            'data'    => $mapping->load(['paymentMethod:id,name,type', 'cashBook:id,name']),
        ], 201);
    }

    public function update(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $mapping = CashBookMapping::where('outlet_id', $outletId)->find($id);

        if (!$mapping) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'cash_book_id'      => 'required|integer|exists:outlet_cash_books,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Cek duplikasi — pastikan payment_method_id tidak dipakai mapping lain
        $duplicate = CashBookMapping::where('outlet_id', $outletId)
            ->where('payment_method_id', $request->payment_method_id)
            ->where('id', '!=', $id)
            ->first();

        if ($duplicate) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Metode pembayaran ini sudah dipetakan ke buku kas lain.',
                'data'    => $duplicate->load(['paymentMethod:id,name,type', 'cashBook:id,name']),
            ], 422);
        }

        $mapping->update([
            'payment_method_id' => $request->payment_method_id,
            'cash_book_id'      => $request->cash_book_id,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Pemetaan buku kas berhasil diperbarui',
            'data'    => $mapping->load(['paymentMethod:id,name,type', 'cashBook:id,name']),
        ]);
    }

    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $mapping = CashBookMapping::where('outlet_id', $outletId)->find($id);

        if (!$mapping) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $mapping->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Pemetaan buku kas berhasil dihapus',
        ]);
    }
}