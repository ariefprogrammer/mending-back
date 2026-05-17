<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ImageLeaveRequest;
use App\Models\LeaveRequest;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LeaveRequestController extends Controller
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

    private function uploadImage($file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('leave_requests', $filename, 'public');
    }

    // LIST
    public function index(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        $query = LeaveRequest::with([
                        'employee:id,name,employee_code',
                        'ownerReviewer:id,name',
                        'reviewer:id,name',
                        'images',
                    ])
                    ->where('outlet_id', $outletId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('leave_type')) {
            $query->where('leave_type', $request->leave_type);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_date', [
                $request->start_date,
                $request->end_date,
            ]);
        }

        $direction     = in_array($request->sort, ['asc', 'desc']) ? $request->sort : 'desc';
        $leaveRequests = $query->orderBy('start_date', $direction)->get();

        return response()->json([
            'status' => 'success',
            'data'   => $leaveRequests,
        ]);
    }

    // DETAIL
    public function show($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $leaveRequest = LeaveRequest::with([
                            'employee:id,name,employee_code',
                            'ownerReviewer:id,name',
                            'reviewer:id,name',
                            'images',
                        ])
                        ->where('outlet_id', $outletId)
                        ->find($id);

        if (!$leaveRequest) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $leaveRequest,
        ]);
    }

    // MY LEAVE REQUEST (per employee)
    public function myLeaveRequest(Request $request, $outletId, $employeeId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        // Cek apakah employee ada di outlet ini
        $employee = \App\Models\Employee::where('id', $employeeId)
            ->where('outlet_id', $outletId)
            ->first();

        if (!$employee) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan tidak ditemukan di outlet ini',
            ], 404);
        }

        // Query leave request berdasarkan employee_id
        $query = LeaveRequest::where('employee_id', $employeeId)
            ->where('outlet_id', $outletId)
            ->with([
                'employee:id,name,employee_code',
                'ownerReviewer:id,name',
                'reviewer:id,name',
                'images',
            ]);

        // Filter berdasarkan tanggal spesifik
        if ($request->filled('date')) {
            $query->whereDate('start_date', $request->date);
        }

        // Filter berdasarkan bulan
        elseif ($request->filled('month')) {
            $month = $request->month;
            $year  = $request->year ?? now()->year;
            $query->whereMonth('start_date', $month)
                ->whereYear('start_date', $year);
        }

        // Filter berdasarkan tahun saja
        elseif ($request->filled('year')) {
            $query->whereYear('start_date', $request->year);
        }

        // Filter berdasarkan range tanggal
        elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
        }

        // Default — tampilkan bulan ini saja
        else {
            $query->whereMonth('start_date', now()->month)
                ->whereYear('start_date', now()->year);
        }

        // Filter tambahan berdasarkan status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter tambahan berdasarkan leave_type
        if ($request->filled('leave_type')) {
            $query->where('leave_type', $request->leave_type);
        }

        // Sort & paginate
        $direction    = in_array($request->sort, ['asc', 'desc']) ? $request->sort : 'desc';
        $leaveRequests = $query->orderBy('start_date', $direction)
            ->paginate($request->per_page ?? 15);

        // Hitung statistik
        $stats = [
            'total_all'      => LeaveRequest::where('employee_id', $employeeId)
                ->where('outlet_id', $outletId)
                ->count(),
            'total_pending'  => LeaveRequest::where('employee_id', $employeeId)
                ->where('outlet_id', $outletId)
                ->where('status', 'pending')
                ->count(),
            'total_approved' => LeaveRequest::where('employee_id', $employeeId)
                ->where('outlet_id', $outletId)
                ->where('status', 'approved')
                ->count(),
            'total_rejected' => LeaveRequest::where('employee_id', $employeeId)
                ->where('outlet_id', $outletId)
                ->where('status', 'rejected')
                ->count(),
            'this_month_approved' => LeaveRequest::where('employee_id', $employeeId)
                ->where('outlet_id', $outletId)
                ->whereMonth('start_date', now()->month)
                ->whereYear('start_date', now()->year)
                ->where('status', 'approved')
                ->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data'   => [
                'employee'      => [
                    'id'            => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'name'          => $employee->name,
                ],
                'leave_requests' => $leaveRequests,
                'statistics'     => $stats,
            ],
        ]);
    }

    public function allLeaveRequests(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        $query = LeaveRequest::where('outlet_id', $outletId)
            ->with([
                'employee:id,name,employee_code',
                'reviewer:id,name',
                'ownerReviewer:id,name',
                'images',
            ]);

        // Filter berdasarkan tanggal spesifik
        if ($request->filled('date')) {
            $query->whereDate('start_date', $request->date);
        }

        // Filter berdasarkan bulan
        elseif ($request->filled('month')) {
            $month = $request->month;
            $year  = $request->year ?? now()->year;
            $query->whereMonth('start_date', $month)
                ->whereYear('start_date', $year);
        }

        // Filter berdasarkan tahun saja
        elseif ($request->filled('year')) {
            $query->whereYear('start_date', $request->year);
        }

        // Filter berdasarkan range tanggal
        elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
        }

        // Default — tampilkan bulan ini saja
        else {
            $query->whereMonth('start_date', now()->month)
                ->whereYear('start_date', now()->year);
        }

        // Filter tambahan berdasarkan status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter tambahan berdasarkan leave_type
        if ($request->filled('leave_type')) {
            $query->where('leave_type', $request->leave_type);
        }

        // Filter tambahan berdasarkan employee_id
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Sort & paginate
        $direction     = in_array($request->sort, ['asc', 'desc']) ? $request->sort : 'desc';
        $leaveRequests = $query->orderBy('start_date', $direction)
            ->paginate($request->per_page ?? 20);

        // Statistik keseluruhan outlet
        $stats = [
            'total_all'      => LeaveRequest::where('outlet_id', $outletId)->count(),
            'total_pending'  => LeaveRequest::where('outlet_id', $outletId)->where('status', 'pending')->count(),
            'total_approved' => LeaveRequest::where('outlet_id', $outletId)->where('status', 'approved')->count(),
            'total_rejected' => LeaveRequest::where('outlet_id', $outletId)->where('status', 'rejected')->count(),
            'this_month_total' => LeaveRequest::where('outlet_id', $outletId)
                ->whereMonth('start_date', now()->month)
                ->whereYear('start_date', now()->year)
                ->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data'   => [
                'leave_requests' => $leaveRequests,
                'statistics'     => $stats,
            ],
        ]);
    }

    // REVIEW LEAVE REQUEST (khusus owner)
    public function reviewLeaveRequest(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        $user = auth('sanctum')->user();

        $leaveRequest = LeaveRequest::where('outlet_id', $outletId)->find($id);

        if (!$leaveRequest) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Pengajuan ini sudah diproses sebelumnya',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Tentukan kolom berdasarkan tipe user
        if ($user instanceof \App\Models\User) {
            $leaveRequest->update([
                'status'            => $request->status,
                'reviewed_by_owner' => $user->id,
                'reviewed_at'       => now(),
            ]);
        } elseif ($user instanceof \App\Models\Employee) {
            $leaveRequest->update([
                'status'      => $request->status,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);
        } else {
            return response()->json(['message' => 'Tipe pengguna tidak dikenali'], 403);
        }

        return response()->json([
            'status'  => 'success',
            'message' => $request->status === 'approved'
                ? 'Pengajuan berhasil diterima'
                : 'Pengajuan berhasil ditolak',
            'data'    => $leaveRequest->load([
                'employee:id,name,employee_code',
                'reviewer:id,name',
                'ownerReviewer:id,name',
                'images',
            ]),
        ]);
    }

    // STORE
    public function store(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'leave_type'  => 'required|string|max:100',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'reason'      => 'nullable|string',
            'images'      => 'required|array|min:1',
            'images.*'    => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();

        try {
            $leaveRequest = LeaveRequest::create([
                'employee_id' => $request->employee_id,
                'outlet_id'   => $outletId,
                'leave_type'  => $request->leave_type,
                'start_date'  => $request->start_date,
                'end_date'    => $request->end_date,
                'reason'      => $request->reason,
                'status'      => 'pending',
            ]);

            // Upload semua gambar
            foreach ($request->file('images') as $image) {
                ImageLeaveRequest::create([
                    'leave_request_id' => $leaveRequest->id,
                    'image_url'        => $this->uploadImage($image),
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Pengajuan izin berhasil dikirim',
                'data'    => $leaveRequest->load([
                    'employee:id,name,employee_code',
                    'images',
                ]),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan, data tidak tersimpan',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // UPDATE (hanya untuk update status / review)
    public function update(Request $request, $outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $leaveRequest = LeaveRequest::where('outlet_id', $outletId)->find($id);

        if (!$leaveRequest) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status'      => 'required|in:pending,approved,rejected',
            'reviewed_by' => 'required|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $leaveRequest->update([
            'status'      => $request->status,
            'reviewed_by' => $request->reviewed_by,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Status pengajuan berhasil diperbarui',
            'data'    => $leaveRequest->load([
                'employee:id,name,employee_code',
                'reviewer:id,name',
                'images',
            ]),
        ]);
    }

    // DELETE
    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $leaveRequest = LeaveRequest::where('outlet_id', $outletId)->find($id);

        if (!$leaveRequest) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // Hanya boleh dihapus jika masih pending
        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Pengajuan yang sudah diproses tidak dapat dihapus',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Hapus semua file gambar dari storage
            foreach ($leaveRequest->images as $image) {
                Storage::disk('public')->delete($image->image_url);
            }

            $leaveRequest->delete();

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Pengajuan izin berhasil dihapus',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}