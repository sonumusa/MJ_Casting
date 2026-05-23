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

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $inventory = Inventory::firstOrNew([]);
        
        if (!$inventory->exists) {
            $inventory->opening_balance = 0;
            $inventory->period_label = 'Current Stock';
        }

        // Get current inventory totals from transactions
        // These are now automatically maintained by observers
        $givenWeight = Invoice::where('status', 'active')->sum('effective_gold');
        
        $receiptKhalis = GoldReceipt::sum('total_khalis_weight');
        
        $invoiceReceivedKhalis = InvoiceReceive::sum('khalis_weight');
        
        $totalReceived = $receiptKhalis + $invoiceReceivedKhalis;

        // Ensure inventory record exists with correct values
        $inventory->received = $totalReceived;
        $inventory->given_invoices = $givenWeight;
        $closingBalance = $inventory->calculateClosingBalance();

        $recentReceipts = GoldReceipt::with('customer')
            ->latest('receipt_date')
            ->take(10)
            ->get();

        $recentInvoices = Invoice::with('customer')
            ->where('status', 'active')
            ->latest('invoice_date')
            ->take(10)
            ->get();

        $recentInvoiceReceives = InvoiceReceive::with('invoice.customer')
            ->latest('created_at')
            ->take(10)
            ->get();

        $customers = Customer::orderBy('name')->get();

        return view('pages.inventory.index', compact(
            'inventory', 
            'givenWeight', 
            'totalReceived',
            'receiptKhalis',
            'invoiceReceivedKhalis',
            'closingBalance',
            'recentReceipts',
            'recentInvoices',
            'recentInvoiceReceives',
            'customers'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $inventory = Inventory::firstOrNew([]);
        
        $validated = $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'period_label' => 'nullable|string|max:100',
        ]);

        $inventory->fill($validated);
        $inventory->updated_by = auth()->id();

        // Recalculate from current transaction totals
        $inventory->received = GoldReceipt::sum('total_khalis_weight') 
            + InvoiceReceive::sum('khalis_weight');
        $inventory->given_invoices = Invoice::where('status', 'active')->sum('effective_gold');
        $inventory->closing_balance = $inventory->calculateClosingBalance();
        
        $inventory->save();

        return redirect()->route('inventory.index')
            ->with('success', 'Inventory updated successfully. Opening balance set and closing stock recalculated from all transactions.');
    }
}
