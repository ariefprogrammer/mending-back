<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\OutletAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OutletAssetController extends Controller
{
    private function getVerifiedOutlet($outletId)
    {
        return Outlet::where('id', $outletId)
                     ->where('user_id', auth('sanctum')->id())
                     ->first();
    }

    // LIST ASSETS
    public function index($outletId)
    {
        if (!$this->getVerifiedOutlet($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $assets = OutletAsset::where('outlet_id', $outletId)
                    ->latest()
                    ->get();

        return response()->json(['status' => 'success', 'data' => $assets]);
    }

    // STORE ASSET
    public function store(Request $request, $outletId)
    {
        if (!$this->getVerifiedOutlet($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'process' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $asset = OutletAsset::create([
            'outlet_id' => $outletId,
            'name' => $request->name,
            'process' => $request->process
        ]);

        return response()->json(['status' => 'success', 'data' => $asset], 201);
    }

    // UPDATE ASSET
    public function update(Request $request, $outletId, $id)
    {
        if (!$this->getVerifiedOutlet($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $asset = OutletAsset::where('outlet_id', $outletId)->find($id);
        if (!$asset) return response()->json(['message' => 'Asset not found'], 404);

        $asset->update($request->only(['name', 'process']));

        return response()->json(['status' => 'success', 'data' => $asset]);
    }

    // DELETE ASSET
    public function destroy($outletId, $id)
    {
        if (!$this->getVerifiedOutlet($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $asset = OutletAsset::where('outlet_id', $outletId)->find($id);
        if (!$asset) return response()->json(['message' => 'Asset not found'], 404);

        $asset->delete();
        return response()->json(['status' => 'success', 'message' => 'Asset deleted']);
    }
}