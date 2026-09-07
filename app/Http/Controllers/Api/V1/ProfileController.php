<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Menampilkan data user yang sedang login (owner atau employee).
     */
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        if ($user instanceof User) {
            $outlet = $user->outlets()->first();
            $subscription = $user->currentSubscription()->with('plan')->first();

            $planData = $subscription ? [
                'plan_slug'              => $subscription->plan->slug,
                'plan_name'              => $subscription->plan->name,
                'transaction_limit'      => $subscription->plan->transaction_limit,
                'remaining_transactions' => $subscription->remainingTransactions(),
                'trial_ends_at'          => $subscription->trial_ends_at?->toIso8601String(),
                'ends_at'                => $subscription->ends_at?->toIso8601String(),
                'is_trial_expired'       => $subscription->isTrialExpired(),
                'allowed_menus'          => $subscription->plan->allowed_menus,
            ] : null;

            return response()->json([
                'status'  => 'success',
                'message' => 'Data profil berhasil diambil',
                'data'    => [
                    'user' => [
                        'id'        => $user->id,
                        'name'      => $user->name,
                        'email'     => $user->email,
                        'phone'     => $user->phone,
                        'role'      => $user->role,
                        'outlet_id' => $outlet?->id,
                        'type'      => 'owner',
                    ],
                    'outlet'       => $outlet ? [
                        'id'   => $outlet->id,
                        'name' => $outlet->name,
                    ] : null,
                    'subscription' => $planData,
                ]
            ]);
        }

        if ($user instanceof Employee) {
            $user->load(['outlet:id,name', 'permissions.permission']);

            $planData = null;
            if ($user->outlet && $user->outlet->user) {
                $subscription = $user->outlet->user->currentSubscription()->with('plan')->first();
                if ($subscription) {
                    $planData = [
                        'plan_slug'              => $subscription->plan->slug,
                        'plan_name'              => $subscription->plan->name,
                        'transaction_limit'      => $subscription->plan->transaction_limit,
                        'remaining_transactions' => $subscription->remainingTransactions(),
                        'trial_ends_at'          => $subscription->trial_ends_at?->toIso8601String(),
                        'ends_at'                => $subscription->ends_at?->toIso8601String(),
                        'is_trial_expired'       => $subscription->isTrialExpired(),
                        'allowed_menus'          => $subscription->plan->allowed_menus,
                    ];
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Data profil berhasil diambil',
                'data'    => [
                    'user' => [
                        'id'            => $user->id,
                        'name'          => $user->name,
                        'email'         => $user->email,
                        'employee_code' => $user->employee_code,
                        'phone'         => $user->phone,
                        'role'          => $user->role?->name,
                        'role_id'       => $user->role_id,
                        'outlet_id'     => $user->outlet_id,
                        'type'          => 'employee',
                        'permissions'   => $user->permissions
                                ->filter(fn($p) => $p->permission !== null)
                                ->map(fn($p) => ['key' => $p->permission->module . '.' . $p->permission->action])
                                ->values(),
                    ],
                    'outlet'       => $user->outlet ? [
                        'id'   => $user->outlet->id,
                        'name' => $user->outlet->name,
                    ] : null,
                    'subscription' => $planData,
                ]
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
    }

    /**
     * Update data owner (nama, telepon, email, password).
     * Khusus untuk user tipe User (owner) — bukan employee.
     */
    public function updateOwner(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user || !($user instanceof User)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|required|string|max:100',
            'phone'    => 'sometimes|nullable|string|max:20',
            'email'    => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => 'sometimes|nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        $updateData = [];
        if (array_key_exists('name', $data))  $updateData['name']  = $data['name'];
        if (array_key_exists('phone', $data)) $updateData['phone'] = $data['phone'];
        if (array_key_exists('email', $data)) $updateData['email'] = $data['email'];
        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data owner berhasil diperbarui',
            'data'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role'  => $user->role,
            ]
        ]);
    }
}