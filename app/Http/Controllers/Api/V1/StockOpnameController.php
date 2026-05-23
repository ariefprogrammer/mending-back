<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\StockOpname;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StockOpnameController extends Controller
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

        $stockOpnames = StockOpname::where('outlet_id', $outletId)
                            ->with([
                                'user',
                                'items.material.satuan',  
                                'items.material.category', 
                            ])
                            ->orderBy('id', 'desc')
                            ->get();

        return response()->json(['status' => 'success', 'data' => $stockOpnames]);
    }

    public function store(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        // Ambil semua bahan milik outlet ini
        $materials = \App\Models\OutletMaterial::where('outlet_id', $outletId)
                        ->select('id', 'current_quantity')
                        ->get();

        if ($materials->isEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tidak ada data bahan untuk dicatat. Tambahkan bahan terlebih dahulu.'
            ], 422);
        }

        $user   = auth('sanctum')->user();
        $userId = ($user instanceof \App\Models\User) ? $user->id : null;

        // Buat header stock opname
        $stockOpname = StockOpname::create([
            'outlet_id' => $outletId,
            'user_id'   => $userId,
        ]);

        // Susun items dari semua bahan — physical_quantity & difference default 0
        $items = $materials->map(fn($material) => [
            'stock_opname_id'    => $stockOpname->id,
            'outlet_material_id' => $material->id,
            'system_quantity'    => $material->current_quantity,
            'physical_quantity'  => 0,
            'difference'         => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ])->toArray();

        \App\Models\StockOpnameItem::insert($items);

        return response()->json([
            'status'  => 'success',
            'message' => 'Stock opname berhasil dibuat',
            'data'    => $stockOpname->load(['user', 'items.material'])
        ], 201);
    }

    public function show($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $stockOpname = StockOpname::where('outlet_id', $outletId)
                            ->with([
                                'user',
                                'items.material.satuan',
                                'items.material.category',
                            ])
                            ->find($id);

        if (!$stockOpname) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $stockOpname]);
    }

    // update qty fisik
    public function updateItems(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $stockOpname = StockOpname::where('outlet_id', $outletId)->find($id);

        if (!$stockOpname) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'items'                    => 'required|array|min:1',
            'items.*.id'               => 'required|integer|exists:stock_opname_items,id',
            'items.*.physical_quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        foreach ($request->items as $item) {
            $opnameItem = \App\Models\StockOpnameItem::where('stock_opname_id', $stockOpname->id)
                            ->find($item['id']);

            if (!$opnameItem) continue;

            $physical   = $item['physical_quantity'];
            $difference = $physical - $opnameItem->system_quantity;

            $opnameItem->update([
                'physical_quantity' => $physical,
                'difference'        => $difference,
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Stock opname berhasil diperbarui',
            'data'    => $stockOpname->load([
                'user',
                'items.material.satuan',
                'items.material.category',
            ])
        ]);
    }

    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $stockOpname = StockOpname::where('outlet_id', $outletId)->find($id);

        if (!$stockOpname) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $stockOpname->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Stock opname berhasil dihapus'
        ]);
    }
}