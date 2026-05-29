<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionItemProcess;
use App\Models\ServiceFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionItemProcessController extends Controller
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

    // ─── Mulai Proses ────────────────────────────────────────────────────────
    // POST /outlets/{outletId}/transactions/{transactionId}/processes
    // Body: { service_flow_id, unit_id, pieces, packaging_qty?, rak_info? }
    public function store(Request $request, $outletId, $transactionId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'service_flow_id'   => 'required|exists:service_flows,id',
            'transaction_item_id' => 'required|exists:transaction_items,id',
            'unit_id'           => 'nullable|exists:units,id',
            'pieces'            => 'nullable|integer|min:0',
            'packaging_qty'     => 'nullable|integer|min:0',
            'rak_info'          => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Pastikan transaksi milik outlet ini
        $transaction = Transaction::where('id', $transactionId)
                                  ->where('outlet_id', $outletId)
                                  ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        DB::beginTransaction();
        try {
            $user       = auth('sanctum')->user();
            $employeeId = ($user instanceof \App\Models\Employee) ? $user->id : null;

            // Ambil semua item dalam transaksi ini
            $processes = [];
            
            $item = $transaction->items()
                                ->where('id', $request->transaction_item_id)
                                ->first();

            if (!$item) {
                DB::rollBack();
                return response()->json(['message' => 'Item tidak ditemukan dalam transaksi'], 404);
            }

            $existing = TransactionItemProcess::where('transaction_item_id', $item->id)
                                            ->where('service_flow_id', $request->service_flow_id)
                                            ->first();

            if ($existing) {
                DB::rollBack();
                return response()->json(['message' => 'Proses ini sudah berjalan'], 422);
            }

            $process = TransactionItemProcess::create([
                'transaction_item_id' => $item->id,
                'service_flow_id'     => $request->service_flow_id,
                'employee_id'         => $employeeId,
                'unit_id'             => $request->unit_id,
                'asset_id'            => null,
                'pieces'              => $request->pieces ?? 0,
                'status'              => 'proses',
                'started_at'          => now(),
                'completed_at'        => null,
            ]);

            $processes = [$process];

            // Update pickup_rak_info dan total_packaging_qty di tabel transactions
            // jika dikirim (khusus flow Kemas / Siap Diambil)
            if ($request->filled('packaging_qty') || $request->filled('rak_info')) {
                $transaction->update([
                    'total_packaging_qty' => $request->packaging_qty ?? $transaction->total_packaging_qty,
                    'pickup_rak_info'     => $request->rak_info      ?? $transaction->pickup_rak_info,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Proses berhasil dimulai',
                'data'    => $processes,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan proses',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── Selesaikan Proses ───────────────────────────────────────────────────
    // PUT /outlets/{outletId}/transactions/{transactionId}/processes/{serviceFlowId}
    // Body: { unit_id?, pieces?, packaging_qty?, rak_info? }
    public function update(Request $request, $outletId, $transactionId, $serviceFlowId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'transaction_item_id' => 'required|exists:transaction_items,id',
            'unit_id'       => 'nullable|exists:units,id',
            'pieces'        => 'nullable|integer|min:0',
            'packaging_qty' => 'nullable|integer|min:0',
            'rak_info'      => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $transaction = Transaction::where('id', $transactionId)
                                  ->where('outlet_id', $outletId)
                                  ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        DB::beginTransaction();
        try {
            $updated = [];

            $item = $transaction->items()
                    ->where('id', $request->transaction_item_id)
                    ->first();

            if (!$item) {
                DB::rollBack();
                return response()->json(['message' => 'Item tidak ditemukan dalam transaksi'], 404);
            }

            $process = TransactionItemProcess::where('transaction_item_id', $item->id)
                                            ->where('service_flow_id', $serviceFlowId)
                                            ->first();

            if (!$process) {
                DB::rollBack();
                return response()->json(['message' => 'Proses tidak ditemukan'], 404);
            }

            $process->update([
                'unit_id'      => $request->unit_id ?? $process->unit_id,
                'pieces'       => $request->pieces  ?? $process->pieces,
                'status'       => 'selesai',
                'completed_at' => now(),
            ]);

            $updated = [$process];

            if ($request->filled('packaging_qty') || $request->filled('rak_info')) {
                $transaction->update([
                    'total_packaging_qty' => $request->packaging_qty ?? $transaction->total_packaging_qty,
                    'pickup_rak_info'     => $request->rak_info      ?? $transaction->pickup_rak_info,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Proses berhasil diselesaikan',
                'data'    => $updated,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui proses',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}