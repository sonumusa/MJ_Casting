<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\GoldReceipt;
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

        $fromDate = $request->get('from_date', now()->subMonths(6)->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));

        $givenWeight = Invoice::where('status', 'active')
            ->when($fromDate, fn($q) => $q->where('invoice_date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->where('invoice_date', '<=', $toDate))
            ->sum('effective_gold');

        $receiptKhalis = GoldReceipt::when($fromDate, fn($q) => $q->where('receipt_date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->where('receipt_date', '<=', $toDate))
            ->sum('total_khalis_weight');

        $invoiceReceivedKhalis = Invoice::where('status', 'active')
            ->when($fromDate, fn($q) => $q->where('invoice_date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->where('invoice_date', '<=', $toDate))
            ->sum('total_received_khalis');

        $totalReceived = $receiptKhalis + $invoiceReceivedKhalis;

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
            'fromDate',
            'toDate',
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

        $inventory->received = GoldReceipt::sum('total_khalis_weight') 
            + Invoice::where('status', 'active')->sum('total_received_khalis');
        $inventory->given_invoices = Invoice::where('status', 'active')->sum('effective_gold');
        $inventory->closing_balance = $inventory->calculateClosingBalance();
        
        $inventory->save();

        return redirect()->route('inventory.index')
            ->with('success', 'Inventory refreshed successfully. Opening stock and all receipts are now properly reflected.');
    }
}
