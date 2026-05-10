<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\OutletServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceCategoryController extends Controller
{
    /**
     * Helper untuk memvalidasi apakah outlet milik user yang sedang login
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

    // LIST KATEGORI
    public function index($outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Unauthorized or Outlet not found'], 403);
        }

        $categories = OutletServiceCategory::where('outlet_id', $outletId)
                        ->orderBy('id', 'desc')
                        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    // STORE KATEGORI
    public function store(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:100',
                // Validasi agar nama kategori tidak duplikat di outlet yang sama
                Rule::unique('outlet_service_categories')->where(function ($query) use ($outletId) {
                    return $query->where('outlet_id', $outletId);
                }),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category = OutletServiceCategory::create([
            'outlet_id' => $outletId,
            'name' => $request->name
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori layanan berhasil dibuat',
            'data' => $category
        ], 201);
    }

    // UPDATE KATEGORI
    public function update(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $category = OutletServiceCategory::where('outlet_id', $outletId)->find($id);
        if (!$category) return response()->json(['message' => 'Category not found'], 404);

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('outlet_service_categories')->where(function ($query) use ($outletId) {
                    return $query->where('outlet_id', $outletId);
                })->ignore($id), // Abaikan ID kategori saat ini saat cek unik
            ],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category->update(['name' => $request->name]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil diperbarui',
            'data' => $category
        ]);
    }

    // DELETE KATEGORI
    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $category = OutletServiceCategory::where('outlet_id', $outletId)->find($id);
        if (!$category) return response()->json(['message' => 'Category not found'], 404);

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori layanan berhasil dihapus'
        ]);
    }
}