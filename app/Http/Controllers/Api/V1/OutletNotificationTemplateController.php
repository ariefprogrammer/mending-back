<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\OutletNotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OutletNotificationTemplateController extends Controller
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

    // ─── INDEX ───────────────────────────────────────────────────
    // GET /outlets/{outletId}/notification-templates
    // Ambil semua template milik outlet, jika belum ada return default kosong

    public function index($outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        // Ambil template yang sudah ada
        $existing = OutletNotificationTemplate::where('outlet_id', $outletId)
            ->get()
            ->keyBy('type');

        // Pastikan semua type selalu muncul di response
        $templates = collect(OutletNotificationTemplate::TYPES)->map(function ($type) use ($existing, $outletId) {
            return $existing->get($type) ?? [
                'outlet_id' => $outletId,
                'type'      => $type,
                'is_active' => false,
                'message'   => null,
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data'   => $templates,
        ]);
    }

    // ─── UPSERT ──────────────────────────────────────────────────
    // POST /outlets/{outletId}/notification-templates
    // Simpan semua template sekaligus (insert or update)

    public function upsert(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'templates'             => 'required|array',
            'templates.*.type'      => 'required|string|in:' . implode(',', OutletNotificationTemplate::TYPES),
            'templates.*.is_active' => 'required|boolean',
            'templates.*.message'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        foreach ($request->templates as $item) {
            OutletNotificationTemplate::updateOrCreate(
                [
                    'outlet_id' => $outletId,
                    'type'      => $item['type'],
                ],
                [
                    'is_active' => $item['is_active'],
                    'message'   => $item['message'] ?? null,
                ]
            );
        }

        $templates = OutletNotificationTemplate::where('outlet_id', $outletId)->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Template notifikasi berhasil disimpan',
            'data'    => $templates,
        ]);
    }
}