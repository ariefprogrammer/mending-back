<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Unit;
use App\Models\UnitProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller
{
    private function getVerifiedOutlet($outletId)
    {
        return Outlet::where('id', $outletId)
                     ->where('user_id', auth('sanctum')->id())
                     ->first();
    }

    // LIST UNITS
    public function index($outletId)
    {
        if (!$this->getVerifiedOutlet($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $units = Unit::where('outlet_id', $outletId)
                    ->with(['unitProcesses'])
                    ->latest()
                    ->get();

        return response()->json(['status' => 'success', 'data' => $units]);
    }

    // SHOW UNIT
    public function show($outletId, $id)
    {
        if (!$this->getVerifiedOutlet($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $unit = Unit::where('outlet_id', $outletId)
                    ->with(['unitProcesses'])
                    ->find($id);

        if (!$unit) return response()->json(['message' => 'Unit not found'], 404);

        return response()->json(['status' => 'success', 'data' => $unit]);
    }

    // STORE UNIT
    public function store(Request $request, $outletId)
    {
        if (!$this->getVerifiedOutlet($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'                      => 'required|string|max:255',
            'processes'                 => 'nullable|array',
            'processes.*.name'          => 'required_with:processes|string|max:255',
            'processes.*.is_active'     => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $unit = Unit::create([
                'outlet_id' => $outletId,
                'name'      => $request->name,
            ]);

            if ($request->filled('processes')) {
                $processes = collect($request->processes)->map(fn($p) => [
                    'unit_id'   => $unit->id,
                    'name'      => $p['name'],
                    'is_active' => $p['is_active'] ?? true,
                ]);
                UnitProcess::insert($processes->toArray());
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data'   => $unit->load('unitProcesses')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create unit', 'error' => $e->getMessage()], 500);
        }
    }

    // UPDATE UNIT
    public function update(Request $request, $outletId, $id)
    {
        if (!$this->getVerifiedOutlet($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $unit = Unit::where('outlet_id', $outletId)->find($id);
        if (!$unit) return response()->json(['message' => 'Unit not found'], 404);

        $validator = Validator::make($request->all(), [
            'name'                      => 'sometimes|required|string|max:255',
            'processes'                 => 'nullable|array',
            'processes.*.id'            => 'nullable|integer|exists:unit_process,id',
            'processes.*.name'          => 'required_with:processes|string|max:255',
            'processes.*.is_active'     => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $unit->update($request->only(['name']));

            if ($request->has('processes')) {
                $incoming     = collect($request->processes);
                $incomingIds  = $incoming->pluck('id')->filter()->toArray();

                // Hapus process yang tidak ada di request
                UnitProcess::where('unit_id', $unit->id)
                            ->whereNotIn('id', $incomingIds)
                            ->delete();

                foreach ($incoming as $p) {
                    if (!empty($p['id'])) {
                        // Update process yang sudah ada
                        UnitProcess::where('id', $p['id'])
                                   ->where('unit_id', $unit->id)
                                   ->update([
                                       'name'      => $p['name'],
                                       'is_active' => $p['is_active'] ?? true,
                                   ]);
                    } else {
                        // Tambah process baru
                        UnitProcess::create([
                            'unit_id'   => $unit->id,
                            'name'      => $p['name'],
                            'is_active' => $p['is_active'] ?? true,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data'   => $unit->load('unitProcesses')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update unit', 'error' => $e->getMessage()], 500);
        }
    }

    // DELETE UNIT
    public function destroy($outletId, $id)
    {
        if (!$this->getVerifiedOutlet($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $unit = Unit::where('outlet_id', $outletId)->find($id);
        if (!$unit) return response()->json(['message' => 'Unit not found'], 404);

        $unit->delete(); // unit_process terhapus otomatis via cascadeOnDelete
        return response()->json(['status' => 'success', 'message' => 'Unit deleted']);
    }
}