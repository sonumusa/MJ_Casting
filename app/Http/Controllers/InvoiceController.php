<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceReceive;
use App\Models\Setting;
use App\Services\GoldCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    private GoldCalculationService $calcService;

    public function __construct(GoldCalculationService $calcService)
    {
        $this->calcService = $calcService;
    }

    /* ═══════════════════════════════════════════════════════════
     * INDEX - List all invoices
     * ═══════════════════════════════════════════════════════════ */
    public function index(Request $request): View
    {
        $query = Invoice::with('customer')->where('status', 'active');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('manual_book_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('invoice_type')) {
            $query->where('invoice_type', $request->input('invoice_type'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('invoice_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('invoice_date', '<=', $request->input('to_date'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $invoices = $query->latest('invoice_date')
                          ->latest('id')
                          ->paginate(25)
                          ->withQueryString();
        
        $customers = Customer::orderBy('name')->get();

        return view('pages.invoices.index', compact('invoices', 'customers'));
    }

    /* ═══════════════════════════════════════════════════════════
     * CREATE - Show invoice creation form
     * ═══════════════════════════════════════════════════════════ */
    public function create(): View
    {
        $customers = Customer::orderBy('name')->get();
        $nextInvoiceNo = $this->generateInvoiceNo();

        return view('pages.invoices.create', compact('customers', 'nextInvoiceNo'));
    }

    /* ═══════════════════════════════════════════════════════════
     * STORE - Save new invoice
     * ═══════════════════════════════════════════════════════════ */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateInvoice($request);

        try {
            DB::beginTransaction();

            // Calculate total received khalis from dynamic receive rows
            $totalReceivedKhalis = 0;
            $receivesData = [];
            
            if (!empty($request->input('receives'))) {
                foreach ($request->input('receives') as $receive) {
                    $gross = (float) ($receive['gross_weight'] ?? 0);
                    $ratti = (float) ($receive['ratti_impurity'] ?? 0);
                    
                    if ($gross > 0) {
                        $khalis = $this->calcService->convertToKhalis($gross, $ratti);
                        $totalReceivedKhalis += $khalis;
                        
                        $receivesData[] = [
                            'description' => $receive['description'] ?? null,
                            'gross_weight' => $gross,
                            'ratti_impurity' => $ratti,
                            'khalis_weight' => $khalis,
                        ];
                    }
                }
            }
            
            $totalReceivedKhalis = round($totalReceivedKhalis, 3);
            $validated['total_received_khalis'] = $totalReceivedKhalis;

            // Get previous balance from customer's last invoice
            $previousBalance = $this->getPreviousBalance($validated['customer_id']);
            $validated['previous_balance'] = $previousBalance;

            // Server-side gold calculation for verification
            $calculations = $this->calcService->calculate($validated);

            // Prepare invoice data
            $invoiceData = [
                'invoice_no' => $this->generateInvoiceNo(),
                'invoice_type' => $validated['invoice_type'],
                'customer_id' => $validated['customer_id'],
                'invoice_date' => $validated['invoice_date'],
                'manual_book_no' => $validated['manual_book_no'] ?? null,
                
                // Gold calculation fields
                'casting_weight' => $calculations['casting_weight'],
                'ratti' => $calculations['ratti'],
                'ratti_rate' => $calculations['ratti_rate'],
                'waste_weight' => $calculations['waste_weight'],
                'total_weight' => $calculations['total_weight'],
                'male_waste' => $calculations['male_waste'],
                'gold_khalis' => $calculations['gold_khalis'],
                
                // Mazdori fields
                'rp_mazdori_weight' => $calculations['rp_mazdori_weight'],
                'rp_mazdori_rate' => $calculations['rp_mazdori_rate'],
                'rp_mazdori_amount' => $calculations['rp_mazdori_amount'],
                'casting_mazdori_weight' => $calculations['casting_mazdori_weight'],
                'casting_mazdori_rate' => $calculations['casting_mazdori_rate'],
                'casting_mazdori_amount' => $calculations['casting_mazdori_amount'],
                
                // Rate and amounts
                'rp_rate' => $calculations['rp_rate'],
                'rp_amount' => $calculations['rp_amount'],
                'effective_gold' => $calculations['effective_gold'],
                'grand_total' => $calculations['grand_total'],
                
                // Balance fields
                'total_received_khalis' => $totalReceivedKhalis,
                'wasooli' => $calculations['wasooli'],
                'previous_balance' => $previousBalance,
                'remaining_balance' => $calculations['remaining_balance'],
                
                // Meta fields
                'remarks' => $validated['remarks'] ?? null,
                'status' => 'active',
                'created_by' => Auth::id(),
            ];

            // Create invoice
            $invoice = Invoice::create($invoiceData);

            // Create receive rows
            foreach ($receivesData as $rec) {
                $invoice->receives()->create($rec);
            }

            // Recalculate balance chain for customer
            $customer = Customer::find($validated['customer_id']);
            $this->calcService->recalculateChain($customer);

            DB::commit();

            // Determine redirect based on action
            if ($request->input('action') === 'print') {
                return redirect()
                    ->route('invoices.print', $invoice)
                    ->with('success', "Invoice {$invoice->invoice_no} created successfully.");
            }

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', "Invoice {$invoice->invoice_no} created successfully.");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Invoice store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to save invoice: ' . $e->getMessage());
        }
    }

    /* ═══════════════════════════════════════════════════════════
     * SHOW - Display invoice details
     * ═══════════════════════════════════════════════════════════ */
    public function show(Invoice $invoice): View
    {
        $invoice->load('customer', 'receives');
        $breakdown = $this->buildCalculationBreakdown($invoice);

        return view('pages.invoices.show', compact('invoice', 'breakdown'));
    }

    /* ═══════════════════════════════════════════════════════════
     * EDIT - Show invoice edit form
     * ═══════════════════════════════════════════════════════════ */
    public function edit(Invoice $invoice): View
    {
        $invoice->load('customer', 'receives');
        $customers = Customer::orderBy('name')->get();
        $breakdown = $this->buildCalculationBreakdown($invoice);

        return view('pages.invoices.edit', compact('invoice', 'customers', 'breakdown'));
    }

    /* ═══════════════════════════════════════════════════════════
     * UPDATE - Update existing invoice
     * ═══════════════════════════════════════════════════════════ */
    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $this->validateInvoice($request, $invoice);

        try {
            DB::beginTransaction();

            // Calculate total received khalis from dynamic receive rows
            $totalReceivedKhalis = 0;
            $receivesData = [];
            
            if (!empty($request->input('receives'))) {
                foreach ($request->input('receives') as $receive) {
                    $gross = (float) ($receive['gross_weight'] ?? 0);
                    $ratti = (float) ($receive['ratti_impurity'] ?? 0);
                    
                    if ($gross > 0) {
                        $khalis = $this->calcService->convertToKhalis($gross, $ratti);
                        $totalReceivedKhalis += $khalis;
                        
                        $receivesData[] = [
                            'description' => $receive['description'] ?? null,
                            'gross_weight' => $gross,
                            'ratti_impurity' => $ratti,
                            'khalis_weight' => $khalis,
                        ];
                    }
                }
            }
            
            $totalReceivedKhalis = round($totalReceivedKhalis, 3);
            $validated['total_received_khalis'] = $totalReceivedKhalis;

            // Get previous balance (from last invoice before this one if customer changed)
            $previousBalance = $this->getPreviousBalance(
                $validated['customer_id'], 
                $invoice->id
            );
            $validated['previous_balance'] = $previousBalance;

            // Server-side gold calculation
            $calculations = $this->calcService->calculate($validated);

            // Update invoice
            $invoice->update([
                'invoice_type' => $validated['invoice_type'],
                'customer_id' => $validated['customer_id'],
                'invoice_date' => $validated['invoice_date'],
                'manual_book_no' => $validated['manual_book_no'] ?? null,
                
                // Gold calculation fields
                'casting_weight' => $calculations['casting_weight'],
                'ratti' => $calculations['ratti'],
                'ratti_rate' => $calculations['ratti_rate'],
                'waste_weight' => $calculations['waste_weight'],
                'total_weight' => $calculations['total_weight'],
                'male_waste' => $calculations['male_waste'],
                'gold_khalis' => $calculations['gold_khalis'],
                
                // Mazdori fields
                'rp_mazdori_weight' => $calculations['rp_mazdori_weight'],
                'rp_mazdori_rate' => $calculations['rp_mazdori_rate'],
                'rp_mazdori_amount' => $calculations['rp_mazdori_amount'],
                'casting_mazdori_weight' => $calculations['casting_mazdori_weight'],
                'casting_mazdori_rate' => $calculations['casting_mazdori_rate'],
                'casting_mazdori_amount' => $calculations['casting_mazdori_amount'],
                
                // Rate and amounts
                'rp_rate' => $calculations['rp_rate'],
                'rp_amount' => $calculations['rp_amount'],
                'effective_gold' => $calculations['effective_gold'],
                'grand_total' => $calculations['grand_total'],
                
                // Balance fields
                'total_received_khalis' => $totalReceivedKhalis,
                'wasooli' => $calculations['wasooli'],
                'previous_balance' => $previousBalance,
                'remaining_balance' => $calculations['remaining_balance'],
                
                // Meta fields
                'remarks' => $validated['remarks'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            // Sync receive rows: delete old, create new
            $invoice->receives()->delete();
            foreach ($receivesData as $rec) {
                $invoice->receives()->create($rec);
            }

            // Recalculate balance chain
            $customer = Customer::find($validated['customer_id']);
            $this->calcService->recalculateChain($customer);

            DB::commit();

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', "Invoice {$invoice->invoice_no} updated successfully.");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Invoice update failed', [
                'id' => $invoice->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update invoice: ' . $e->getMessage());
        }
    }

    /* ═══════════════════════════════════════════════════════════
     * DESTROY - Soft delete invoice
     * ═══════════════════════════════════════════════════════════ */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $customerId = $invoice->customer_id;
            $invoiceNo = $invoice->invoice_no;

            // Delete receive rows
            $invoice->receives()->delete();
            
            // Soft delete invoice
            $invoice->update(['status' => 'cancelled']);
            $invoice->delete();

            // Recalculate balance chain
            $customer = Customer::find($customerId);
            if ($customer) {
                $this->calcService->recalculateChain($customer);
            }

            DB::commit();

            return redirect()
                ->route('invoices.index')
                ->with('success', "Invoice {$invoiceNo} deleted successfully.");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Invoice delete failed', [
                'id' => $invoice->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to delete invoice: ' . $e->getMessage());
        }
    }

    /* ═══════════════════════════════════════════════════════════
     * PRINT - Generate printable invoice
     * ═══════════════════════════════════════════════════════════ */
    public function print(Request $request, Invoice $invoice): View
    {
        $invoice->load('customer', 'receives');

        $format = $request->query('format', 'slip');
        $breakdown = $this->buildCalculationBreakdown($invoice);

        $workshopSettings = [
            'name' => Setting::getSetting('workshop_name', 'M.J Casting'),
            'name_urdu' => Setting::getSetting('workshop_name_urdu', 'ایم جے کاسٹنگ'),
            'address' => Setting::getSetting('address', ''),
            'phone' => Setting::getSetting('phone', ''),
            'phone2' => Setting::getSetting('phone2', ''),
            'phone3' => Setting::getSetting('phone3', ''),
            'city' => Setting::getSetting('city', ''),
            'messenger' => Setting::getSetting('messenger', ''),
            'social' => Setting::getSetting('social', ''),
        ];

        return view('pages.invoices.print', compact('invoice', 'format', 'breakdown', 'workshopSettings'));
    }

    /* ═══════════════════════════════════════════════════════════
     * EXPORT - Export invoices to CSV
     * ═══════════════════════════════════════════════════════════ */
    public function export(Request $request): Response
    {
        $query = Invoice::with('customer')->where('status', 'active');

        if ($request->filled('from_date')) {
            $query->whereDate('invoice_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('invoice_date', '<=', $request->input('to_date'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        $invoices = $query->latest('invoice_date')->latest('id')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="invoices_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($invoices) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Invoice No', 'Book No', 'Date', 'Type', 'Customer',
                'Casting (g)', 'Ratti', 'Ratti Rate', 'Waste (g)',
                'Total Weight (g)', 'Male Waste (g)', 'Gold Khalis (g)',
                'RP Rate', 'RP Amount', 'RP Mazdori Wt (g)', 'RP Mazdori Amt',
                'Casting Mazdori Wt (g)', 'Casting Mazdori Amt',
                'Effective Gold (g)', 'Grand Total (g)', 'Received Khalis (g)',
                'Wasooli (g)', 'Previous Balance (g)', 'Remaining Balance (g)',
                'Remarks', 'Status',
            ]);

            foreach ($invoices as $inv) {
                fputcsv($file, [
                    $inv->invoice_no,
                    $inv->manual_book_no,
                    $inv->invoice_date->format('Y-m-d'),
                    $inv->invoice_type,
                    $inv->customer?->name,
                    number_format($inv->casting_weight, 3, '.', ''),
                    number_format($inv->ratti, 2, '.', ''),
                    number_format($inv->ratti_rate, 3, '.', ''),
                    number_format($inv->waste_weight, 3, '.', ''),
                    number_format($inv->total_weight, 3, '.', ''),
                    number_format($inv->male_waste, 3, '.', ''),
                    number_format($inv->gold_khalis, 3, '.', ''),
                    number_format($inv->rp_rate, 2, '.', ''),
                    number_format($inv->rp_amount, 2, '.', ''),
                    number_format($inv->rp_mazdori_weight, 3, '.', ''),
                    number_format($inv->rp_mazdori_amount, 2, '.', ''),
                    number_format($inv->casting_mazdori_weight, 3, '.', ''),
                    number_format($inv->casting_mazdori_amount, 2, '.', ''),
                    number_format($inv->effective_gold, 3, '.', ''),
                    number_format($inv->grand_total, 3, '.', ''),
                    number_format($inv->total_received_khalis, 3, '.', ''),
                    number_format($inv->wasooli, 3, '.', ''),
                    number_format($inv->previous_balance, 3, '.', ''),
                    number_format($inv->remaining_balance, 3, '.', ''),
                    $inv->remarks,
                    $inv->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /* ═══════════════════════════════════════════════════════════
     * API: Get Customer Last Balance (for AJAX)
     * ═══════════════════════════════════════════════════════════ */
    public function customerLastBalance($customerId): Response
    {
        $customer = Customer::find($customerId);
        
        if (!$customer) {
            return response()->json(['balance' => 0], 404);
        }

        $lastInvoice = $customer->invoices()
            ->where('status', 'active')
            ->latest('invoice_date')
            ->latest('id')
            ->first();

        $balance = $lastInvoice?->remaining_balance ?? $customer->opening_balance;

        return response()->json(['balance' => $balance]);
    }

    /* ═══════════════════════════════════════════════════════════
     * HELPER METHODS
     * ═══════════════════════════════════════════════════════════ */

    /**
     * Get previous balance for a customer
     */
    private function getPreviousBalance(int $customerId, ?int $excludeInvoiceId = null): float
    {
        $customer = Customer::find($customerId);
        
        if (!$customer) {
            return 0;
        }

        $query = $customer->invoices()
            ->where('status', 'active');

        if ($excludeInvoiceId) {
            $query->where('id', '!=', $excludeInvoiceId);
        }

        $lastInvoice = $query->latest('invoice_date')
                            ->latest('id')
                            ->first();

        return $lastInvoice?->remaining_balance ?? $customer->opening_balance;
    }

    /**
     * Validate invoice input
     */
    private function validateInvoice(Request $request, ?Invoice $existing = null): array
    {
        $rules = [
            'customer_id' => 'required|exists:customers,id',
            'invoice_type' => 'required|in:customer,dukandar,karigar',
            'invoice_date' => 'required|date',
            'manual_book_no' => 'nullable|string|max:50',
            
            // Gold calculation inputs
            'casting_weight' => 'required|numeric|min:0',
            'ratti' => 'required|numeric|min:0',
            'ratti_rate' => 'required|numeric|min:0',
            
            // Mazdori inputs
            'rp_mazdori_weight' => 'nullable|numeric|min:0',
            'rp_mazdori_rate' => 'nullable|numeric|min:0',
            'casting_mazdori_weight' => 'nullable|numeric|min:0',
            'casting_mazdori_rate' => 'nullable|numeric|min:0',
            
            // Rate and balance
            'rp_rate' => 'required|numeric|min:0',
            'wasooli' => 'nullable|numeric|min:0',
            
            // Receive rows (optional)
            'receives' => 'nullable|array',
            'receives.*.description' => 'nullable|string|max:255',
            'receives.*.gross_weight' => 'required_with:receives|numeric|min:0',
            'receives.*.ratti_impurity' => 'required_with:receives|numeric|min:0',
            'receives.*.khalis_weight' => 'nullable|numeric|min:0',
            
            // Meta
            'remarks' => 'nullable|string|max:1000',
        ];

        return $request->validate($rules);
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNo(): string
    {
        $prefix = 'INV';
        $lastInvoice = Invoice::withTrashed()->latest('id')->first();
        $number = ($lastInvoice?->id ?? 0) + 1;
        
        return $prefix . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Build calculation breakdown for display
     */
    private function buildCalculationBreakdown(Invoice $invoice): array
    {
        return [
            'steps' => [
                ['label' => 'Casting Weight', 'value' => $invoice->casting_weight, 'unit' => 'g', 'formula' => 'Input'],
                ['label' => 'Ratti', 'value' => $invoice->ratti, 'unit' => '', 'formula' => 'Input'],
                ['label' => 'Ratti Rate', 'value' => $invoice->ratti_rate, 'unit' => 'g', 'formula' => 'From System Setting'],
                ['label' => 'Waste Weight', 'value' => $invoice->waste_weight, 'unit' => 'g', 'formula' => 'Casting ÷ 10 × Ratti Rate'],
                ['label' => 'Total Weight', 'value' => $invoice->total_weight, 'unit' => 'g', 'formula' => 'Casting + Waste'],
                ['label' => 'Male Waste', 'value' => $invoice->male_waste, 'unit' => 'g', 'formula' => 'Total Weight ÷ 96 × Ratti'],
                ['label' => 'Gold Khalis', 'value' => $invoice->gold_khalis, 'unit' => 'g', 'formula' => 'Total Weight - Male Waste'],
                ['label' => 'RP Mazdori Weight', 'value' => $invoice->rp_mazdori_weight, 'unit' => 'g', 'formula' => 'Input'],
                ['label' => 'Casting Mazdori Weight', 'value' => $invoice->casting_mazdori_weight, 'unit' => 'g', 'formula' => 'Input'],
                ['label' => 'Effective Gold', 'value' => $invoice->effective_gold, 'unit' => 'g', 'formula' => 'Gold Khalis + RP Mazdori + Casting Mazdori'],
                ['label' => 'Grand Total', 'value' => $invoice->grand_total, 'unit' => 'g', 'formula' => 'Effective Gold'],
                ['label' => 'Total Received Khalis', 'value' => $invoice->total_received_khalis, 'unit' => 'g', 'formula' => 'Sum of Receive Rows'],
            ],
            'balance_chain' => [
                'previous_balance' => $invoice->previous_balance,
                'effective_gold' => $invoice->effective_gold,
                            'grand_total' => $invoice->grand_total,
                'wasooli' => $invoice->wasooli,
                'received_khalis' => $invoice->total_received_khalis,
                'remaining_balance' => $invoice->remaining_balance,
                'formula' => 'Previous + Effective - Wasooli - Received',
            ],
        ];
    }
}