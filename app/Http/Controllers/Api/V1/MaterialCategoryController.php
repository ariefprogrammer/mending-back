<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\OutletMaterialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaterialCategoryController extends Controller
{
    private ?bool $accessCache = null;
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

        $categories = OutletMaterialCategory::where('outlet_id', $outletId)
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

        $category = OutletMaterialCategory::create([
            'outlet_id' => $outletId,
            'name'      => $request->name,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Kategori material berhasil ditambahkan',
            'data'    => $category
        ], 201);
    }

    public function update(Request $request, $outletId, $id)
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

        $category = OutletMaterialCategory::where('outlet_id', $outletId)->find($id);

        if (!$category) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $category->update(['name' => $request->name]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Kategori material berhasil diperbarui',
            'data'    => $category
        ]);
    }

    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $category = OutletMaterialCategory::where('outlet_id', $outletId)->find($id);

        if (!$category) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $category->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Kategori material berhasil dihapus'
        ]);
    }
}