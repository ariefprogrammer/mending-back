<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerBalanceMutation;
use App\Models\Outlet;
use App\Models\TransactionCashBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerBalanceMutationController extends Controller
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
    // GET /outlets/{outletId}/customers/{customerId}/balance-mutations
    // Riwayat mutasi saldo customer

    public function index(Request $request, $outletId, $customerId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $customer = Customer::where('id', $customerId)
                            ->where('outlet_id', $outletId)
                            ->first();

        if (!$customer) {
            return response()->json(['status' => 'error', 'message' => 'Customer tidak ditemukan'], 404);
        }

        $mutations = CustomerBalanceMutation::where('customer_id', $customerId)
            ->with([
                'createdByUser:id,name',
                'createdByEmployee:id,name',
            ])
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'status'  => 'success',
            'balance' => $customer->balance,
            'data'    => $mutations,
        ]);
    }

    // ─── STORE ───────────────────────────────────────────────────
    // POST /outlets/{outletId}/customers/{customerId}/balance-mutations
    // Topup / deduction / refund / adjustment saldo customer

    public function store(Request $request, $outletId, $customerId)
    {
        if (!$this->checkAccess($outletId)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $validator = Validator::make($request->all(), [
            'type'              => 'required|in:' . implode(',', CustomerBalanceMutation::TYPES),
            'amount'            => 'required|numeric|min:1',
            'payment_method_id' => 'nullable|integer|exists:payment_methods,id',
            'cash_book_id'      => 'nullable|integer|exists:outlet_cash_books,id',
            'notes'             => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $customer = Customer::where('id', $customerId)
                            ->where('outlet_id', $outletId)
                            ->lockForUpdate()
                            ->first();

        if (!$customer) {
            return response()->json(['status' => 'error', 'message' => 'Customer tidak ditemukan'], 404);
        }

        if (in_array($request->type, CustomerBalanceMutation::DEBIT_TYPES)) {
            if ($customer->balance < $request->amount) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Saldo customer tidak mencukupi',
                    'balance' => $customer->balance,
                ], 422);
            }
        }

        $user       = auth('sanctum')->user();
        $userId     = $user instanceof \App\Models\User     ? $user->id : null;
        $employeeId = $user instanceof \App\Models\Employee ? $user->id : null;

        try {
            DB::beginTransaction();

            $balanceBefore = $customer->balance;
            $balanceAfter  = in_array($request->type, CustomerBalanceMutation::CREDIT_TYPES)
                ? $balanceBefore + $request->amount
                : $balanceBefore - $request->amount;

            // Insert mutasi saldo
            $mutation = CustomerBalanceMutation::create([
                'customer_id'            => $customerId,
                'outlet_id'              => $outletId,
                'type'                   => $request->type,
                'amount'                 => $request->amount,
                'payment_method_id'      => $request->payment_method_id,
                'cash_book_id'           => $request->cash_book_id,
                'balance_before'         => $balanceBefore,
                'balance_after'          => $balanceAfter,
                'notes'                  => $request->notes,
                'created_by_user_id'     => $userId,
                'created_by_employee_id' => $employeeId,
            ]);

            // Update saldo customer
            $customer->update(['balance' => $balanceAfter]);

            // Catat ke transaction_cash_books jika cash_book_id disertakan
            if (!empty($request->cash_book_id)) {
                TransactionCashBook::create([
                    'outlet_cash_book_id'    => $request->cash_book_id,
                    'outlet_id'              => $outletId,
                    'type'                   => in_array($request->type, CustomerBalanceMutation::CREDIT_TYPES) ? 'in' : 'out',
                    'amount'                 => $request->amount,
                    'description'            => "Topup saldo - {$customer->name}",
                    'transaction_date'       => now()->toDateString(),
                    'created_by_user_id'     => $userId,
                    'created_by_employee_id' => $employeeId,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => $this->_successMessage($request->type),
                'data'    => $mutation->load(['createdByUser:id,name', 'createdByEmployee:id,name']),
                'balance' => $balanceAfter,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('CustomerBalanceMutation Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memproses mutasi saldo',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── HELPER ──────────────────────────────────────────────────

    private function _successMessage(string $type): string
    {
        return match($type) {
            'topup'      => 'Topup saldo berhasil',
            'deduction'  => 'Pengurangan saldo berhasil',
            'refund'     => 'Refund saldo berhasil',
            'adjustment' => 'Adjustment saldo berhasil',
            default      => 'Mutasi saldo berhasil',
        };
    }
}