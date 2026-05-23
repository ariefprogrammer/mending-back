<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\OutletMaterial;
use App\Models\OutletMaterialUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OutletMaterialController extends Controller
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

        $materials = OutletMaterial::where('outlet_id', $outletId)
                        ->with(['category', 'satuan'])
                        ->orderBy('id', 'desc')
                        ->get();

        return response()->json(['status' => 'success', 'data' => $materials]);
    }

    public function show($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $material = OutletMaterial::where('outlet_id', $outletId)
                        ->with(['category', 'satuan'])
                        ->find($id);

        if (!$material) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $material]);
    }

    public function store(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'                        => 'required|string|max:100',
            'outlet_material_category_id' => 'nullable|integer|exists:outlet_material_categories,id',
            'satuan_id'                   => 'nullable|integer|exists:satuans,id',
            'min_stock_alert'             => 'nullable|integer|min:0',
            'current_quantity'            => 'nullable|integer|min:0',
            'usages'                      => 'nullable|array',
            'usages.*.satuan_id'          => 'nullable|integer|exists:satuans,id',
            'usages.*.quantity_used_per_unit' => 'required_with:usages|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $material = OutletMaterial::create([
            'outlet_id'                   => $outletId,
            'outlet_material_category_id' => $request->outlet_material_category_id,
            'name'                        => $request->name,
            'satuan_id'                   => $request->satuan_id,
            'min_stock_alert'             => $request->min_stock_alert ?? 0,
            'current_quantity'            => $request->current_quantity ?? 0,
        ]);

        if ($request->filled('usages')) {
            $usages = collect($request->usages)->map(fn($u) => [
                'outlet_material_id'     => $material->id,
                'satuan_id'              => $u['satuan_id'] ?? null,
                'quantity_used_per_unit' => $u['quantity_used_per_unit'],
                'created_at'             => now(),
                'updated_at'             => now(),
            ])->toArray();

            OutletMaterialUsage::insert($usages);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Material berhasil ditambahkan',
            'data'    => $material->load(['category', 'satuan', 'usages.satuan'])
        ], 201);
    }

    public function update(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'                        => 'required|string|max:100',
            'outlet_material_category_id' => 'nullable|integer|exists:outlet_material_categories,id',
            'satuan_id'                   => 'nullable|integer|exists:satuans,id',
            'min_stock_alert'             => 'nullable|integer|min:0',
            'current_quantity'            => 'nullable|integer|min:0',
            'usages'                      => 'nullable|array',
            'usages.*.satuan_id'          => 'nullable|integer|exists:satuans,id',
            'usages.*.quantity_used_per_unit' => 'required_with:usages|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $material = OutletMaterial::where('outlet_id', $outletId)->find($id);

        if (!$material) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $material->update([
            'outlet_material_category_id' => $request->outlet_material_category_id,
            'name'                        => $request->name,
            'satuan_id'                   => $request->satuan_id,
            'min_stock_alert'             => $request->min_stock_alert ?? $material->min_stock_alert,
            'current_quantity'            => $request->current_quantity ?? $material->current_quantity,
        ]);

        if ($request->has('usages')) {
            // Hapus semua usages lama lalu insert yang baru (replace strategy)
            $material->usages()->delete();

            if (!empty($request->usages)) {
                $usages = collect($request->usages)->map(fn($u) => [
                    'outlet_material_id'     => $material->id,
                    'satuan_id'              => $u['satuan_id'] ?? null,
                    'quantity_used_per_unit' => $u['quantity_used_per_unit'],
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ])->toArray();

                OutletMaterialUsage::insert($usages);
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Material berhasil diperbarui',
            'data'    => $material->load(['category', 'satuan', 'usages.satuan'])
        ]);
    }

    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $material = OutletMaterial::where('outlet_id', $outletId)->find($id);

        if (!$material) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $material->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Material berhasil dihapus'
        ]);
    }
}