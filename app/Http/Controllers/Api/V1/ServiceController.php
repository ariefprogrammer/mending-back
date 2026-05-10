<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Service;
use App\Models\ServiceFlow;
use App\Models\Satuan;
use App\Models\OutletServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
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
    
    // LIST LAYANAN
    public function index(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $services = Service::with('category')
                    ->where('outlet_id', $outletId)
                    ->when($request->search, function($query, $search) {
                        $query->where('name', 'like', "%{$search}%")
                              ->orWhere('service_code', 'like', "%{$search}%");
                    })
                    ->with('flows')
                    ->latest()
                    ->paginate(15);

        return response()->json(['status' => 'success', 'data' => $services]);
    }

    // SIMPAN LAYANAN BARU
    public function store(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'service_code' => [
                'required', 'string',
                Rule::unique('services')->where('outlet_id', $outletId)
            ],
            'name' => 'required|string|max:255',
            'outlet_service_category_id' => [
                'required',
                Rule::exists('outlet_service_categories', 'id')->where('outlet_id', $outletId)
            ],
            'satuan_id' => ['required', 'integer', Rule::exists('satuans', 'id')],
            'price' => 'required|numeric|min:0',
            'duration_unit' => 'required|string',
            'duration' => 'required|integer|min:0',
            'minimum_qty' => 'required|integer|min:1',

            // Validasi service flows (opsional)
            'flows' => 'nullable|array',
            'flows.*.name' => 'required_with:flows|string|max:255',
            'flows.*.commission' => 'required_with:flows|numeric|min:0',
            'flows.*.service_unit_id' => ['required_with:flows', 'integer', Rule::exists('service_units', 'id')],
            'flows.*.is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Simpan service
        $service = Service::create(array_merge(
            $request->except('flows'),
            ['outlet_id' => $outletId]
        ));

        // Simpan service flows
        $flows = $request->input('flows', []);
        $flowsData = [];

        // Nama flow default yang selalu ada (sesuai gambar)
        $defaultFlows = ['Cuci', 'Kering', 'Setrika', 'Lipat', 'Kemas'];

        foreach ($defaultFlows as $sequence => $flowName) {
            // Cari apakah flow ini dikirim dari request
            $inputFlow = collect($flows)->firstWhere('name', $flowName);

            if ($inputFlow && !empty($inputFlow['commission'])) {
                // Flow diisi oleh user
                $flowsData[] = [
                    'service_id'      => $service->id,
                    'name'            => $flowName,
                    'sequence'        => $sequence + 1,
                    'is_active'       => $inputFlow['is_active'] ?? true,
                    'service_unit_id' => $inputFlow['service_unit_id'],
                    'commission'      => $inputFlow['commission'],
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            } else {
                // Flow tidak diisi, tetap simpan dengan nilai default
                $flowsData[] = [
                    'service_id'      => $service->id,
                    'name'            => $flowName,
                    'sequence'        => 0,
                    'is_active'       => false,
                    'service_unit_id' => $request->input('flows.0.service_unit_id', 1), // fallback ke id pertama
                    'commission'      => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }
        }

        ServiceFlow::insert($flowsData);

        $service->load('flows');

        return response()->json([
            'status' => 'success',
            'message' => 'Layanan berhasil dibuat',
            'data' => $service
        ], 201);
    }

    // UPDATE LAYANAN
    public function update(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $service = Service::where('outlet_id', $outletId)->find($id);
        if (!$service) return response()->json(['message' => 'Service Not Found'], 404);

        $validator = Validator::make($request->all(), [
            'service_code' => [
                'sometimes', 'required', 'string',
                Rule::unique('services')->where('outlet_id', $outletId)->ignore($id)
            ],
            'name' => 'sometimes|required|string|max:255',
            'outlet_service_category_id' => [
                'sometimes', 'required',
                Rule::exists('outlet_service_categories', 'id')->where('outlet_id', $outletId)
            ],
            'satuan_id' => ['sometimes', 'required', 'integer', Rule::exists('satuans', 'id')],
            'price' => 'sometimes|required|numeric|min:0',
            'duration_unit' => 'sometimes|required|string',
            'duration' => 'sometimes|required|integer|min:0',
            'minimum_qty' => 'sometimes|required|integer|min:1',

            // Validasi service flows (opsional)
            'flows' => 'nullable|array',
            'flows.*.name' => 'required_with:flows|string|max:255',
            'flows.*.commission' => 'required_with:flows|numeric|min:0',
            'flows.*.service_unit_id' => ['required_with:flows', 'integer', Rule::exists('service_units', 'id')],
            'flows.*.is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 422);

        // Update data service
        $service->update($request->except('flows'));

        // Update service flows jika dikirim
        if ($request->has('flows')) {
            $flows = $request->input('flows', []);
            $defaultFlows = ['Cuci', 'Kering', 'Setrika', 'Lipat', 'Kemas'];

            foreach ($defaultFlows as $sequence => $flowName) {
                $inputFlow = collect($flows)->firstWhere('name', $flowName);
                $existingFlow = ServiceFlow::where('service_id', $service->id)
                                        ->where('name', $flowName)
                                        ->first();

                if ($inputFlow && !empty($inputFlow['commission'])) {
                    // Flow diisi oleh user
                    $flowData = [
                        'sequence'        => $sequence + 1,
                        'is_active'       => $inputFlow['is_active'] ?? true,
                        'service_unit_id' => $inputFlow['service_unit_id'],
                        'commission'      => $inputFlow['commission'],
                    ];
                } else {
                    // Flow tidak diisi, set ke nilai default
                    $flowData = [
                        'sequence'        => 0,
                        'is_active'       => false,
                        'service_unit_id' => $existingFlow?->service_unit_id ?? 1,
                        'commission'      => 0,
                    ];
                }

                if ($existingFlow) {
                    // Update flow yang sudah ada
                    $existingFlow->update($flowData);
                } else {
                    // Buat flow baru jika belum ada
                    ServiceFlow::create(array_merge($flowData, [
                        'service_id' => $service->id,
                        'name'       => $flowName,
                    ]));
                }
            }
        }

        $service->load('flows');

        return response()->json([
            'status' => 'success',
            'message' => 'Layanan berhasil diperbarui',
            'data' => $service
        ]);
    }

    // HAPUS LAYANAN
    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $service = Service::where('outlet_id', $outletId)->find($id);
        if (!$service) return response()->json(['message' => 'Service Not Found'], 404);

        $service->delete();

        return response()->json(['status' => 'success', 'message' => 'Service deleted']);
    }
}