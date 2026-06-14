<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomerItemReport;
use App\Models\Outlet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CustomerItemReportController extends Controller
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

    // ─── GET /outlets/{outletId}/customer-item-reports ─────────────────────────
    public function index(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $query = CustomerItemReport::with([
                'transaction:id,transaction_code,customer_id,customer_name',
                'transaction.customer:id,name,phone',
                'createdByUser:id,name',
                'createdByEmployee:id,name',
            ])
            ->where('outlet_id', $outletId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('transaction', function ($t) use ($search) {
                      $t->where('transaction_code', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                  });
            });
        }

        $reports = $query->orderBy('created_at', 'desc')
                          ->paginate($request->per_page ?? 15);

        return response()->json([
            'status' => 'success',
            'data'   => $reports,
        ]);
    }

    // ─── GET /outlets/{outletId}/customer-item-reports/{id} ─────────────────────
    public function show($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $report = CustomerItemReport::with([
                'transaction:id,transaction_code,customer_id,customer_name',
                'transaction.customer:id,name,phone',
                'createdByUser:id,name',
                'createdByEmployee:id,name',
            ])
            ->where('outlet_id', $outletId)
            ->where('id', $id)
            ->first();

        if (!$report) {
            return response()->json(['status' => 'error', 'message' => 'Formulir tidak ditemukan'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $report]);
    }

    // ─── POST /outlets/{outletId}/customer-item-reports ─────────────────────────
    // Body (multipart/form-data):
    //   transaction_id (required), description (required),
    //   signature (file, optional), image (file, optional), status (optional)
    public function store(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transactions,id',
            'description'    => 'required|string',
            'image'          => 'nullable|image|max:2048',
            'status'         => 'nullable|in:' . implode(',', CustomerItemReport::STATUSES),
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        // Pastikan transaksi milik outlet ini
        $transaction = Transaction::where('id', $request->transaction_id)
                                  ->where('outlet_id', $outletId)
                                  ->first();

        if (!$transaction) {
            return response()->json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan di outlet ini'], 404);
        }

        DB::beginTransaction();
        try {
            $user       = auth('sanctum')->user();
            $userId     = ($user instanceof \App\Models\User)     ? $user->id : null;
            $employeeId = ($user instanceof \App\Models\Employee) ? $user->id : null;

            $imagePath = null;

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')
                    ->store("outlets/{$outletId}/customer-item-reports/images", 'public');
            }

            $report = CustomerItemReport::create([
                'outlet_id'              => $outletId,
                'transaction_id'         => $request->transaction_id,
                'description'            => $request->description,
                'image'                  => $imagePath,
                'status'                 => $request->status ?? 'draft',
                'created_by_user_id'     => $userId,
                'created_by_employee_id' => $employeeId,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Formulir berhasil dibuat',
                'data'    => $report,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menyimpan formulir',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── PUT/POST /outlets/{outletId}/customer-item-reports/{id} ────────────────
    // Body (multipart/form-data):
    //   description, signature (file), image (file), status — semua optional
    public function update(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $report = CustomerItemReport::where('outlet_id', $outletId)
                                    ->where('id', $id)
                                    ->first();

        if (!$report) {
            return response()->json(['status' => 'error', 'message' => 'Formulir tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'description'        => 'nullable|string',
            'image'              => 'nullable|image|max:2048',
            'pickup_proof_image' => 'nullable|image|max:2048',
            'status'             => 'nullable|in:' . implode(',', CustomerItemReport::STATUSES),
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $updateData = [];

            if ($request->filled('description')) {
                $updateData['description'] = $request->description;
            }

            if ($request->filled('status')) {
                $updateData['status'] = $request->status;
            }

            if ($request->hasFile('image')) {
                if ($report->image) {
                    Storage::disk('public')->delete($report->image);
                }
                $updateData['image'] = $request->file('image')
                    ->store("outlets/{$outletId}/customer-item-reports/images", 'public');
            }

            if ($request->hasFile('pickup_proof_image')) {
                if ($report->pickup_proof_image) {
                    Storage::disk('public')->delete($report->pickup_proof_image);
                }
                $updateData['pickup_proof_image'] = $request->file('pickup_proof_image')
                    ->store("outlets/{$outletId}/customer-item-reports/pickup-proofs", 'public');

                $updateData['status'] = 'selesai';
            }

            $report->update($updateData);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Formulir berhasil diperbarui',
                'data'    => $report,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui formulir',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── DELETE /outlets/{outletId}/customer-item-reports/{id} ──────────────────
    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $report = CustomerItemReport::where('outlet_id', $outletId)
                                    ->where('id', $id)
                                    ->first();

        if (!$report) {
            return response()->json(['status' => 'error', 'message' => 'Formulir tidak ditemukan'], 404);
        }

        if ($report->signature) {
            Storage::disk('public')->delete($report->signature);
        }
        if ($report->image) {
            Storage::disk('public')->delete($report->image);
        }

        $report->delete();

        return response()->json(['status' => 'success', 'message' => 'Formulir berhasil dihapus']);
    }
}