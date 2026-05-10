<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\ExternalOutletCostCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CostCategoryController extends Controller
{
    /**
     * Memastikan user hanya mengelola outlet miliknya sendiri.
     */
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

    public function index($outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $categories = ExternalOutletCostCategory::where('outlet_id', $outletId)
                        ->orderBy('id', 'desc')
                        ->get();

        return response()->json(['status' => 'success', 'data' => $categories]);
    }

    public function store(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category = ExternalOutletCostCategory::create([
            'outlet_id' => $outletId,
            'name' => $request->name
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori pengeluaran berhasil ditambahkan',
            'data' => $category
        ], 201);
    }

    public function update(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $category = ExternalOutletCostCategory::where('outlet_id', $outletId)->find($id);

        if (!$category) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $category->update(['name' => $request->name]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil diperbarui',
            'data' => $category
        ]);
    }

    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $category = ExternalOutletCostCategory::where('outlet_id', $outletId)->find($id);

        if (!$category) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori pengeluaran berhasil dihapus'
        ]);
    }
}