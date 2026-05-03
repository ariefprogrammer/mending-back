<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegionController extends Controller
{
    /**
     * Helper untuk memastikan user terautentikasi
     * Sama seperti pola di store() OutletController Anda
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

    public function provinces()
    {
        if ($this->checkAuth() instanceof \Illuminate\Http\JsonResponse) return $this->checkAuth();

        $provinces = DB::table('provinces')->orderBy('name', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar provinsi berhasil diambil',
            'data' => $provinces
        ]);
    }

    public function kabupatens(Request $request)
    {
        if ($this->checkAuth() instanceof \Illuminate\Http\JsonResponse) return $this->checkAuth();

        $provinceId = $request->query('province_id');

        if (!$provinceId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter province_id diperlukan.'
            ], 400);
        }

        $kabupatens = DB::table('kabupatens')
            ->where('province_id', $provinceId)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar kabupaten berhasil diambil',
            'data' => $kabupatens
        ]);
    }

    public function kecamatans(Request $request)
    {
        if ($this->checkAuth() instanceof \Illuminate\Http\JsonResponse) return $this->checkAuth();

        $kabupatenId = $request->query('kabupaten_id');

        if (!$kabupatenId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter kabupaten_id diperlukan.'
            ], 400);
        }

        $kecamatans = DB::table('kecamatans')
            ->where('kabupaten_id', $kabupatenId)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar kecamatan berhasil diambil',
            'data' => $kecamatans
        ]);
    }

    public function kelurahans(Request $request)
    {
        if ($this->checkAuth() instanceof \Illuminate\Http\JsonResponse) return $this->checkAuth();

        $kecamatanId = $request->query('kecamatan_id');

        if (!$kecamatanId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter kecamatan_id diperlukan.'
            ], 400);
        }

        $kelurahans = DB::table('kelurahans')
            ->where('kecamatan_id', $kecamatanId)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar kelurahan berhasil diambil',
            'data' => $kelurahans
        ]);
    }
}