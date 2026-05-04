<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Outlet;
use App\Models\SalaryComponent;
use App\Models\DetailSalaryComponent;
use App\Models\EmployeePermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    private function checkAccess(int $outletId): bool
    {
        $user = auth('sanctum')->user();
        return Outlet::where('id', $outletId)->where('user_id', $user->id)->exists();
    }

    private function findEmployee(int $outletId, string $id): ?Employee
    {
        return Employee::where('outlet_id', $outletId)->find($id);
    }

    /**
     * Upload gambar dan kembalikan path-nya.
     * Hapus file lama jika ada.
     */
    private function uploadImage($file, string $folder, ?string $oldPath = null): string
    {
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($folder, $filename, 'public');
    }

    // LIST
    public function index(Request $request, $outletId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak atau outlet tidak ditemukan'], 403);
        }

        $query = Employee::with(['role:id,name'])
                    ->where('outlet_id', $outletId);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('employee_code', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        $employees = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data'   => $employees,
        ]);
    }

    // DETAIL
    public function show($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $employee = Employee::where('outlet_id', $outletId)
            ->where('id', $id)
            ->first();

        if (!$employee) {
            return response()->json(['message' => 'Karyawan tidak ditemukan'], 404);
        }

        // ✅ DEBUG 1: Cek apakah data bisa diakses via relasi
        \Log::info('=== DEBUG SHOW EMPLOYEE ===');
        \Log::info('Employee ID: ' . $employee->id);
        \Log::info('Employee Name: ' . $employee->name);
        
        // Coba akses relasi langsung
        $sc = $employee->salaryComponents()->get();
        \Log::info('salaryComponents via query: ' . $sc->count() . ' items');
        \Log::info('salaryComponents data: ' . json_encode($sc->toArray()));
        
        $perms = $employee->permissions()->get();
        \Log::info('permissions via query: ' . $perms->count() . ' items');
        \Log::info('permissions data: ' . json_encode($perms->toArray()));

        // ✅ DEBUG 2: Load relasi
        $employee->load([
            'role:id,name',
            'salaryComponents.details',
            'permissions.permission',
        ]);
        
        \Log::info('After load - salaryComponents: ' . $employee->salaryComponents->count() . ' items');
        \Log::info('After load - permissions: ' . $employee->permissions->count() . ' items');

        // ✅ DEBUG 3: Convert to array
        $array = $employee->toArray();
        \Log::info('toArray keys: ' . implode(', ', array_keys($array)));
        \Log::info('Has salary_components key: ' . (isset($array['salary_components']) ? 'YES' : 'NO'));
        \Log::info('Has permissions key: ' . (isset($array['permissions']) ? 'YES' : 'NO'));
        
        if (isset($array['salary_components'])) {
            \Log::info('salary_components in array: ' . json_encode($array['salary_components']));
        }
        if (isset($array['permissions'])) {
            \Log::info('permissions in array: ' . json_encode($array['permissions']));
        }
        \Log::info('===========================');

        // ✅ RESPONSE - Coba 2 cara
        return response()->json([
            'status' => 'success',
            'data'   => $array, // Cara 1: toArray()
        ]);
    }

    // public function store(Request $request, $outletId)
    // {
    //     if (!$this->checkAccess($outletId)) {
    //         return response()->json(['message' => 'Akses ditolak'], 403);
    //     }

    //     if ($request->filled('salary_components') && is_string($request->salary_components)) {
    //         $request->merge([
    //             'salary_components' => json_decode($request->salary_components, true)
    //         ]);
    //     }

    //     if ($request->filled('permissions') && is_string($request->permissions)) {
    //         $request->merge(['permissions' => json_decode($request->permissions, true)]);
    //     }

    //     $validator = Validator::make($request->all(), [
    //         'role_id'                        => 'nullable|exists:outlet_employee_roles,id',
    //         'employee_code'                  => 'required|string|max:50|unique:employees,employee_code',
    //         'name'                           => 'required|string|max:255',
    //         'phone'                          => 'nullable|string|max:20',
    //         'email'                          => 'nullable|email|max:255',
    //         'password'                       => 'required|string|min:6',
    //         'default_base_salary'            => 'nullable|numeric|min:0',
    //         'overtime_salary_per_hour'       => 'nullable|numeric|min:0',
    //         'ktp_image'                      => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    //         'npwp_image'                     => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    //         'bpjs_kesehatan_image'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    //         'bpjs_ketenagakerjaan_image'     => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

    //         // Validasi salary components
    //         'salary_components'                        => 'nullable|array',
    //         'salary_components.*.name'                 => 'required|string|max:255',
    //         'salary_components.*.details'              => 'nullable|array',
    //         'salary_components.*.details.*.name'     => 'required|string|max:255',
    //         'salary_components.*.details.*.amount'   => 'required|numeric|min:0',
    //         'salary_components.*.details.*.type'     => 'required|string|max:100',
    //         'salary_components.*.details.*.duration' => 'required|string|max:50',

    //         // Validasi permissions
    //         'permissions'                          => 'nullable|array',
    //         'permissions.*.permission_id'          => 'required|exists:permissions,id',
    //         'permissions.*.allowed'                => 'required|boolean',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'message' => 'Validasi gagal',
    //             'errors'  => $validator->errors()->toArray(),
    //             'data_sent' => $request->except(['ktp_image', 'npwp_image', 'bpjs_kesehatan_image', 'bpjs_ketenagakerjaan_image']) // Debug data yang dikirim
    //         ], 422);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         $data = [
    //             'outlet_id'                      => $outletId,
    //             'role_id'                        => $request->role_id,
    //             'employee_code'                  => $request->employee_code,
    //             'name'                           => $request->name,
    //             'phone'                          => $request->phone,
    //             'email'                          => $request->email,
    //             'password'                       => bcrypt($request->password),
    //             'default_base_salary'            => $request->default_base_salary            ?? 0,
    //             'overtime_salary_per_hour'       => $request->overtime_salary_per_hour       ?? 0,
    //         ];

    //         if ($request->hasFile('ktp_image')) {
    //             $data['ktp_image_url'] = $this->uploadImage($request->file('ktp_image'), 'employees/ktp');
    //         }
    //         if ($request->hasFile('npwp_image')) {
    //             $data['npwp_image_url'] = $this->uploadImage($request->file('npwp_image'), 'employees/npwp');
    //         }
    //         if ($request->hasFile('bpjs_kesehatan_image')) {
    //             $data['bpjs_kesehatan_image_url'] = $this->uploadImage($request->file('bpjs_kesehatan_image'), 'employees/bpjs_kesehatan');
    //         }
    //         if ($request->hasFile('bpjs_ketenagakerjaan_image')) {
    //             $data['bpjs_ketenagakerjaan_image_url'] = $this->uploadImage($request->file('bpjs_ketenagakerjaan_image'), 'employees/bpjs_ketenagakerjaan');
    //         }

    //         $employee = Employee::create($data);

    //         // Simpan salary components beserta detailnya
    //         if ($request->filled('salary_components')) {
    //             foreach ($request->salary_components as $componentData) {
    //                 $component = SalaryComponent::create([
    //                     'employee_id' => $employee->id,
    //                     'name'        => $componentData['name'],
    //                 ]);

    //                 if (!empty($componentData['details'])) {
    //                     foreach ($componentData['details'] as $detail) {
    //                         DetailSalaryComponent::create([
    //                             'salary_component_id' => $component->id,
    //                             'name'                => $detail['name'],
    //                             'amount'              => $detail['amount'],
    //                             'type'                => $detail['type'],
    //                             'duration'            => $detail['duration'],
    //                         ]);
    //                     }
    //                 }
    //             }
    //         }

    //         // Simpan permissions
    //         if ($request->filled('permissions')) {
    //             $activePermissions = collect($request->permissions)
    //                 ->where('allowed', true)
    //                 ->values();
                
    //             foreach ($activePermissions as $perm) {
    //                 EmployeePermission::create([
    //                     'employee_id'   => $employee->id,
    //                     'outlet_id'     => $outletId,
    //                     'permission_id' => $perm['permission_id'],
    //                     'allowed'       => true,
    //                 ]);
    //             }
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'status'  => 'success',
    //             'message' => 'Karyawan berhasil ditambahkan',
    //             'data'    => $employee->load([
    //                 'role:id,name',
    //                 'salaryComponents.details',
    //                 'permissions.permission',
    //             ]),
    //         ], 201);

    //     } catch (\Illuminate\Database\QueryException $e) {
    //         DB::rollBack();
    //         \Log::error('Database Error: ' . $e->getMessage(), [
    //             'sql' => $e->getSql(),
    //             'bindings' => $e->getBindings(),
    //             'request_data' => $request->except(['password', 'ktp_image', 'npwp_image', 'bpjs_kesehatan_image', 'bpjs_ketenagakerjaan_image'])
    //         ]);
            
    //         return response()->json([
    //             'message' => 'Gagal menyimpan ke database',
    //             'error'   => $e->getMessage(),
    //             'hint'    => 'Periksa apakah semua data sudah benar dan tidak ada duplikasi',
    //         ], 500);
            
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         \Log::error('General Error: ' . $e->getMessage(), [
    //             'file' => $e->getFile(),
    //             'line' => $e->getLine(),
    //             'trace' => $e->getTraceAsString(),
    //             'request_data' => $request->except(['password', 'ktp_image', 'npwp_image', 'bpjs_kesehatan_image', 'bpjs_ketenagakerjaan_image'])
    //         ]);
            
    //         return response()->json([
    //             'message' => 'Terjadi kesalahan, data tidak tersimpan',
    //             'error'   => $e->getMessage(),
    //             'file'    => basename($e->getFile()), 
    //             'line'    => $e->getLine(),           
    //         ], 500);
    //     }
    // }

    public function store(Request $request, $outletId)
    {
        // ✅ 1. Cek akses outlet
        if (!$this->checkAccess($outletId)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk outlet ini.',
            ], 403);
        }

        // ✅ 2. Decode JSON strings
        if ($request->filled('salary_components') && is_string($request->salary_components)) {
            $decoded = json_decode($request->salary_components, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Format salary_components tidak valid',
                    'error'   => 'JSON Error: ' . json_last_error_msg(),
                ], 422);
            }
            $request->merge(['salary_components' => $decoded]);
        }

        if ($request->filled('permissions') && is_string($request->permissions)) {
            $decoded = json_decode($request->permissions, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Format permissions tidak valid',
                    'error'   => 'JSON Error: ' . json_last_error_msg(),
                ], 422);
            }
            $request->merge(['permissions' => $decoded]);
        }

        // ✅ 3. Validasi input
        $validator = Validator::make($request->all(), [
            'role_id'                        => 'nullable|exists:outlet_employee_roles,id',
            'employee_code'                  => 'required|string|max:50|unique:employees,employee_code',
            'name'                           => 'required|string|max:255',
            'phone'                          => 'nullable|string|max:20',
            'email'                          => 'nullable|email|max:255',
            'password'                       => 'required|string|min:6',
            'default_base_salary'            => 'nullable|numeric|min:0',
            'overtime_salary_per_hour'       => 'nullable|numeric|min:0',
            'ktp_image'                      => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'npwp_image'                     => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'bpjs_kesehatan_image'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'bpjs_ketenagakerjaan_image'     => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            // Validasi salary components
            'salary_components'                        => 'nullable|array',
            'salary_components.*.name'                 => 'required|string|max:255',
            'salary_components.*.details'              => 'nullable|array',
            'salary_components.*.details.*.name'     => 'required|string|max:255',
            'salary_components.*.details.*.amount'   => 'required|numeric|min:0',
            'salary_components.*.details.*.type'     => 'required|string|max:100',
            'salary_components.*.details.*.duration' => 'required|string|max:50',

            // Validasi permissions
            'permissions'                          => 'nullable|array',
            'permissions.*.permission_id'          => 'required|exists:permissions,id',
            'permissions.*.allowed'                => 'required|boolean',
        ], [
            // ✅ Custom error messages dalam bahasa Indonesia
            'employee_code.required' => 'Kode karyawan wajib diisi',
            'employee_code.unique'   => 'Kode karyawan sudah digunakan',
            'name.required'          => 'Nama karyawan wajib diisi',
            'password.required'      => 'Password wajib diisi',
            'password.min'           => 'Password minimal 6 karakter',
            'email.email'            => 'Format email tidak valid',
            'ktp_image.image'        => 'File KTP harus berupa gambar',
            'ktp_image.max'          => 'Ukuran file KTP maksimal 2MB',
            'npwp_image.image'       => 'File NPWP harus berupa gambar',
            'npwp_image.max'         => 'Ukuran file NPWP maksimal 2MB',
            'bpjs_kesehatan_image.image' => 'File BPJS Kesehatan harus berupa gambar',
            'bpjs_kesehatan_image.max'   => 'Ukuran file BPJS Kesehatan maksimal 2MB',
            'bpjs_ketenagakerjaan_image.image' => 'File BPJS Ketenagakerjaan harus berupa gambar',
            'bpjs_ketenagakerjaan_image.max'   => 'Ukuran file BPJS Ketenagakerjaan maksimal 2MB',
            'role_id.exists'         => 'Role/jabatan tidak ditemukan',
            'default_base_salary.numeric'      => 'Gaji pokok harus berupa angka',
            'overtime_salary_per_hour.numeric' => 'Upah lembur harus berupa angka',
            'permissions.*.permission_id.exists' => 'Permission ID tidak ditemukan di database',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal. Periksa kembali data yang dikirim.',
                'errors'  => $validator->errors()->toArray(),
            ], 422);
        }

        // ✅ 4. Mulai transaksi database
        DB::beginTransaction();

        try {
            // ✅ 5. Siapkan data employee
            $data = [
                'outlet_id'                => $outletId,
                'role_id'                  => $request->role_id,
                'employee_code'            => $request->employee_code,
                'name'                     => $request->name,
                'phone'                    => $request->phone,
                'email'                    => $request->email,
                'password'                 => bcrypt($request->password),
                'default_base_salary'      => $request->default_base_salary      ?? 0,
                'overtime_salary_per_hour' => $request->overtime_salary_per_hour ?? 0,
                'is_active'                => true,
            ];

            // ✅ 6. Upload gambar
            $uploadErrors = [];
            
            try {
                if ($request->hasFile('ktp_image')) {
                    $data['ktp_image_url'] = $this->uploadImage($request->file('ktp_image'), 'employees/ktp');
                }
            } catch (\Exception $e) {
                $uploadErrors[] = 'Gagal upload KTP: ' . $e->getMessage();
                \Log::error('Upload KTP Error: ' . $e->getMessage());
            }

            try {
                if ($request->hasFile('npwp_image')) {
                    $data['npwp_image_url'] = $this->uploadImage($request->file('npwp_image'), 'employees/npwp');
                }
            } catch (\Exception $e) {
                $uploadErrors[] = 'Gagal upload NPWP: ' . $e->getMessage();
                \Log::error('Upload NPWP Error: ' . $e->getMessage());
            }

            try {
                if ($request->hasFile('bpjs_kesehatan_image')) {
                    $data['bpjs_kesehatan_image_url'] = $this->uploadImage($request->file('bpjs_kesehatan_image'), 'employees/bpjs_kesehatan');
                }
            } catch (\Exception $e) {
                $uploadErrors[] = 'Gagal upload BPJS Kesehatan: ' . $e->getMessage();
                \Log::error('Upload BPJS Kesehatan Error: ' . $e->getMessage());
            }

            try {
                if ($request->hasFile('bpjs_ketenagakerjaan_image')) {
                    $data['bpjs_ketenagakerjaan_image_url'] = $this->uploadImage($request->file('bpjs_ketenagakerjaan_image'), 'employees/bpjs_ketenagakerjaan');
                }
            } catch (\Exception $e) {
                $uploadErrors[] = 'Gagal upload BPJS Ketenagakerjaan: ' . $e->getMessage();
                \Log::error('Upload BPJS Ketenagakerjaan Error: ' . $e->getMessage());
            }

            // Jika ada error upload, rollback dan kirim pesan error
            if (!empty($uploadErrors)) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Gagal mengupload file',
                    'errors'  => $uploadErrors,
                ], 500);
            }

            // ✅ 7. Simpan employee
            $employee = Employee::create($data);

            // ✅ 8. Simpan salary components
            if ($request->filled('salary_components')) {
                foreach ($request->salary_components as $index => $componentData) {
                    try {
                        $component = SalaryComponent::create([
                            'employee_id' => $employee->id,
                            'name'        => $componentData['name'],
                        ]);

                        if (!empty($componentData['details'])) {
                            foreach ($componentData['details'] as $detailIndex => $detail) {
                                DetailSalaryComponent::create([
                                    'salary_component_id' => $component->id,
                                    'name'                => $detail['name'],
                                    'amount'              => $detail['amount'],
                                    'type'                => $detail['type'],
                                    'duration'            => $detail['duration'],
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        DB::rollBack();
                        \Log::error("Error menyimpan salary component index $index: " . $e->getMessage());
                        return response()->json([
                            'status'  => 'error',
                            'message' => "Gagal menyimpan komponen gaji '{$componentData['name']}'",
                            'error'   => $e->getMessage(),
                        ], 500);
                    }
                }
            }

            // ✅ 9. Simpan permissions
            if ($request->filled('permissions')) {
                try {
                    $activePermissions = collect($request->permissions)
                        ->where('allowed', true)
                        ->values();
                    
                    foreach ($activePermissions as $perm) {
                        EmployeePermission::create([
                            'employee_id'   => $employee->id,
                            'outlet_id'     => $outletId,
                            'permission_id' => $perm['permission_id'],
                            'allowed'       => true,
                        ]);
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error("Error menyimpan permissions: " . $e->getMessage());
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Gagal menyimpan data izin akses',
                        'error'   => $e->getMessage(),
                    ], 500);
                }
            }

            // ✅ 10. Commit transaksi
            DB::commit();

            // ✅ 11. Siapkan response sukses (TANPA load relasi yang berpotensi error)
            $responseData = [
                'id'                  => $employee->id,
                'outlet_id'           => $employee->outlet_id,
                'role_id'             => $employee->role_id,
                'employee_code'       => $employee->employee_code,
                'name'                => $employee->name,
                'phone'               => $employee->phone,
                'email'               => $employee->email,
                'default_base_salary'      => $employee->default_base_salary,
                'overtime_salary_per_hour' => $employee->overtime_salary_per_hour,
                'ktp_image_url'            => $employee->ktp_image_url,
                'npwp_image_url'           => $employee->npwp_image_url,
                'bpjs_kesehatan_image_url' => $employee->bpjs_kesehatan_image_url,
                'bpjs_ketenagakerjaan_image_url' => $employee->bpjs_ketenagakerjaan_image_url,
                'created_at'          => $employee->created_at,
                'updated_at'          => $employee->updated_at,
            ];

            // ✅ 12. Opsional: Load relasi jika ada, tapi jangan gagalkan response jika error
            try {
                if (method_exists($employee, 'role')) {
                    $employee->load('role:id,name');
                    $responseData['role'] = $employee->role;
                }
            } catch (\Exception $e) {
                \Log::warning('Gagal load relasi role: ' . $e->getMessage());
            }

            try {
                if (method_exists($employee, 'salaryComponents')) {
                    $employee->load('salaryComponents.details');
                    $responseData['salary_components'] = $employee->salaryComponents;
                }
            } catch (\Exception $e) {
                \Log::warning('Gagal load relasi salaryComponents: ' . $e->getMessage());
            }

            try {
                if (method_exists($employee, 'permissions')) {
                    $employee->load('permissions.permission');
                    $responseData['permissions'] = $employee->permissions;
                }
            } catch (\Exception $e) {
                \Log::warning('Gagal load relasi permissions: ' . $e->getMessage());
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Karyawan berhasil ditambahkan',
                'data'    => $responseData,
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            // ✅ Handle database error spesifik
            DB::rollBack();
            
            $errorCode    = $e->getCode();
            $errorMessage = $e->getMessage();
            $userMessage  = 'Gagal menyimpan ke database';
            
            // Analisis error code untuk pesan yang lebih user-friendly
            if ($errorCode == '23000') {
                if (strpos($errorMessage, 'Duplicate entry') !== false) {
                    if (strpos($errorMessage, 'employee_code') !== false) {
                        $userMessage = 'Kode karyawan sudah digunakan. Silakan gunakan kode yang berbeda.';
                    } elseif (strpos($errorMessage, 'email') !== false) {
                        $userMessage = 'Email sudah digunakan oleh karyawan lain.';
                    } else {
                        $userMessage = 'Data duplikat ditemukan. Periksa kembali data yang dikirim.';
                    }
                }
            } elseif ($errorCode == '42S22') {
                $userMessage = 'Struktur database tidak sesuai. Hubungi administrator.';
            } elseif ($errorCode == 'HY000') {
                $userMessage = 'Koneksi database gagal. Silakan coba lagi.';
            }
            
            \Log::error('Database Error [' . $errorCode . ']: ' . $errorMessage, [
                'sql'          => $e->getSql(),
                'bindings'     => $e->getBindings(),
                'request_data' => $request->except(['password', 'ktp_image', 'npwp_image', 'bpjs_kesehatan_image', 'bpjs_ketenagakerjaan_image'])
            ]);
            
            return response()->json([
                'status'  => 'error',
                'message' => $userMessage,
                'error'   => $errorMessage,
                'code'    => $errorCode,
            ], 500);
            
        } catch (\Exception $e) {
            // ✅ Handle general error
            DB::rollBack();
            
            \Log::error('General Error: ' . $e->getMessage(), [
                'file'         => $e->getFile(),
                'line'         => $e->getLine(),
                'trace'        => $e->getTraceAsString(),
                'request_data' => $request->except(['password', 'ktp_image', 'npwp_image', 'bpjs_kesehatan_image', 'bpjs_ketenagakerjaan_image'])
            ]);
            
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada server',
                'error'   => $e->getMessage(),
                'file'    => basename($e->getFile()),
                'line'    => $e->getLine(),
            ], 500);
        }
    }

    public function update(Request $request, $outletId, $id)
    {
        // ✅ 1. Cek akses outlet
        if (!$this->checkAccess($outletId)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk outlet ini.',
            ], 403);
        }

        // ✅ 2. Cari employee
        $employee = $this->findEmployee($outletId, $id);
        if (!$employee) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan tidak ditemukan',
            ], 404);
        }

        // ✅ 3. Decode JSON strings dengan validasi error
        if ($request->filled('salary_components') && is_string($request->salary_components)) {
            $decoded = json_decode($request->salary_components, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Format salary_components tidak valid',
                    'error'   => 'JSON Error: ' . json_last_error_msg(),
                ], 422);
            }
            $request->merge(['salary_components' => $decoded]);
        }

        if ($request->filled('permissions') && is_string($request->permissions)) {
            $decoded = json_decode($request->permissions, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Format permissions tidak valid',
                    'error'   => 'JSON Error: ' . json_last_error_msg(),
                ], 422);
            }
            $request->merge(['permissions' => $decoded]);
        }

        // ✅ 4. Validasi input (password & email nullable untuk update)
        $validator = Validator::make($request->all(), [
            'role_id'                        => 'nullable|exists:outlet_employee_roles,id',
            'employee_code'                  => 'required|string|max:50|unique:employees,employee_code,' . $id,
            'name'                           => 'required|string|max:255',
            'phone'                          => 'nullable|string|max:20',
            'email'                          => 'nullable|email|max:255|unique:employees,email,' . $id, // ✅ Nullable + unique kecuali dirinya
            'password'                       => 'nullable|string|min:6', // ✅ Nullable untuk update
            'default_base_salary'            => 'nullable|numeric|min:0',
            'overtime_salary_per_hour'       => 'nullable|numeric|min:0',
            'ktp_image'                      => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'npwp_image'                     => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'bpjs_kesehatan_image'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'bpjs_ketenagakerjaan_image'     => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            // ✅ Flag untuk hapus gambar
            'delete_ktp_image'                  => 'nullable|boolean',
            'delete_npwp_image'                 => 'nullable|boolean',
            'delete_bpjs_kesehatan_image'       => 'nullable|boolean',
            'delete_bpjs_ketenagakerjaan_image' => 'nullable|boolean',

            'salary_components'                        => 'nullable|array',
            'salary_components.*.name'                 => 'required|string|max:255',
            'salary_components.*.details'              => 'nullable|array',
            'salary_components.*.details.*.name'       => 'required|string|max:255',
            'salary_components.*.details.*.amount'     => 'required|numeric|min:0',
            'salary_components.*.details.*.type'       => 'required|string|max:100',
            'salary_components.*.details.*.duration'   => 'required|string|max:50',

            'permissions'                          => 'nullable|array',
            'permissions.*.permission_id'          => 'required|exists:permissions,id',
            'permissions.*.allowed'                => 'required|boolean',
        ], [
            'employee_code.required' => 'Kode karyawan wajib diisi',
            'employee_code.unique'   => 'Kode karyawan sudah digunakan',
            'name.required'          => 'Nama karyawan wajib diisi',
            'password.min'           => 'Password minimal 6 karakter',
            'email.email'            => 'Format email tidak valid',
            'email.unique'           => 'Email sudah digunakan oleh karyawan lain',
            'ktp_image.image'        => 'File KTP harus berupa gambar',
            'ktp_image.max'          => 'Ukuran file KTP maksimal 2MB',
            'npwp_image.image'       => 'File NPWP harus berupa gambar',
            'npwp_image.max'         => 'Ukuran file NPWP maksimal 2MB',
            'bpjs_kesehatan_image.image' => 'File BPJS Kesehatan harus berupa gambar',
            'bpjs_kesehatan_image.max'   => 'Ukuran file BPJS Kesehatan maksimal 2MB',
            'bpjs_ketenagakerjaan_image.image' => 'File BPJS Ketenagakerjaan harus berupa gambar',
            'bpjs_ketenagakerjaan_image.max'   => 'Ukuran file BPJS Ketenagakerjaan maksimal 2MB',
            'role_id.exists'         => 'Role/jabatan tidak ditemukan',
            'default_base_salary.numeric'      => 'Gaji pokok harus berupa angka',
            'overtime_salary_per_hour.numeric' => 'Upah lembur harus berupa angka',
            'permissions.*.permission_id.exists' => 'Permission ID tidak ditemukan di database',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal. Periksa kembali data yang dikirim.',
                'errors'  => $validator->errors()->toArray(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // ✅ 5. Siapkan data update
            $data = [
                'role_id'                  => $request->role_id,
                'employee_code'            => $request->employee_code,
                'name'                     => $request->name,
                'phone'                    => $request->phone,
                'email'                    => $request->email,
                'default_base_salary'      => $request->default_base_salary      ?? $employee->default_base_salary,
                'overtime_salary_per_hour' => $request->overtime_salary_per_hour ?? $employee->overtime_salary_per_hour,
            ];

            // ✅ Update password hanya jika dikirim
            if ($request->filled('password')) {
                $data['password'] = bcrypt($request->password);
            }

            // ✅ 6. Handle upload & delete gambar
            $uploadErrors = [];

            // KTP
            try {
                if ($request->hasFile('ktp_image')) {
                    // Hapus file lama
                    if ($employee->ktp_image_url) {
                        $this->deleteImage($employee->ktp_image_url);
                    }
                    $data['ktp_image_url'] = $this->uploadImage($request->file('ktp_image'), 'employees/ktp');
                } elseif ($request->boolean('delete_ktp_image')) {
                    // ✅ Hapus gambar jika flag delete dikirim
                    if ($employee->ktp_image_url) {
                        $this->deleteImage($employee->ktp_image_url);
                    }
                    $data['ktp_image_url'] = null;
                }
            } catch (\Exception $e) {
                $uploadErrors[] = 'Gagal upload/hapus KTP: ' . $e->getMessage();
                \Log::error('KTP Error: ' . $e->getMessage());
            }

            // NPWP
            try {
                if ($request->hasFile('npwp_image')) {
                    if ($employee->npwp_image_url) {
                        $this->deleteImage($employee->npwp_image_url);
                    }
                    $data['npwp_image_url'] = $this->uploadImage($request->file('npwp_image'), 'employees/npwp');
                } elseif ($request->boolean('delete_npwp_image')) {
                    if ($employee->npwp_image_url) {
                        $this->deleteImage($employee->npwp_image_url);
                    }
                    $data['npwp_image_url'] = null;
                }
            } catch (\Exception $e) {
                $uploadErrors[] = 'Gagal upload/hapus NPWP: ' . $e->getMessage();
                \Log::error('NPWP Error: ' . $e->getMessage());
            }

            // BPJS Kesehatan
            try {
                if ($request->hasFile('bpjs_kesehatan_image')) {
                    if ($employee->bpjs_kesehatan_image_url) {
                        $this->deleteImage($employee->bpjs_kesehatan_image_url);
                    }
                    $data['bpjs_kesehatan_image_url'] = $this->uploadImage($request->file('bpjs_kesehatan_image'), 'employees/bpjs_kesehatan');
                } elseif ($request->boolean('delete_bpjs_kesehatan_image')) {
                    if ($employee->bpjs_kesehatan_image_url) {
                        $this->deleteImage($employee->bpjs_kesehatan_image_url);
                    }
                    $data['bpjs_kesehatan_image_url'] = null;
                }
            } catch (\Exception $e) {
                $uploadErrors[] = 'Gagal upload/hapus BPJS Kesehatan: ' . $e->getMessage();
                \Log::error('BPJS Kesehatan Error: ' . $e->getMessage());
            }

            // BPJS Ketenagakerjaan
            try {
                if ($request->hasFile('bpjs_ketenagakerjaan_image')) {
                    if ($employee->bpjs_ketenagakerjaan_image_url) {
                        $this->deleteImage($employee->bpjs_ketenagakerjaan_image_url);
                    }
                    $data['bpjs_ketenagakerjaan_image_url'] = $this->uploadImage($request->file('bpjs_ketenagakerjaan_image'), 'employees/bpjs_ketenagakerjaan');
                } elseif ($request->boolean('delete_bpjs_ketenagakerjaan_image')) {
                    if ($employee->bpjs_ketenagakerjaan_image_url) {
                        $this->deleteImage($employee->bpjs_ketenagakerjaan_image_url);
                    }
                    $data['bpjs_ketenagakerjaan_image_url'] = null;
                }
            } catch (\Exception $e) {
                $uploadErrors[] = 'Gagal upload/hapus BPJS Ketenagakerjaan: ' . $e->getMessage();
                \Log::error('BPJS Ketenagakerjaan Error: ' . $e->getMessage());
            }

            if (!empty($uploadErrors)) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Gagal memproses file gambar',
                    'errors'  => $uploadErrors,
                ], 500);
            }

            // ✅ 7. Update employee
            $employee->update($data);

            // ✅ 8. Update salary components (hapus lama, buat baru)
            if ($request->has('salary_components')) {
                try {
                    // Hapus detail dulu, lalu component
                    $employee->salaryComponents()->each(function ($component) {
                        $component->details()->delete();
                        $component->delete();
                    });

                    foreach ($request->salary_components as $index => $componentData) {
                        $component = SalaryComponent::create([
                            'employee_id' => $employee->id,
                            'name'        => $componentData['name'],
                        ]);

                        if (!empty($componentData['details'])) {
                            foreach ($componentData['details'] as $detail) {
                                DetailSalaryComponent::create([
                                    'salary_component_id' => $component->id,
                                    'name'                => $detail['name'],
                                    'amount'              => $detail['amount'],
                                    'type'                => $detail['type'],
                                    'duration'            => $detail['duration'],
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error("Error update salary components: " . $e->getMessage());
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Gagal memperbarui komponen gaji',
                        'error'   => $e->getMessage(),
                    ], 500);
                }
            }

            // ✅ 9. Update permissions (hanya yang allowed: true)
            if ($request->has('permissions')) {
                try {
                    EmployeePermission::where('employee_id', $employee->id)
                                    ->where('outlet_id', $outletId)
                                    ->delete();

                    $activePermissions = collect($request->permissions)
                        ->where('allowed', true)
                        ->values();

                    foreach ($activePermissions as $perm) {
                        EmployeePermission::create([
                            'employee_id'   => $employee->id,
                            'outlet_id'     => $outletId,
                            'permission_id' => $perm['permission_id'],
                            'allowed'       => true,
                        ]);
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error("Error update permissions: " . $e->getMessage());
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Gagal memperbarui izin akses',
                        'error'   => $e->getMessage(),
                    ], 500);
                }
            }

            DB::commit();

            // ✅ 10. Response sukses
            $responseData = [
                'id'                  => $employee->id,
                'outlet_id'           => $employee->outlet_id,
                'role_id'             => $employee->role_id,
                'employee_code'       => $employee->employee_code,
                'name'                => $employee->name,
                'phone'               => $employee->phone,
                'email'               => $employee->email,
                'default_base_salary'      => $employee->default_base_salary,
                'overtime_salary_per_hour' => $employee->overtime_salary_per_hour,
                'ktp_image_url'            => $employee->ktp_image_url,
                'npwp_image_url'           => $employee->npwp_image_url,
                'bpjs_kesehatan_image_url' => $employee->bpjs_kesehatan_image_url,
                'bpjs_ketenagakerjaan_image_url' => $employee->bpjs_ketenagakerjaan_image_url,
                'created_at'          => $employee->created_at,
                'updated_at'          => $employee->updated_at,
            ];

            // Load relasi (opsional)
            try {
                if (method_exists($employee, 'role')) {
                    $employee->load('role:id,name');
                    $responseData['role'] = $employee->role;
                }
            } catch (\Exception $e) {
                \Log::warning('Gagal load relasi role: ' . $e->getMessage());
            }

            try {
                if (method_exists($employee, 'salaryComponents')) {
                    $employee->load('salaryComponents.details');
                    $responseData['salary_components'] = $employee->salaryComponents;
                }
            } catch (\Exception $e) {
                \Log::warning('Gagal load relasi salaryComponents: ' . $e->getMessage());
            }

            try {
                if (method_exists($employee, 'permissions')) {
                    $employee->load('permissions.permission');
                    $responseData['permissions'] = $employee->permissions;
                }
            } catch (\Exception $e) {
                \Log::warning('Gagal load relasi permissions: ' . $e->getMessage());
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Data karyawan berhasil diperbarui',
                'data'    => $responseData,
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            
            $errorCode    = $e->getCode();
            $errorMessage = $e->getMessage();
            $userMessage  = 'Gagal menyimpan ke database';
            
            if ($errorCode == '23000') {
                if (strpos($errorMessage, 'Duplicate entry') !== false) {
                    if (strpos($errorMessage, 'employee_code') !== false) {
                        $userMessage = 'Kode karyawan sudah digunakan.';
                    } elseif (strpos($errorMessage, 'email') !== false) {
                        $userMessage = 'Email sudah digunakan oleh karyawan lain.';
                    } else {
                        $userMessage = 'Data duplikat ditemukan.';
                    }
                }
            } elseif ($errorCode == '42S22') {
                $userMessage = 'Struktur database tidak sesuai.';
            }
            
            \Log::error('Database Error [' . $errorCode . ']: ' . $errorMessage);
            
            return response()->json([
                'status'  => 'error',
                'message' => $userMessage,
                'error'   => $errorMessage,
                'code'    => $errorCode,
            ], 500);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('General Error: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada server',
                'error'   => $e->getMessage(),
                'file'    => basename($e->getFile()),
                'line'    => $e->getLine(),
            ], 500);
        }
    }

    // DELETE
    public function destroy($outletId, $id)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $employee = $this->findEmployee($outletId, $id);

        if (!$employee) {
            return response()->json(['message' => 'Karyawan tidak ditemukan'], 404);
        }

        // Hapus semua file gambar dari storage
        foreach ([
            $employee->ktp_image_url,
            $employee->npwp_image_url,
            $employee->bpjs_kesehatan_image_url,
            $employee->bpjs_ketenagakerjaan_image_url,
        ] as $imagePath) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        $employee->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Karyawan berhasil dihapus',
        ]);
    }

    public function activate(Request $request, $outletId, $id)
    {
        // ✅ Cek akses outlet
        if (!$this->checkAccess($outletId)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk outlet ini.',
            ], 403);
        }

        // ✅ Cari employee
        $employee = $this->findEmployee($outletId, $id);
        
        if (!$employee) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan tidak ditemukan',
            ], 404);
        }

        // ✅ Cek apakah sudah aktif
        if ($employee->is_active) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan sudah dalam keadaan aktif',
            ], 422);
        }

        // ✅ Aktifkan
        $employee->update(['is_active' => true]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Karyawan berhasil diaktifkan',
            'data'    => [
                'id'        => $employee->id,
                'name'      => $employee->name,
                'is_active' => $employee->is_active,
            ],
        ]);
    }

    public function deactivate(Request $request, $outletId, $id)
    {
        // ✅ Cek akses outlet
        if (!$this->checkAccess($outletId)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk outlet ini.',
            ], 403);
        }

        // ✅ Cari employee
        $employee = $this->findEmployee($outletId, $id);
        
        if (!$employee) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan tidak ditemukan',
            ], 404);
        }

        // ✅ Cek apakah sudah nonaktif
        if (!$employee->is_active) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan sudah dalam keadaan nonaktif',
            ], 422);
        }

        // ✅ Nonaktifkan
        $employee->update(['is_active' => false]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Karyawan berhasil dinonaktifkan',
            'data'    => [
                'id'        => $employee->id,
                'name'      => $employee->name,
                'is_active' => $employee->is_active,
            ],
        ]);
    }
}