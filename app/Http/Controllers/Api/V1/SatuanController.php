<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Satuan;

class SatuanController extends Controller
{
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

    // LIST SEMUA SATUAN
    public function index()
    {
        if ($this->checkAuth() instanceof \Illuminate\Http\JsonResponse) return $this->checkAuth();

        $satuans = Satuan::orderBy('type')->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $satuans
        ]);
    }

    // DETAIL SATUAN
    public function show($id)
    {
        if ($this->checkAuth() instanceof \Illuminate\Http\JsonResponse) return $this->checkAuth();
        
        $satuan = Satuan::find($id);
        if (!$satuan) return response()->json(['message' => 'Satuan tidak ditemukan'], 404);

        return response()->json([
            'status' => 'success',
            'data' => $satuan
        ]);
    }
}