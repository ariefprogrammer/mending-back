<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmployeeAttendanceController extends Controller
{
    /**
     * Cek akses outlet
     */
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

    /**
     * Mendapatkan semua data presensi
     */
    public function index(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        $query = EmployeeAttendance::where('outlet_id', $outletId)
            ->with('employee:id,employee_code,name');

        // Filter berdasarkan tanggal
        if ($request->filled('date')) {
            $query->whereDate('work_date', $request->date);
        }

        // Filter berdasarkan bulan
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('work_date', $request->month)
                  ->whereYear('work_date', $request->year);
        }

        // Filter berdasarkan employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendances = $query->orderBy('work_date', 'desc')
            ->orderBy('check_in', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'status' => 'success',
            'data'   => $attendances,
        ]);
    }

    /**
     * Mendapatkan detail presensi
     */
    public function show($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        $attendance = EmployeeAttendance::where('outlet_id', $outletId)
            ->where('id', $id)
            ->with('employee:id,employee_code,name')
            ->first();

        if (!$attendance) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data presensi tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $attendance,
        ]);
    }

    /**
     * Check-in karyawan
     */
    public function checkIn(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'work_date'   => 'required|date',
            'check_in'    => 'nullable|date_format:H:i:s',
        ], [
            'employee_id.required' => 'ID karyawan wajib diisi',
            'employee_id.exists'   => 'Karyawan tidak ditemukan',
            'work_date.required'   => 'Tanggal kerja wajib diisi',
            'work_date.date'       => 'Format tanggal tidak valid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()->toArray(),
            ], 422);
        }

        // ✅ Cek apakah employee milik outlet ini
        $employee = Employee::where('id', $request->employee_id)
            ->where('outlet_id', $outletId)
            ->first();

        if (!$employee) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan tidak ditemukan di outlet ini',
            ], 404);
        }

        // ✅ Cek apakah sudah check-in hari ini
        $existingAttendance = EmployeeAttendance::where('employee_id', $request->employee_id)
            ->where('work_date', $request->work_date)
            ->first();

        if ($existingAttendance && $existingAttendance->check_in) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan sudah melakukan check-in hari ini',
                'data'    => $existingAttendance,
            ], 422);
        }

        try {
            $checkInTime = $request->check_in ?? now()->format('H:i:s');

            if ($existingAttendance) {
                // Update record yang sudah ada
                $existingAttendance->update([
                    'check_in' => $checkInTime,
                    'status'   => 'in',
                ]);
                $attendance = $existingAttendance;
            } else {
                // Buat record baru
                $attendance = EmployeeAttendance::create([
                    'employee_id' => $request->employee_id,
                    'outlet_id'   => $outletId,
                    'work_date'   => $request->work_date,
                    'check_in'    => $checkInTime,
                    'status'      => 'in',
                ]);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Check-in berhasil',
                'data'    => $attendance->load('employee:id,employee_code,name'),
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Check-in Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal melakukan check-in',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mencatat waktu mulai overtime/lembur
     */
    public function overtime(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        // ✅ Cari data presensi
        $attendance = EmployeeAttendance::where('outlet_id', $outletId)
            ->where('id', $id)
            ->first();

        if (!$attendance) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data presensi tidak ditemukan',
            ], 404);
        }

        // ✅ Cek apakah sudah check-in
        if (!$attendance->check_in) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan belum melakukan check-in',
            ], 422);
        }

        // ✅ Cek apakah sudah check-out
        if ($attendance->check_out) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan sudah check-out, tidak bisa mulai overtime',
            ], 422);
        }

        // ✅ Cek apakah sudah mulai overtime sebelumnya
        if ($attendance->overtime) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Overtime sudah dimulai pada ' . $attendance->overtime->format('H:i'),
                'data'    => $attendance,
            ], 422);
        }

        try {
            $overtimeTime = $request->overtime ?? now()->format('H:i:s');

            $attendance->update([
                'overtime' => $overtimeTime,
                'status'   => 'overtime', // ✅ Status berubah jadi overtime
            ]);

            \Log::info('Overtime dimulai', [
                'attendance_id' => $attendance->id,
                'employee_id'   => $attendance->employee_id,
                'overtime'      => $overtimeTime,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Overtime berhasil dimulai pada ' . $overtimeTime,
                'data'    => $attendance->load('employee:id,employee_code,name'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Overtime Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mencatat overtime',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check-out karyawan
     */
    public function checkOut(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        $attendance = EmployeeAttendance::where('outlet_id', $outletId)
            ->where('id', $id)
            ->first();

        if (!$attendance) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data presensi tidak ditemukan',
            ], 404);
        }

        // ✅ Cek apakah sudah check-in
        if (!$attendance->check_in) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan belum melakukan check-in',
            ], 422);
        }

        // ✅ Cek apakah sudah check-out
        if ($attendance->check_out) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan sudah melakukan check-out',
                'data'    => $attendance,
            ], 422);
        }

        try {
            $checkOutTime = $request->check_out ?? now()->format('H:i:s');

            $updateData = [
                'check_out' => $checkOutTime,
                'status'    => 'out', // ✅ Status selalu jadi 'out' saat check-out
            ];

            $message = 'Check-out berhasil';

            // ✅ Jika sebelumnya overtime, beri info durasi (nanti dihitung)
            if ($attendance->status === 'overtime' && $attendance->overtime) {
                // Hitung durasi overtime (untuk info saja, tidak disimpan)
                $overtimeStart = strtotime($attendance->overtime);
                $overtimeEnd   = strtotime($checkOutTime);
                $durationMinutes = round(($overtimeEnd - $overtimeStart) / 60, 0);
                $hours   = floor($durationMinutes / 60);
                $minutes = $durationMinutes % 60;
                
                $message = "Check-out berhasil. Durasi lembur: {$hours} jam {$minutes} menit";
            }

            $attendance->update($updateData);

            \Log::info('Check-out berhasil', [
                'attendance_id' => $attendance->id,
                'employee_id'   => $attendance->employee_id,
                'check_out'     => $checkOutTime,
                'was_overtime'  => $attendance->overtime ? true : false,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => $message,
                'data'    => $attendance->load('employee:id,employee_code,name'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Check-out Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal melakukan check-out',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Membuat/tambah data presensi manual
     */
    public function store(Request $request, $outletId)
    {
       if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'work_date'   => 'required|date',
            'check_in'    => 'nullable|date_format:H:i:s',
            'overtime' => 'nullable|date_format:H:i:s',
            'check_out'   => 'nullable|date_format:H:i:s',
            'status'      => 'nullable|string|max:50',
        ], [
            'employee_id.required' => 'ID karyawan wajib diisi',
            'employee_id.exists'   => 'Karyawan tidak ditemukan',
            'work_date.required'   => 'Tanggal kerja wajib diisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()->toArray(),
            ], 422);
        }

        // ✅ Cek apakah employee milik outlet ini
        $employee = Employee::where('id', $request->employee_id)
            ->where('outlet_id', $outletId)
            ->first();

        if (!$employee) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan tidak ditemukan di outlet ini',
            ], 404);
        }

        // ✅ Cek duplikasi
        $existingAttendance = EmployeeAttendance::where('employee_id', $request->employee_id)
            ->where('work_date', $request->work_date)
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data presensi untuk karyawan ini pada tanggal tersebut sudah ada',
                'data'    => $existingAttendance,
            ], 422);
        }

        try {
            $attendance = EmployeeAttendance::create([
                'employee_id' => $request->employee_id,
                'outlet_id'   => $outletId,
                'work_date'   => $request->work_date,
                'check_in'    => $request->check_in,
                'check_out'   => $request->check_out,
                'status'      => $request->status ?? ($request->check_out ? 'out' : ($request->check_in ? 'in' : null)),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data presensi berhasil ditambahkan',
                'data'    => $attendance->load('employee:id,employee_code,name'),
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Store Attendance Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menambah data presensi',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update data presensi
     */
    public function update(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        $attendance = EmployeeAttendance::where('outlet_id', $outletId)
            ->where('id', $id)
            ->first();

        if (!$attendance) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data presensi tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'work_date' => 'nullable|date',
            'check_in'  => 'nullable|date_format:H:i:s',
            'overtime' => 'nullable|date_format:H:i:s',
            'check_out' => 'nullable|date_format:H:i:s',
            'status'    => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()->toArray(),
            ], 422);
        }

        try {
            $attendance->update($request->only([
                'work_date',
                'check_in',
                'check_out',
                'status',
            ]));

            return response()->json([
                'status'  => 'success',
                'message' => 'Data presensi berhasil diperbarui',
                'data'    => $attendance->load('employee:id,employee_code,name'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Update Attendance Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui data presensi',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mendapatkan data presensi berdasarkan employee_id (presensi diri sendiri)
     */
    public function myAttendance(Request $request, $outletId, $employeeId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        // ✅ Cek apakah employee ada di outlet ini
        $employee = Employee::where('id', $employeeId)
            ->where('outlet_id', $outletId)
            ->first();

        if (!$employee) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan tidak ditemukan di outlet ini',
            ], 404);
        }

        // ✅ Query presensi berdasarkan employee_id
        $query = EmployeeAttendance::where('employee_id', $employeeId)
            ->where('outlet_id', $outletId)
            ->with('employee:id,employee_code,name');

        // Filter berdasarkan tanggal
        if ($request->filled('date')) {
            $query->whereDate('work_date', $request->date);
        }

        // Filter berdasarkan bulan
        elseif ($request->filled('month')) {
            $month = $request->month;
            $year  = $request->year ?? now()->year;
            $query->whereMonth('work_date', $month)
                ->whereYear('work_date', $year);
        }

        // Filter berdasarkan tahun saja
        elseif ($request->filled('year')) {
            $query->whereYear('work_date', $request->year);
        }

        // Filter berdasarkan range tanggal
        elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('work_date', [$request->start_date, $request->end_date]);
        }

        // Default — tampilkan bulan ini saja
        else {
            $query->whereMonth('work_date', now()->month)
                ->whereYear('work_date', now()->year);
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ✅ Order by terbaru
        $direction = in_array($request->sort, ['asc', 'desc']) ? $request->sort : 'desc';
        $attendances = $query->orderBy('work_date', $direction)
            ->orderBy('check_in', $direction)
            ->paginate($request->per_page ?? 31);

        // ✅ Hitung statistik
        $stats = [
            'total_present' => EmployeeAttendance::where('employee_id', $employeeId)
                ->where('outlet_id', $outletId)
                ->whereNotNull('check_in')
                ->count(),
            'total_days' => EmployeeAttendance::where('employee_id', $employeeId)
                ->where('outlet_id', $outletId)
                ->count(),
            'this_month_present' => EmployeeAttendance::where('employee_id', $employeeId)
                ->where('outlet_id', $outletId)
                ->whereMonth('work_date', now()->month)
                ->whereYear('work_date', now()->year)
                ->whereNotNull('check_in')
                ->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data'   => [
                'employee'    => [
                    'id'             => $employee->id,
                    'employee_code'  => $employee->employee_code,
                    'name'           => $employee->name,
                ],
                'attendances' => $attendances,
                'statistics'  => $stats,
            ],
        ]);
    }

    /**
     * Mendapatkan presensi hari ini untuk employee tertentu
     */
    public function todayAttendance(Request $request, $outletId, $employeeId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        $employee = Employee::where('id', $employeeId)
            ->where('outlet_id', $outletId)
            ->first();

        if (!$employee) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan tidak ditemukan di outlet ini',
            ], 404);
        }

        $today = $request->date ?? now()->format('Y-m-d');

        $attendance = EmployeeAttendance::where('employee_id', $employeeId)
            ->where('outlet_id', $outletId)
            ->whereDate('work_date', $today)
            ->first();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'employee'   => [
                    'id'             => $employee->id,
                    'employee_code'  => $employee->employee_code,
                    'name'           => $employee->name,
                ],
                'date'       => $today,
                'attendance' => $attendance,
                'has_checked_in'  => $attendance && $attendance->check_in ? true : false,
                'has_checked_out' => $attendance && $attendance->check_out ? true : false,
            ],
        ]);
    }

    /**
     * Hapus data presensi
     */
    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        $attendance = EmployeeAttendance::where('outlet_id', $outletId)
            ->where('id', $id)
            ->first();

        if (!$attendance) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data presensi tidak ditemukan',
            ], 404);
        }

        try {
            $attendance->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data presensi berhasil dihapus',
            ]);

        } catch (\Exception $e) {
            \Log::error('Delete Attendance Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus data presensi',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}