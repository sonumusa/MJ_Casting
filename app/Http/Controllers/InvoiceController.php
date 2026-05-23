<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceReceive;
use App\Models\Setting;
use App\Services\GoldCalculationService;
use App\Services\InvoiceService;
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
    private InvoiceService $invoiceService;

    public function __construct(GoldCalculationService $calcService, InvoiceService $invoiceService)
    {
        $this->calcService = $calcService;
        $this->invoiceService = $invoiceService;
    }

    /* ─────────────────────────────────────────────────────────
     | INDEX
     ───────────────────────────────────────────────────────── */
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
        } else {
            $query->where('status', 'active');
        }

        $invoices = $query->latest('invoice_date')->latest('id')->paginate(25)->withQueryString();
        $customers = Customer::orderBy('name')->get();

        return view('pages.invoices.index', compact('invoices', 'customers'));
    }

    /* ─────────────────────────────────────────────────────────
     | CREATE
     ───────────────────────────────────────────────────────── */
    public function create(): View
    {
        $customers = Customer::orderBy('name')->get();
        $calcSettings = $this->getCalcSettings();
        $nextInvoiceNo = $this->generateInvoiceNo();

        return view('pages.invoices.create', compact('customers', 'calcSettings', 'nextInvoiceNo'));
    }

    /* ─────────────────────────────────────────────────────────
     | STORE
     ───────────────────────────────────────────────────────── */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateInvoice($request);

        try {
            DB::beginTransaction();

            // Calculate total received khalis from dynamic rows
            $totalReceivedKhalis = 0;
            $receivesData = [];
            if (!empty($request->input('receives'))) {
                foreach ($request->input('receives') as $receive) {
                    $gross = (float) ($receive['gross_weight'] ?? 0);
                    $ratti = (float) ($receive['ratti_impurity'] ?? 0);
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
            $validated['total_received_khalis'] = round($totalReceivedKhalis, 3);

            // Server-side gold calculation
            $calculations = $this->calcService->calculate($validated);

            $invoiceData = array_merge($validated, $calculations, [
                'invoice_no' => $this->generateInvoiceNo(),
                'created_by' => Auth::id(),
                'status' => 'active',
            ]);

            $invoice = Invoice::create($invoiceData);

            // Create receive rows
            foreach ($receivesData as $rec) {
                $rec['invoice_id'] = $invoice->id;
                InvoiceReceive::create($rec);
            }

            DB::commit();

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', "Invoice {$invoice->invoice_no} created successfully.")
                ->with('print_invoice_id', $invoice->id);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Invoice store failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return back()
                ->withInput()
                ->with('error', 'Failed to save invoice. ' . $e->getMessage());
        }
    }

    /* ─────────────────────────────────────────────────────────
     | SHOW
     ───────────────────────────────────────────────────────── */
    public function show(Invoice $invoice): View
    {
        $invoice->load('customer', 'receives');
        $breakdown = $this->buildCalculationBreakdown($invoice);

        return view('pages.invoices.show', compact('invoice', 'breakdown'));
    }

    /* ─────────────────────────────────────────────────────────
     | EDIT
     ───────────────────────────────────────────────────────── */
    public function edit(Invoice $invoice): View
    {
        $invoice->load('customer', 'receives');
        $customers = Customer::orderBy('name')->get();
        $calcSettings = $this->getCalcSettings();
        $breakdown = $this->buildCalculationBreakdown($invoice);

        return view('pages.invoices.edit', compact('invoice', 'customers', 'calcSettings', 'breakdown'));
    }

    /* ─────────────────────────────────────────────────────────
     | UPDATE
     ───────────────────────────────────────────────────────── */
    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $this->validateInvoice($request, $invoice);

        try {
            DB::beginTransaction();

            // Calculate total received khalis from dynamic rows
            $totalReceivedKhalis = 0;
            $receivesData = [];
            if (!empty($request->input('receives'))) {
                foreach ($request->input('receives') as $receive) {
                    $gross = (float) ($receive['gross_weight'] ?? 0);
                    $ratti = (float) ($receive['ratti_impurity'] ?? 0);
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
            $validated['total_received_khalis'] = round($totalReceivedKhalis, 3);

            $calculations = $this->calcService->calculate($validated);

            $invoiceData = array_merge($validated, $calculations);

            $invoice->update($invoiceData);

            // Sync receive rows: delete old, recreate
            $invoice->receives()->delete();
            foreach ($receivesData as $rec) {
                $rec['invoice_id'] = $invoice->id;
                InvoiceReceive::create($rec);
            }

            $this->recalculateBalanceChain($invoice->customer_id, $invoice->id);

            DB::commit();

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', "Invoice {$invoice->invoice_no} updated successfully.")
                ->with('print_invoice_id', $invoice->id);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Invoice update failed', ['id' => $invoice->id, 'error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update invoice. ' . $e->getMessage());
        }
    }

    /* ─────────────────────────────────────────────────────────
     | DESTROY (soft delete)
     ───────────────────────────────────────────────────────── */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $customerId = $invoice->customer_id;
            $invoiceId = $invoice->id;
            $invoiceNo = $invoice->invoice_no;

            $invoice->receives()->delete();
            $invoice->update(['status' => 'cancelled']);
            $invoice->delete(); // soft delete

            $this->recalculateBalanceChain($customerId, $invoiceId);

            DB::commit();

            return redirect()
                ->route('invoices.index')
                ->with('success', "Invoice {$invoiceNo} deleted successfully.");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Invoice delete failed', ['id' => $invoice->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Failed to delete invoice. ' . $e->getMessage());
        }
    }

    /* ─────────────────────────────────────────────────────────
     | PRINT
     ───────────────────────────────────────────────────────── */
    public function print(Request $request, Invoice $invoice): View
    {
        $invoice->load('customer', 'receives');

        $format = $request->query('format', 'slip'); // slip | a5 | a4
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

    /* ─────────────────────────────────────────────────────────
     | EXPORT CSV
     ───────────────────────────────────────────────────────── */
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
                'Invoice No',
                'Book No',
                'Date',
                'Type',
                'Customer',
                'Casting (g)',
                'Waste (g)',
                'Total Weight (g)',
                'Ratti',
                'Ratti Rate',
                'Male Waste (g)',
                'Gold Khalis (g)',
                'Received Khalis (g)',
                'RP Rate',
                'RP Amount',
                'RP Mazdori Wt',
                'Casting Mazdori Wt',
                'Effective Gold',
                'Grand Total',
                'Wasooli',
                'Previous Balance',
                'Remaining Balance',
                'Remarks',
                'Status',
            ]);

            foreach ($invoices as $inv) {
                fputcsv($file, [
                    $inv->invoice_no,
                    $inv->manual_book_no,
                    $inv->invoice_date->format('Y-m-d'),
                    $inv->invoice_type,
                    $inv->customer?->name,
                    number_format($inv->casting_weight, 3),
                    number_format($inv->waste_weight, 3),
                    number_format($inv->total_weight, 3),
                    number_format($inv->ratti, 2),
                    number_format($inv->ratti_rate, 3),
                    number_format($inv->male_waste, 3),
                    number_format($inv->gold_khalis, 3),
                    number_format($inv->total_received_khalis, 3),
                    number_format($inv->rp_rate, 2),
                    number_format($inv->rp_amount, 2),
                    number_format($inv->rp_mazdori_weight, 3),
                    number_format($inv->casting_mazdori_weight, 3),
                    number_format($inv->effective_gold, 3),
                    number_format($inv->grand_total, 3),
                    number_format($inv->wasooli, 3),
                    number_format($inv->previous_balance, 3),
                    number_format($inv->remaining_balance, 3),
                    $inv->remarks,
                    $inv->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /* ─────────────────────────────────────────────────────────
     | HELPERS
     ───────────────────────────────────────────────────────── */

    private function validateInvoice(Request $request, ?Invoice $existing = null): array
    {
        $rules = [
            'customer_id' => 'required|exists:customers,id',
            'invoice_type' => 'required|in:customer,dukandar,karigar',
            'invoice_date' => 'required|date',
            'manual_book_no' => 'nullable|string|max:50',
            'casting_weight' => 'required|numeric|min:0',
            'waste_weight' => 'required|numeric|min:0',
            'ratti' => 'required|numeric|min:0',
            'ratti_rate' => 'required|numeric|min:0',
            'male_waste' => 'required|numeric|min:0',
            'gold_khalis' => 'required|numeric|min:0',
            'rp_rate' => 'required|numeric|min:0',
            'rp_mazdori_weight' => 'nullable|numeric|min:0',
            'rp_mazdori_rate' => 'nullable|numeric|min:0',
            'casting_mazdori_weight' => 'nullable|numeric|min:0',
            'casting_mazdori_rate' => 'nullable|numeric|min:0',
            'wasooli' => 'nullable|numeric|min:0',
            'previous_balance' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
        ];

        return $request->validate($rules);
    }

    private function getCalcSettings(): array
    {
        return [
            'default_waste_rate' => Setting::getSetting('default_waste_rate', 0),
            'default_ratti_rate' => Setting::getSetting('default_ratti_rate', 0),
            'ratti_tiers' => Setting::getSetting('ratti_tiers', []),
        ];
    }

    private function generateInvoiceNo(): string
    {
        $prefix = 'INV';
        $lastInvoice = Invoice::withTrashed()->latest('id')->first();
        $number = ($lastInvoice?->id ?? 0) + 1;
        return $prefix . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    private function buildCalculationBreakdown(Invoice $invoice): array
    {
        return [
            'steps' => [
                [
                    'label' => 'Casting Weight',
                    'value' => $invoice->casting_weight,
                    'formula' => 'Input',
                ],
                [
                    'label' => 'Waste Weight',
                    'value' => $invoice->waste_weight,
                    'formula' => $invoice->waste_auto ? 'Auto: Casting / 10 × Rate' : 'Manual',
                ],
                [
                    'label' => 'Total Weight',
                    'value' => $invoice->total_weight,
                    'formula' => 'Casting + Waste',
                ],
                [
                    'label' => 'Ratti Deduction',
                    'value' => $invoice->ratti,
                    'formula' => $invoice->ratti_auto ? 'Auto (Tiered)' : 'Manual',
                ],
                [
                    'label' => 'Male Waste',
                    'value' => $invoice->male_waste,
                    'formula' => 'Total / 96 × Ratti',
                ],
                [
                    'label' => 'Gold Khalis',
                    'value' => $invoice->gold_khalis,
                    'formula' => 'Total - Male Waste',
                ],
                [
                    'label' => 'Received Khalis',
                    'value' => $invoice->total_received_khalis,
                    'formula' => 'Sum of converted rows',
                ],
                [
                    'label' => 'RP Mazdori',
                    'value' => $invoice->rp_mazdori_weight,
                    'formula' => 'Input',
                ],
                [
                    'label' => 'Casting Mazdori',
                    'value' => $invoice->casting_mazdori_weight,
                    'formula' => 'Input',
                ],
                [
                    'label' => 'Effective Gold',
                    'value' => $invoice->effective_gold,
                    'formula' => 'Khalis + Mazdori',
                ],
                [
                    'label' => 'Grand Total',
                    'value' => $invoice->grand_total,
                    'formula' => 'Effective Gold',
                ],
            ],
            'balance_chain' => [
                'previous_balance' => $invoice->previous_balance,
                'grand_total' => $invoice->grand_total,
                'wasooli' => $invoice->wasooli,
                'received_khalis' => $invoice->total_received_khalis,
                'remaining_balance' => $invoice->remaining_balance,
            ],
        ];
    }

    private function recalculateBalanceChain(int $customerId, ?int $excludeInvoiceId = null): void
    {
        $customer = Customer::find($customerId);
        if (!$customer) return;

        $this->calcService->recalculateChain($customer, $excludeInvoiceId);
    }
}
