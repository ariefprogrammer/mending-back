<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EmployeeWarningLetter;
use App\Models\Outlet;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmployeeWarningLetterController extends Controller
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

    // ─── GET /outlets/{outletId}/employee-warning-letters ───────────────────────
    public function index(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $query = EmployeeWarningLetter::with([
                'employee:id,name,employee_code',
                'createdByUser:id,name',
                'createdByEmployee:id,name',
            ])
            ->where('outlet_id', $outletId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($e) use ($search) {
                      $e->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                  });
            });
        }

        $letters = $query->orderBy('created_at', 'desc')
                          ->paginate($request->per_page ?? 15);

        return response()->json([
            'status' => 'success',
            'data'   => $letters,
        ]);
    }

    // ─── GET /outlets/{outletId}/employee-warning-letters/{id} ──────────────────
    public function show($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $letter = EmployeeWarningLetter::with([
                'employee:id,name,employee_code',
                'createdByUser:id,name',
                'createdByEmployee:id,name',
            ])
            ->where('outlet_id', $outletId)
            ->where('id', $id)
            ->first();

        if (!$letter) {
            return response()->json(['status' => 'error', 'message' => 'Formulir tidak ditemukan'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $letter]);
    }

    // ─── POST /outlets/{outletId}/employee-warning-letters ──────────────────────
    // Body:
    //   employee_id (required), date_effective (required, format Y-m-d),
    //   description (required), status (optional)
    public function store(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'employee_id'    => 'required|exists:employees,id',
            'date_effective' => 'required|date_format:Y-m-d',
            'description'    => 'required|string',
            'status'         => 'nullable|in:' . implode(',', EmployeeWarningLetter::STATUSES),
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        // Pastikan karyawan milik outlet ini
        $employee = Employee::where('id', $request->employee_id)
                            ->where('outlet_id', $outletId)
                            ->first();

        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Karyawan tidak ditemukan di outlet ini'], 404);
        }

        DB::beginTransaction();
        try {
            $user       = auth('sanctum')->user();
            $userId     = ($user instanceof \App\Models\User)     ? $user->id : null;
            $employeeId = ($user instanceof \App\Models\Employee) ? $user->id : null;

            $letter = EmployeeWarningLetter::create([
                'outlet_id'              => $outletId,
                'employee_id'            => $request->employee_id,
                'date_effective'         => $request->date_effective,
                'description'            => $request->description,
                'status'                 => $request->status ?? 'draft',
                'created_by_user_id'     => $userId,
                'created_by_employee_id' => $employeeId,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Surat teguran berhasil dibuat',
                'data'    => $letter,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menyimpan surat teguran',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── PUT /outlets/{outletId}/employee-warning-letters/{id} ──────────────────
    // Body: date_effective, description, status — semua optional
    public function update(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $letter = EmployeeWarningLetter::where('outlet_id', $outletId)
                                       ->where('id', $id)
                                       ->first();

        if (!$letter) {
            return response()->json(['status' => 'error', 'message' => 'Formulir tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'date_effective' => 'nullable|date_format:Y-m-d',
            'description'    => 'nullable|string',
            'status'         => 'nullable|in:' . implode(',', EmployeeWarningLetter::STATUSES),
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $letter->update($request->only(['date_effective', 'description', 'status']));

            return response()->json([
                'status'  => 'success',
                'message' => 'Surat teguran berhasil diperbarui',
                'data'    => $letter,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui surat teguran',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── DELETE /outlets/{outletId}/employee-warning-letters/{id} ───────────────
    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $letter = EmployeeWarningLetter::where('outlet_id', $outletId)
                                       ->where('id', $id)
                                       ->first();

        if (!$letter) {
            return response()->json(['status' => 'error', 'message' => 'Formulir tidak ditemukan'], 404);
        }

        $letter->delete();

        return response()->json(['status' => 'success', 'message' => 'Surat teguran berhasil dihapus']);
    }
}