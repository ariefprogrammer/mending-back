<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentMethodController extends Controller
{
    /**
     * Helper untuk validasi akses outlet
     */
    private function checkAccess($outletId)
    {
        $user = auth('sanctum')->user();
        return Outlet::where('id', $outletId)->where('user_id', $user->id)->exists();
    }

    // LIST PAYMENT METHODS
    public function index(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau Outlet tidak ditemukan'], 403);
        }

        $query = PaymentMethod::where('outlet_id', $outletId);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $paymentMethods = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $paymentMethods
        ]);
    }

    // SIMPAN PAYMENT METHOD BARU
    public function store(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'type' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $paymentMethod = PaymentMethod::create([
            'outlet_id' => $outletId,
            'name'      => $request->name,
            'type'      => $request->type,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Payment method berhasil dibuat',
            'data'    => $paymentMethod
        ], 201);
    }

    // UPDATE PAYMENT METHOD
    public function update(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $paymentMethod = PaymentMethod::where('outlet_id', $outletId)->find($id);

        if (!$paymentMethod) {
            return response()->json(['message' => 'Payment method tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'type' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $paymentMethod->update([
            'name' => $request->name,
            'type' => $request->type
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Payment method berhasil diperbarui',
            'data'    => $paymentMethod
        ]);
    }

    // HAPUS PAYMENT METHOD
    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $paymentMethod = PaymentMethod::where('outlet_id', $outletId)->find($id);

        if (!$paymentMethod) {
            return response()->json(['message' => 'Payment method tidak ditemukan'], 404);
        }

        $paymentMethod->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Payment method berhasil dihapus'
        ]);
    }
}