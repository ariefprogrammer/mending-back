<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Outlet;
use App\Models\Province;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use Illuminate\Support\Facades\Validator;

class OutletController extends Controller
{
    private function checkAccess(int $outletId): bool
    {
        $user = auth('sanctum')->user();

        if (!$user) return false;

        if ($user instanceof \App\Models\User) {
            return \App\Models\Outlet::where('id', $outletId)
                                    ->where('user_id', $user->id)
                                    ->exists();
        }

        if ($user instanceof \App\Models\Employee) {
            return (int) $user->outlet_id === (int) $outletId;
        }

        return false;
    }

    public function index(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user instanceof \App\Models\User) {
            $outlets = Outlet::with(['province', 'kabupaten', 'kecamatan', 'kelurahan'])
                ->where('user_id', $user->id)
                ->latest()
                ->get();
        } elseif ($user instanceof \App\Models\Employee) {
            $outlets = Outlet::with(['province', 'kabupaten', 'kecamatan', 'kelurahan'])
                ->where('id', $user->outlet_id)
                ->latest()
                ->get();
        } else {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar outlet berhasil diambil',
            'data'    => $outlets
        ]);
    }

    public function show($id)
    {
        if (!$this->checkAccess((int) $id)) {
            return response()->json(['message' => 'Akses ditolak atau Outlet tidak ditemukan'], 403);
        }

        $outlet = Outlet::with(['user', 'province', 'kabupaten', 'kecamatan', 'kelurahan'])
                    ->where('id', $id)
                    ->first();

        if (!$outlet) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Outlet tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail outlet berhasil ditemukan',
            'data'    => $outlet
        ]);
    }

    public function store(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Token tidak valid atau tidak terbaca.'
            ], 401);
        }

        if ($user instanceof \App\Models\Employee) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses untuk membuat outlet.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'outlet_code'  => 'required|string|unique:outlets,outlet_code',
            'name'         => 'required|string|max:255',
            'phone'        => 'required|string',
            'province_id'  => 'required|exists:provinces,id',
            'kabupaten_id' => 'required|exists:kabupatens,id',
            'kecamatan_id' => 'required|exists:kecamatans,id',
            'kelurahan_id' => 'required|exists:kelurahans,id',
            'address'      => 'required',
            'text_image'   => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'blok'         => 'nullable|string',
            'rt'           => 'nullable|string',
            'rw'           => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data              = $request->all();
        $data['user_id']   = $user->id;

        if ($request->hasFile('text_image')) {
            $file     = $request->file('text_image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/outlets', $filename);
            $data['text_image'] = $filename;
        }

        $outlet = Outlet::create($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Outlet created successfully',
            'data'    => $outlet
        ], 201);
    }

    public function update(Request $request, $id)
    {
        if (!$this->checkAccess((int) $id)) {
            return response()->json(['message' => 'Akses ditolak atau Outlet tidak ditemukan'], 403);
        }

        $outlet = Outlet::find($id);

        if (!$outlet) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Outlet tidak ditemukan.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'         => 'sometimes|required|string|max:255',
            'phone'        => 'sometimes|required|string',
            'province_id'  => 'sometimes|required|exists:provinces,id',
            'kabupaten_id' => 'sometimes|required|exists:kabupatens,id',
            'kecamatan_id' => 'sometimes|required|exists:kecamatans,id',
            'kelurahan_id' => 'sometimes|required|exists:kelurahans,id',
            'address'      => 'sometimes|required',
            'text_image'   => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'blok'         => 'nullable|string',
            'rt'           => 'nullable|string',
            'rw'           => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->all();

        if ($request->hasFile('text_image')) {
            if ($outlet->text_image) {
                \Storage::delete('public/outlets/' . $outlet->text_image);
            }

            $file     = $request->file('text_image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/outlets', $filename);
            $data['text_image'] = $filename;
        }

        $outlet->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Outlet updated successfully',
            'data'    => $outlet
        ]);
    }

    public function destroy($id)
    {
        if (!$this->checkAccess((int) $id)) {
            return response()->json(['message' => 'Akses ditolak atau Outlet tidak ditemukan'], 403);
        }

        $outlet = Outlet::find($id);

        if (!$outlet) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Outlet tidak ditemukan.'
            ], 404);
        }

        $user = auth('sanctum')->user();
        if ($user instanceof \App\Models\Employee) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki otoritas untuk menghapus outlet ini.'
            ], 403);
        }

        $outlet->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Outlet berhasil dihapus.'
        ]);
    }

    public function storeConfiguration(Request $request, $id)
    {
        $outlet = Outlet::find($id);

        if (!$outlet) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Outlet tidak ditemukan.'
            ], 404);
        }

        if (!$this->checkAccess((int) $id)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses untuk mengatur outlet ini.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'allow_multiple_services'   => 'required|boolean',
            'allow_duplicate_service'   => 'required|boolean',
            'input_total_pcs_mandatory' => 'required|boolean',
            'process_berurutan'         => 'required|boolean',
            'payment_first'             => 'required|boolean',
            'employee_update_data'      => 'required|boolean',
            'nota_number'               => 'nullable|string',
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

        $configuration = $outlet->configuration()->updateOrCreate(
            ['outlet_id' => $id],
            $request->all()
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Konfigurasi outlet berhasil disimpan.',
            'data'    => $configuration
        ]);
    }

    public function getConfiguration($id)
    {
        $outlet = Outlet::find($id);

        if (!$outlet) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Outlet tidak ditemukan.'
            ], 404);
        }

        if (!$this->checkAccess((int) $id)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses untuk melihat konfigurasi outlet ini.'
            ], 403);
        }

        $configuration = $outlet->configuration;

        if (!$configuration) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Konfigurasi untuk outlet ini belum diatur.',
                'data'    => null
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Konfigurasi outlet berhasil diambil.',
            'data'    => $configuration
        ]);
    }
}