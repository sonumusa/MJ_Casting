<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\GoldReceipt;
use App\Models\InvoiceReceive;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    /**
     * Display the inventory/stock report page
     * 
     * Inventory Formula:
     * Closing = Opening 
     *         + GoldReceipts (total_khalis_weight) 
     *         + InvoiceReceives (khalis_weight) 
     *         + Invoice Internal Received (total_received_khalis) 
     *         - Invoice Effective Gold (effective_gold)
     */
    public function index(Request $request): View
    {
        // ✅ Get date filters from request (fixes undefined variable error)
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        
        $inventory = Inventory::firstOrNew([]);
        
        if (!$inventory->exists) {
            $inventory->opening_balance = 0;
            $inventory->period_label = 'Current Stock';
        }

        // ═══════════════════════════════════════════════════════
        // 🔥 CORRECTED INVENTORY CALCULATION LOGIC
        // ═══════════════════════════════════════════════════════
        
        // 1️⃣ STOCK IN: Direct Gold Receipts from customers
        $receiptKhalis = GoldReceipt::sum('total_khalis_weight');
        
        // 2️⃣ STOCK IN: Gold received via InvoiceReceive model (separate receive records)
        $invoiceReceivedKhalis = InvoiceReceive::sum('khalis_weight');
        
        // 3️⃣ STOCK IN: Gold received WITH invoices (total_received_khalis field) ← 🔥 KEY FIX
        $invoiceInternalReceived = Invoice::where('status', 'active')->sum('total_received_khalis');
        
        // ✅ Total Stock IN
        $totalReceived = $receiptKhalis + $invoiceReceivedKhalis + $invoiceInternalReceived;

        // 4️⃣ STOCK OUT: Effective gold given in active invoices
        $givenWeight = Invoice::where('status', 'active')->sum('effective_gold');

        // 5️⃣ Update inventory record with calculated values
        $inventory->received = $totalReceived;
        $inventory->given_invoices = $givenWeight;
        $closingBalance = $inventory->calculateClosingBalance();

        // ═══════════════════════════════════════════════════════
        // 📋 FETCH RECENT TRANSACTIONS (with optional date filtering)
        // ═══════════════════════════════════════════════════════
        
        // Recent Gold Receipts (+ Stock)
        $receiptsQuery = GoldReceipt::with('customer');
        if ($fromDate) {
            $receiptsQuery->whereDate('receipt_date', '>=', $fromDate);
        }
        if ($toDate) {
            $receiptsQuery->whereDate('receipt_date', '<=', $toDate);
        }
        $recentReceipts = $receiptsQuery->latest('receipt_date')->take(10)->get();

        // Recent Invoices (- Stock: effective_gold, + Stock: total_received_khalis)
        $invoicesQuery = Invoice::with('customer')->where('status', 'active');
        if ($fromDate) {
            $invoicesQuery->whereDate('invoice_date', '>=', $fromDate);
        }
        if ($toDate) {
            $invoicesQuery->whereDate('invoice_date', '<=', $toDate);
        }
        $recentInvoices = $invoicesQuery->latest('invoice_date')->take(10)->get();

        // Recent Invoice Receives (+ Stock)
        $receivesQuery = InvoiceReceive::with('invoice.customer');
        if ($fromDate) {
            $receivesQuery->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $receivesQuery->whereDate('created_at', '<=', $toDate);
        }
        $recentInvoiceReceives = $receivesQuery->latest('created_at')->take(10)->get();

        $customers = Customer::orderBy('name')->get();

        // ✅ Pass ALL required variables to view (including date filters)
        return view('pages.inventory.index', compact(
            'inventory', 
            'givenWeight', 
            'totalReceived',
            'receiptKhalis',
            'invoiceReceivedKhalis',
            'invoiceInternalReceived',
            'closingBalance',
            'recentReceipts',
            'recentInvoices',
            'recentInvoiceReceives',
            'customers',
            'fromDate',
            'toDate'
        ));
    }

    /**
     * Update the opening stock and recalculate closing balance
     */
    public function update(Request $request): RedirectResponse
    {
        $inventory = Inventory::firstOrNew([]);
        
        $validated = $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'period_label' => 'nullable|string|max:100',
        ]);

        $inventory->fill($validated);
        $inventory->updated_by = Auth::id();

        // ═══════════════════════════════════════════════════════
        // 🔥 RECALCULATE USING CORRECTED FORMULA
        // ═══════════════════════════════════════════════════════
        
        $inventory->received = 
            GoldReceipt::sum('total_khalis_weight') 
            + InvoiceReceive::sum('khalis_weight')
            + Invoice::where('status', 'active')->sum('total_received_khalis');
            
        $inventory->given_invoices = Invoice::where('status', 'active')->sum('effective_gold');
        
        $inventory->closing_balance = $inventory->calculateClosingBalance();
        
        $inventory->save();

        return redirect()->route('inventory.index')
            ->with('success', 'Inventory updated successfully. Closing stock recalculated: Opening + Receipts + Invoice Receives + Invoice Internal Received - Invoice Given.');
    }
}