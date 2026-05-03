<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    private function validateOutlet($outletId)
    {
        $user = auth('sanctum')->user();
        return Outlet::where('id', $outletId)->where('user_id', $user->id)->first();
    }

    public function index(Request $request, $outletId)
    {
        if (!$this->validateOutlet($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Customer::where('outlet_id', $outletId);

        if ($request->has('type')) {
            $query->where('customer_type', $request->type);
        }

        // Fitur pencarian
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->get(); 

        return response()->json([
            'status' => 'success',
            'data' => $customers
        ]);
    }

    // SIMPAN CUSTOMER
    public function store(Request $request, $outletId)
    {
        if (!$this->validateOutlet($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'customer_type' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'url_address' => 'nullable|url',
            'balance' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $customer = Customer::create(array_merge(
            $request->all(),
            ['outlet_id' => $outletId]
        ));

        return response()->json(['status' => 'success', 'data' => $customer], 201);
    }

    // DETAIL CUSTOMER
    public function show($outletId, $id)
    {
        if (!$this->validateOutlet($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $customer = Customer::where('outlet_id', $outletId)->find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $customer]);
    }

    // UPDATE CUSTOMER
    public function update(Request $request, $outletId, $id)
    {
        if (!$this->validateOutlet($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $customer = Customer::where('outlet_id', $outletId)->find($id);
        if (!$customer) return response()->json(['message' => 'Not Found'], 404);

        $customer->update($request->all());

        return response()->json(['status' => 'success', 'data' => $customer]);
    }

    // DELETE CUSTOMER
    public function destroy($outletId, $id)
    {
        if (!$this->validateOutlet($outletId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $customer = Customer::where('outlet_id', $outletId)->find($id);
        if (!$customer) return response()->json(['message' => 'Not Found'], 404);

        $customer->delete();

        return response()->json(['status' => 'success', 'message' => 'Customer deleted']);
    }
}