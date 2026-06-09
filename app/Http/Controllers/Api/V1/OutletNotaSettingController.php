<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\OutletNotaSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OutletNotaSettingController extends Controller
{
    private function checkAccess(int $outletId): bool
    {
        $user = auth('sanctum')->user();

        if (!$user) return false;

        if ($user instanceof \App\Models\User) {
            return Outlet::where('id', $outletId)
                         ->where('user_id', $user->id)
                         ->exists();
        }

        if ($user instanceof \App\Models\Employee) {
            return (int) $user->outlet_id === (int) $outletId;
        }

        return false;
    }

    // ─── SHOW ────────────────────────────────────────────────────
    // GET /outlets/{outletId}/nota-settings
    // Ambil setting nota outlet, jika belum ada return default

    public function show($outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $setting = OutletNotaSetting::where('outlet_id', $outletId)->first();

        // Jika belum ada, return nilai default tanpa insert ke DB
        if (!$setting) {
            return response()->json([
                'status' => 'success',
                'data'   => [
                    'outlet_id'             => (int) $outletId,
                    'logo_path'             => null,
                    'logo_url'              => null,
                    'header_alignment'      => 'tengah',
                    'header_note'           => null,
                    'show_logo'             => false,
                    'show_nama_outlet'      => false,
                    'show_alamat_outlet'    => false,
                    'show_nama_kasir'       => false,
                    'show_nama_pelanggan'   => false,
                    'show_kategori_layanan' => false,
                    'show_jumlah_potong'    => false,
                    'show_estimasi_selesai' => false,
                    'show_parfum'           => false,
                    'show_qr_code'          => false,
                    'show_powered_by'       => false,
                    'show_header_fisik'     => false,
                    'show_footer_fisik'     => false,
                    'auto_potong_nota'      => false,
                ],
            ]);
        }

        return response()->json(['status' => 'success', 'data' => $setting]);
    }

    // ─── UPSERT ──────────────────────────────────────────────────
    // POST /outlets/{outletId}/nota-settings
    // Simpan atau update setting nota (multipart/form-data)

    public function upsert(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'logo'                  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'header_alignment'      => 'nullable|in:kiri,tengah,kanan',
            'header_note'           => 'nullable|string',
            'show_logo'             => 'nullable|in:0,1',
            'show_nama_outlet'      => 'nullable|in:0,1',
            'show_alamat_outlet'    => 'nullable|in:0,1',
            'show_nama_kasir'       => 'nullable|in:0,1',
            'show_nama_pelanggan'   => 'nullable|in:0,1',
            'show_kategori_layanan' => 'nullable|in:0,1',
            'show_jumlah_potong'    => 'nullable|in:0,1',
            'show_estimasi_selesai' => 'nullable|in:0,1',
            'show_parfum'           => 'nullable|in:0,1',
            'show_qr_code'          => 'nullable|in:0,1',
            'show_powered_by'       => 'nullable|in:0,1',
            'show_header_fisik'     => 'nullable|in:0,1',
            'show_footer_fisik'     => 'nullable|in:0,1',
            'auto_potong_nota'      => 'nullable|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $setting = OutletNotaSetting::firstOrNew(['outlet_id' => $outletId]);

        // Handle upload logo
        if ($request->hasFile('logo')) {
            // Hapus logo lama jika ada
            if ($setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }
            $setting->logo_path = $request->file('logo')->store("logos/outlet_{$outletId}", 'public');
        }

        // Update field lainnya jika dikirim
        $fields = [
            'header_alignment', 'header_note',
            'show_logo', 'show_nama_outlet', 'show_alamat_outlet',
            'show_nama_kasir', 'show_nama_pelanggan', 'show_kategori_layanan',
            'show_jumlah_potong', 'show_estimasi_selesai', 'show_parfum',
            'show_qr_code', 'show_powered_by', 'show_header_fisik',
            'show_footer_fisik', 'auto_potong_nota',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $setting->$field = $request->$field;
            }
        }

        $setting->outlet_id = $outletId;
        $setting->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Pengaturan nota berhasil disimpan',
            'data'    => $setting->fresh(),
        ]);
    }
}