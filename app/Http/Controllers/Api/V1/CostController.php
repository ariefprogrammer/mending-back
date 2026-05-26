<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Cost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\TransactionCashBook;
use Illuminate\Support\Facades\DB;

class CostController extends Controller
{
    private function checkAccess(int $outletId): bool
    {
        // Cek apakah yang login adalah owner (dari tabel users)
        $user = auth('sanctum')->user();

        if (!$user) return false;

        // Jika owner — cek apakah outlet miliknya
        if ($user instanceof \App\Models\User) {
            return \App\Models\Outlet::where('id', $outletId)
                                    ->where('user_id', $user->id)
                                    ->exists();
        }

        // Jika employee — cek apakah outlet_id di tabel employees sesuai
        if ($user instanceof \App\Models\Employee) {
            return (int) $user->outlet_id === (int) $outletId;
        }

        return false;
    }

    private function findCost(int $outletId, int $id): ?Cost
    {
        return Cost::where('outlet_id', $outletId)->find($id);
    }

    // LIST COSTS
    public function index(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        $query = Cost::with(['cashBook:id,name', 'category:id,name', 'paymentMethod:id,name'])
                    ->where('outlet_id', $outletId);

        if ($request->filled('cash_book_id')) {
            $query->where('cash_book_id', $request->cash_book_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date   . ' 23:59:59',
            ]);
        }

        $costs = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data'   => $costs,
        ]);
    }

    // DETAIL COST
    public function show($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $cost = Cost::with(['cashBook:id,name', 'category:id,name', 'paymentMethod:id,name'])
                    ->where('outlet_id', $outletId)
                    ->find($id);

        if (!$cost) {
            return response()->json(['message' => 'Data cost tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $cost,
        ]);
    }

    // SIMPAN COST BARU
    public function store(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $validator = Validator::make($request->all(), [
            'cash_book_id'      => 'nullable|exists:outlet_cash_books,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'category_id'       => 'nullable|exists:external_outlet_cost_categories,id',
            'name'              => 'required|string|max:255',
            'unit_name'         => 'required|string|max:100',
            'quantity'          => 'required|numeric|min:0',
            'price'             => 'required|numeric|min:0',
            'catatan'           => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $user   = auth('sanctum')->user();
            $userId = $user instanceof \App\Models\User     ? $user->id : null;
            $empId  = $user instanceof \App\Models\Employee ? $user->id : null;

            $cost = Cost::create([
                'outlet_id'         => $outletId,
                'cash_book_id'      => $request->cash_book_id,
                'payment_method_id' => $request->payment_method_id,
                'category_id'       => $request->category_id,
                'name'              => $request->name,
                'unit_name'         => $request->unit_name,
                'quantity'          => $request->quantity,
                'price'             => $request->price,
                'catatan'           => $request->catatan,
            ]);

            if ($request->filled('cash_book_id')) {
                $cashBookTrx = TransactionCashBook::create([
                    'outlet_cash_book_id'    => $request->cash_book_id,
                    'outlet_id'              => $outletId,
                    'type'                   => 'out',
                    'amount'                 => $request->quantity * $request->price,
                    'description'            => $request->catatan ?? $request->name,
                    'transaction_date'       => now()->toDateString(),
                    'created_by_user_id'     => $userId,
                    'created_by_employee_id' => $empId,
                ]);

                $cost->update(['transaction_cash_book_id' => $cashBookTrx->id]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data cost berhasil disimpan',
                'data'    => $cost->load(['cashBook:id,name', 'category:id,name', 'paymentMethod:id,name']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    // UPDATE COST
    public function update(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $cost = $this->findCost($outletId, $id);

        if (!$cost) {
            return response()->json(['message' => 'Data cost tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'cash_book_id'      => 'nullable|exists:outlet_cash_books,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'category_id'       => 'nullable|exists:external_outlet_cost_categories,id',
            'name'              => 'required|string|max:255',
            'unit_name'         => 'required|string|max:100',
            'quantity'          => 'required|numeric|min:0',
            'price'             => 'required|numeric|min:0',
            'catatan'           => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $user   = auth('sanctum')->user();
            $userId = $user instanceof \App\Models\User     ? $user->id : null;
            $empId  = $user instanceof \App\Models\Employee ? $user->id : null;

            $newAmount     = $request->quantity * $request->price;
            $oldCashBookId = $cost->cash_book_id;
            $newCashBookId = $request->cash_book_id;

            $cost->update([
                'cash_book_id'      => $newCashBookId,
                'payment_method_id' => $request->payment_method_id,
                'category_id'       => $request->category_id,
                'name'              => $request->name,
                'unit_name'         => $request->unit_name,
                'quantity'          => $request->quantity,
                'price'             => $request->price,
                'catatan'           => $request->catatan,
            ]);

            if ($newCashBookId) {
                $cashBookChanged = (int) $oldCashBookId !== (int) $newCashBookId;

                if ($cashBookChanged) {
                    // Hapus transaksi lama jika ada
                    if ($cost->transaction_cash_book_id) {
                        TransactionCashBook::find($cost->transaction_cash_book_id)?->delete();
                    }

                    // Buat transaksi baru di buku kas yang baru
                    $cashBookTrx = TransactionCashBook::create([
                        'outlet_cash_book_id'    => $newCashBookId,
                        'outlet_id'              => $outletId,
                        'type'                   => 'out',
                        'amount'                 => $newAmount,
                        'description'            => $request->catatan ?? $request->name,
                        'transaction_date'       => now()->toDateString(),
                        'created_by_user_id'     => $userId,
                        'created_by_employee_id' => $empId,
                    ]);

                    $cost->update(['transaction_cash_book_id' => $cashBookTrx->id]);

                } else {
                    // Buku kas sama — update transaksi yang sudah ada
                    if ($cost->transaction_cash_book_id) {
                        TransactionCashBook::find($cost->transaction_cash_book_id)?->update([
                            'amount'      => $newAmount,
                            'description' => $request->catatan ?? $request->name,
                        ]);
                    } else {
                        // Sebelumnya tidak ada transaksi, buat baru
                        $cashBookTrx = TransactionCashBook::create([
                            'outlet_cash_book_id'    => $newCashBookId,
                            'outlet_id'              => $outletId,
                            'type'                   => 'out',
                            'amount'                 => $newAmount,
                            'description'            => $request->catatan ?? $request->name,
                            'transaction_date'       => now()->toDateString(),
                            'created_by_user_id'     => $userId,
                            'created_by_employee_id' => $empId,
                        ]);

                        $cost->update(['transaction_cash_book_id' => $cashBookTrx->id]);
                    }
                }
            } else {
                // cash_book_id dikosongkan — hapus transaksi lama jika ada
                if ($cost->transaction_cash_book_id) {
                    TransactionCashBook::find($cost->transaction_cash_book_id)?->delete();
                    $cost->update(['transaction_cash_book_id' => null]);
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data cost berhasil diperbarui',
                'data'    => $cost->load(['cashBook:id,name', 'category:id,name', 'paymentMethod:id,name']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    // HAPUS COST
    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $cost = $this->findCost($outletId, $id);

        if (!$cost) {
            return response()->json(['message' => 'Data cost tidak ditemukan'], 404);
        }

        $cost->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Data cost berhasil dihapus',
        ]);
    }
}