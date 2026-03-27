<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\ExternalOutletRevenueCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RevenueCategoryController extends Controller
{
    /**
     * Helper untuk validasi akses outlet
     */
    private function checkAccess($outletId)
    {
        $user = auth('sanctum')->user();
        return Outlet::where('id', $outletId)->where('user_id', $user->id)->exists();
    }

    // LIST KATEGORI
    public function index($outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau Outlet tidak ditemukan'], 403);
        }

        $categories = ExternalOutletRevenueCategory::where('outlet_id', $outletId)
                        ->orderBy('id', 'desc')
                        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    // SIMPAN KATEGORI BARU
    public function store(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category = ExternalOutletRevenueCategory::create([
            'outlet_id' => $outletId,
            'name' => $request->name
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori pendapatan berhasil dibuat',
            'data' => $category
        ], 201);
    }

    // UPDATE KATEGORI
    public function update(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $category = ExternalOutletRevenueCategory::where('outlet_id', $outletId)->find($id);

        if (!$category) {
            return response()->json(['message' => 'Kategori tidak ditemukan'], 404);
        }

        $category->update(['name' => $request->name]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil diperbarui',
            'data' => $category
        ]);
    }

    // HAPUS KATEGORI
    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $category = ExternalOutletRevenueCategory::where('outlet_id', $outletId)->find($id);

        if (!$category) {
            return response()->json(['message' => 'Kategori tidak ditemukan'], 404);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil dihapus'
        ]);
    }
}