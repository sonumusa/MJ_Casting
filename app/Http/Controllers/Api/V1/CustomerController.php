<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * List all active customers
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::active();

        // Search
        if ($request->search) {
            $query->searchable($request->search);
        }

        $customers = $query->latest('name')
            ->paginate($request->per_page ?? 25);

        return response()->json(CustomerResource::collection($customers));
    }

    /**
     * Get single customer
     */
    public function show(Customer $customer): JsonResponse
    {
        return response()->json(new CustomerResource($customer->load('invoices')));
    }

    /**
     * Create customer
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'cnic' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $customer = Customer::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'data' => new CustomerResource($customer),
        ], 201);
    }

    /**
     * Update customer
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'cnic' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $customer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data' => new CustomerResource($customer->fresh()),
        ]);
    }

    /**
     * Delete customer
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully',
        ]);
    }

    /**
     * Get customer balance
     */
    public function balance(Customer $customer): JsonResponse
    {
        return response()->json([
            'opening_balance' => $customer->opening_balance,
            'current_balance' => $customer->getCurrentBalance(),
            'total_invoiced' => $customer->getTotalInvoiced(),
            'total_wasooli' => $customer->getTotalWasooli(),
        ]);
    }

    /**
     * Get customer ledger
     */
    public function ledger(Customer $customer): JsonResponse
    {
        $service = app(\App\Services\LedgerService::class);
        $ledger = $service->getCustomerLedger($customer);

        return response()->json($ledger);
    }
}
