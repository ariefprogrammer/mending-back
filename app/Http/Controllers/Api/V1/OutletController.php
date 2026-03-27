<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Outlet;
use Illuminate\Support\Facades\Validator;

class OutletController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();

        $outlets = Outlet::where('user_id', $user->id)->latest()->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar outlet berhasil diambil',
            'data' => $outlets
        ]);
    }

    public function show($id)
    {
        $outlet = Outlet::with('user')->where('id', $id)->first();

        if (!$outlet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Outlet tidak ditemukan.'
            ], 404);
        }

        $user = auth('sanctum')->user();
        
        // Memastikan user hanya bisa melihat outlet miliknya sendiri
        if ($outlet->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk melihat outlet ini.'
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail outlet berhasil ditemukan',
            'data' => $outlet
        ]);
    }

    public function store(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak valid atau tidak terbaca.'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'outlet_code' => 'required|string|unique:outlets,outlet_code',
            'name' => 'required|string|max:255',
            'phone' => 'required|string',
            'province' => 'required',
            'city' => 'required',
            'kecamatan' => 'required',
            'kelurahan' => 'required',
            'address' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $userId = $user->id; 

        $outlet = Outlet::create([
            'outlet_code' => $request->outlet_code,
            'name' => $request->name,
            'user_id' => $userId, 
            'phone' => $request->phone,
            'province' => $request->province,
            'city' => $request->city,
            'kecamatan' => $request->kecamatan,
            'kelurahan' => $request->kelurahan,
            'address' => $request->address,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Outlet created successfully',
            'data' => $outlet
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $outlet = Outlet::find($id);

        if (!$outlet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Outlet tidak ditemukan.'
            ], 404);
        }

        $user = auth('sanctum')->user();
        if ($outlet->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk mengubah outlet ini.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string',
            'province' => 'sometimes|required',
            'city' => 'sometimes|required',
            'kecamatan' => 'sometimes|required',
            'kelurahan' => 'sometimes|required',
            'address' => 'sometimes|required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $outlet->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Outlet updated successfully',
            'data' => $outlet
        ]);
    }

    public function destroy($id)
    {
        $outlet = Outlet::find($id);

        if (!$outlet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Outlet tidak ditemukan.'
            ], 404);
        }

        $user = auth('sanctum')->user();
        if ($outlet->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki otoritas untuk menghapus outlet ini.'
            ], 403);
        }

        $outlet->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Outlet berhasil dihapus.'
        ]);
    }

    public function storeConfiguration(Request $request, $id)
    {
        // 1. Cari outlet dan cek kepemilikan
        $outlet = Outlet::find($id);

        if (!$outlet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Outlet tidak ditemukan.'
            ], 404);
        }

        $user = auth('sanctum')->user();
        if ($outlet->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk mengatur outlet ini.'
            ], 403);
        }

        // 2. Validasi input
        $validator = Validator::make($request->all(), [
            'allow_multiple_services'   => 'required|boolean',
            'allow_duplicate_service'   => 'required|boolean',
            'input_total_pcs_mandatory' => 'required|boolean',
            'process_berurutan'         => 'required|boolean',
            'payment_first'             => 'required|boolean',
            'employee_update_data'      => 'required|boolean',
            'rounding_type'             => 'nullable|string',
            'rounding_multiple'         => 'nullable|integer',
            'is_tax_enabled'            => 'required|boolean',
            'tax_type'                  => 'nullable|string',
            'tax_percentage'            => 'nullable|numeric',
            'delivery_form_url'         => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 3. Simpan atau Update Konfigurasi
        $configuration = $outlet->configuration()->updateOrCreate(
            ['outlet_id' => $id], // Cari berdasarkan outlet_id
            $request->all()       // Data yang akan diupdate/disimpan
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Konfigurasi outlet berhasil disimpan.',
            'data' => $configuration
        ]);
    }

    public function getConfiguration($id)
    {
        // 1. Cari outlet terlebih dahulu untuk pengecekan akses
        $outlet = Outlet::find($id);

        if (!$outlet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Outlet tidak ditemukan.'
            ], 404);
        }

        // 2. Pastikan user yang login adalah pemilik outlet tersebut
        $user = auth('sanctum')->user();
        if ($outlet->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk melihat konfigurasi outlet ini.'
            ], 403);
        }

        // 3. Ambil data konfigurasi melalui relasi
        $configuration = $outlet->configuration;

        if (!$configuration) {
            return response()->json([
                'status' => 'error',
                'message' => 'Konfigurasi untuk outlet ini belum diatur.',
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Konfigurasi outlet berhasil diambil.',
            'data' => $configuration
        ]);
    }
}
