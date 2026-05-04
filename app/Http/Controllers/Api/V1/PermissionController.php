<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Helper untuk memastikan user terautentikasi
     */
    private function checkAuth()
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak valid atau tidak terbaca.'
            ], 401);
        }
        return $user;
    }

    /**
     * Menampilkan semua data permissions (global, bukan per outlet)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Cek autentikasi
        if ($this->checkAuth() instanceof \Illuminate\Http\JsonResponse) {
            return $this->checkAuth();
        }

        // Ambil semua permissions, urutkan berdasarkan module lalu action
        $permissions = Permission::orderBy('module', 'asc')
            ->orderBy('action', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar permissions berhasil diambil',
            'data' => $permissions
        ]);
    }

    /**
     * Menampilkan detail satu permission berdasarkan ID
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Cek autentikasi
        if ($this->checkAuth() instanceof \Illuminate\Http\JsonResponse) {
            return $this->checkAuth();
        }

        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permission tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail permission berhasil diambil',
            'data' => $permission
        ]);
    }

    /**
     * Menampilkan permissions berdasarkan module
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function byModule(Request $request)
    {
        // Cek autentikasi
        if ($this->checkAuth() instanceof \Illuminate\Http\JsonResponse) {
            return $this->checkAuth();
        }

        $module = $request->query('module');

        if (!$module) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter module diperlukan.'
            ], 400);
        }

        $permissions = Permission::where('module', $module)
            ->orderBy('action', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => "Daftar permissions untuk module '{$module}' berhasil diambil",
            'data' => $permissions
        ]);
    }
}