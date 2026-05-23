<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Customer;
use App\Http\Resources\InvoiceResource;
use App\Services\GoldCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private GoldCalculationService $calculationService
    ) {}

    /**
     * Get all invoices
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with('customer')
            ->where('status', 'active');

        // Filter by customer
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by date range
        if ($request->from_date && $request->to_date) {
            $query->whereDate('invoice_date', '>=', $request->from_date)
                  ->whereDate('invoice_date', '<=', $request->to_date);
        }

        // Search
        if ($request->search) {
            $query->searchable($request->search);
        }

        $invoices = $query->latest('invoice_date')
            ->latest('id')
            ->paginate($request->per_page ?? 25);

        return response()->json(InvoiceResource::collection($invoices));
    }

    /**
     * Get single invoice
     */
    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json(new InvoiceResource($invoice));
    }

    /**
     * Calculate invoice values
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'casting_weight' => 'required|numeric|min:0',
            'waste_weight' => 'nullable|numeric|min:0',
            'ratti' => 'nullable|numeric|min:0',
            'ratti_rate' => 'nullable|numeric|min:0',
            'rp_rate' => 'required|numeric|min:0',
            'rp_mazdori_weight' => 'nullable|numeric|min:0',
            'rp_mazdori_rate' => 'nullable|numeric|min:0',
            'casting_mazdori_weight' => 'nullable|numeric|min:0',
            'casting_mazdori_rate' => 'nullable|numeric|min:0',
            'wasooli' => 'nullable|numeric|min:0',
            'previous_balance' => 'nullable|numeric',
            'manual_waste_override' => 'nullable|boolean',
            'manual_male_waste_override' => 'nullable|boolean',
            'manual_ratti_override' => 'nullable|boolean',
        ]);

        // Map frontend flags to service expectations
        if ($request->boolean('manual_waste_override')) {
            $validated['waste_weight'] = $request->input('waste_weight');
        } else {
            unset($validated['waste_weight']);
        }

        if ($request->boolean('manual_ratti_override')) {
            $validated['ratti'] = $request->input('ratti');
        } else {
            unset($validated['ratti']);
        }

        if ($request->boolean('manual_male_waste_override')) {
            $validated['male_waste'] = $request->input('male_waste');
        } else {
            unset($validated['male_waste']);
        }

        $calculations = $this->calculationService->calculate($validated);

        return response()->json([
            'success' => true,
            'data' => $calculations,
        ]);
    }

    /**
     * Create invoice
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'casting_weight' => 'required|numeric|min:0',
            'waste_weight' => 'required|numeric|min:0',
            'ratti' => 'nullable|numeric|min:0',
            'ratti_rate' => 'nullable|numeric|min:0',
            'rp_rate' => 'required|numeric|min:0',
            'rp_mazdori_weight' => 'nullable|numeric|min:0',
            'rp_mazdori_rate' => 'nullable|numeric|min:0',
            'casting_mazdori_weight' => 'nullable|numeric|min:0',
            'casting_mazdori_rate' => 'nullable|numeric|min:0',
            'wasooli' => 'nullable|numeric|min:0',
            'manual_book_no' => 'nullable|string|max:50',
            'remarks' => 'nullable|string',
        ]);

        // Use service to create invoice
        $service = app(\App\Services\InvoiceService::class);
        $invoice = $service->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Invoice created successfully',
            'data' => new InvoiceResource($invoice->fresh()),
        ], 201);
    }

    /**
     * Update invoice
     */
    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'invoice_date' => 'nullable|date',
            'casting_weight' => 'nullable|numeric|min:0',
            'waste_weight' => 'nullable|numeric|min:0',
            'ratti' => 'nullable|numeric|min:0',
            'ratti_rate' => 'nullable|numeric|min:0',
            'rp_rate' => 'nullable|numeric|min:0',
            'rp_mazdori_weight' => 'nullable|numeric|min:0',
            'rp_mazdori_rate' => 'nullable|numeric|min:0',
            'casting_mazdori_weight' => 'nullable|numeric|min:0',
            'casting_mazdori_rate' => 'nullable|numeric|min:0',
            'wasooli' => 'nullable|numeric|min:0',
            'manual_book_no' => 'nullable|string|max:50',
            'remarks' => 'nullable|string',
        ]);

        // Use service to update invoice
        $service = app(\App\Services\InvoiceService::class);
        $invoice = $service->update($invoice, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully',
            'data' => new InvoiceResource($invoice->fresh()),
        ]);
    }

    /**
     * Delete invoice
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        $service = app(\App\Services\InvoiceService::class);
        $service->delete($invoice);

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully',
        ]);
    }

    /**
     * Recalculate customer balance chains for all customers.
     */
    public function recalculateAll(Request $request): JsonResponse
    {
        $customers = Customer::whereHas('invoices')->get();

        foreach ($customers as $customer) {
            $this->calculationService->recalculateChain($customer);
        }

        return response()->json([
            'success' => true,
            'message' => 'Recalculated all customer balance chains.',
        ]);
    }

    /**
     * Return invoice changes for incremental offline sync.
     */
    public function getChanges(Request $request): JsonResponse
    {
        $since = $request->query('since');

        $query = Invoice::with('customer')
            ->where('status', 'active');

        if ($since) {
            $query->where('updated_at', '>', $since);
        }

        $invoices = $query->orderBy('updated_at')->get();

        return response()->json([
            'success' => true,
            'data' => InvoiceResource::collection($invoices),
        ]);
    }
}
