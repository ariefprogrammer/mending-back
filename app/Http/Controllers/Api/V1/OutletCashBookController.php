<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\OutletCashBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OutletCashBookController extends Controller
{
    // Helper untuk validasi kepemilikan outlet (Security)
    private function validateOutletOwnership($outletId)
    {
        $user = auth('sanctum')->user();
        $outlet = Outlet::where('id', $outletId)->where('user_id', $user->id)->first();
        return $outlet;
    }

    // LIST: Menampilkan semua buku kas di satu outlet
    public function index($outletId)
    {
        if (!$this->validateOutletOwnership($outletId)) {
            return response()->json(['message' => 'Unauthorized or Outlet not found'], 403);
        }

        $cashBooks = OutletCashBook::where('outlet_id', $outletId)->latest()->get();
        return response()->json(['status' => 'success', 'data' => $cashBooks]);
    }

    // STORE: Membuat buku kas baru
    public function store(Request $request, $outletId)
    {
        if (!$this->validateOutletOwnership($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $cashBook = OutletCashBook::create([
            'outlet_id' => $outletId,
            'name' => $request->name
        ]);

        return response()->json(['status' => 'success', 'data' => $cashBook], 201);
    }

    // UPDATE: Mengubah nama buku kas
    public function update(Request $request, $outletId, $id)
    {
        if (!$this->validateOutletOwnership($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cashBook = OutletCashBook::where('outlet_id', $outletId)->find($id);
        if (!$cashBook) {
            return response()->json(['message' => 'Cash book not found'], 404);
        }

        $cashBook->update(['name' => $request->name]);

        return response()->json(['status' => 'success', 'data' => $cashBook]);
    }

    // DELETE: Menghapus buku kas
    public function destroy($outletId, $id)
    {
        if (!$this->validateOutletOwnership($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cashBook = OutletCashBook::where('outlet_id', $outletId)->find($id);
        if (!$cashBook) {
            return response()->json(['message' => 'Cash book not found'], 404);
        }

        $cashBook->delete();
        return response()->json(['status' => 'success', 'message' => 'Cash book deleted']);
    }
}