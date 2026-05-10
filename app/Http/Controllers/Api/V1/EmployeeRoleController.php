<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\OutletEmployeeRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeRoleController extends Controller
{
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

    private function findRole(int $outletId, int $id): ?OutletEmployeeRole
    {
        return OutletEmployeeRole::where('outlet_id', $outletId)->find($id);
    }

    // LIST
    public function index($outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        $roles = OutletEmployeeRole::where('outlet_id', $outletId)
                    ->orderBy('id', 'desc')
                    ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $roles,
        ]);
    }

    // DETAIL
    public function show($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $role = $this->findRole($outletId, $id);

        if (!$role) {
            return response()->json(['message' => 'Role tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $role,
        ]);
    }

    // STORE
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

        $role = OutletEmployeeRole::create([
            'outlet_id' => $outletId,
            'name'      => $request->name,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Role berhasil dibuat',
            'data'    => $role,
        ], 201);
    }

    // UPDATE
    public function update(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $role = $this->findRole($outletId, $id);

        if (!$role) {
            return response()->json(['message' => 'Role tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $role->update(['name' => $request->name]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Role berhasil diperbarui',
            'data'    => $role,
        ]);
    }

    // DELETE
    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $role = $this->findRole($outletId, $id);

        if (!$role) {
            return response()->json(['message' => 'Role tidak ditemukan'], 404);
        }

        $role->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Role berhasil dihapus',
        ]);
    }
}