<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Generate Unique 6-Digit owner_id
        $uniqueOwnerId = $this->generateUniqueOwnerId();

        // 3. Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'owner', // Hardcoded sesuai permintaan
            'owner_id' => $uniqueOwnerId,
        ]);

        // 4. Generate Token (Sanctum)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 201);
    }

    private function generateUniqueOwnerId()
    {
        do {
            $number = random_int(100000, 999999);
        } while (User::where('owner_id', $number)->exists());

        return $number;
    }

    public function login(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Coba login sebagai User (Owner/Admin)
        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            // ✅ Ambil outlet pertama milik owner
            $outlet = $user->outlets()->first();
            $outletId = $outlet ? $outlet->id : null;

            return response()->json([
                'status'  => 'success',
                'message' => 'Login berhasil',
                'data'    => [
                    'user' => [
                        'id'         => $user->id,
                        'name'       => $user->name,
                        'email'      => $user->email,
                        'role'       => $user->role,
                        'outlet_id'  => $outletId,  // ✅ Tambahkan outlet_id
                        'type'       => 'owner',
                    ],
                    'outlet' => $outlet ? [           // ✅ Tambahkan data outlet
                        'id'   => $outlet->id,
                        'name' => $outlet->name,
                    ] : null,
                    'access_token' => $token,
                    'token_type'   => 'Bearer',
                ]
            ]);
        }

        // 3. Coba login sebagai Employee (Karyawan)
        $employee = Employee::where('email', $request->email)
            ->where('is_active', true)
            ->with('outlet:id,name') // ✅ Load relasi outlet
            ->first();

        if ($employee && Hash::check($request->password, $employee->password)) {
            $employee->tokens()->delete();
            $token = $employee->createToken('employee_auth_token')->plainTextToken;

            return response()->json([
                'status'  => 'success',
                'message' => 'Login berhasil',
                'data'    => [
                    'user' => [
                        'id'             => $employee->id,
                        'name'           => $employee->name,
                        'email'          => $employee->email,
                        'employee_code'  => $employee->employee_code,
                        'role'           => $employee->role?->name,
                        'role_id'        => $employee->role_id,
                        'outlet_id'      => $employee->outlet_id, // ✅ Sudah ada
                        'phone'          => $employee->phone,
                        'type'           => 'employee',
                    ],
                    'outlet' => $employee->outlet ? [            // ✅ Tambahkan data outlet
                        'id'   => $employee->outlet->id,
                        'name' => $employee->outlet->name,
                    ] : null,
                    'outlet_id'     => $employee->outlet_id,     // ✅ Tambahkan outlet_id di root data
                    'access_token'  => $token,
                    'token_type'    => 'Bearer',
                ]
            ]);
        }

        // 4. Tidak ditemukan
        return response()->json([
            'status'  => 'error',
            'message' => 'Email atau password salah'
        ], 401);
    }

    public function logout(Request $request)
    {
        // Menghapus token yang digunakan untuk request saat ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }
}