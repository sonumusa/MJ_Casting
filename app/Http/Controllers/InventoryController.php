<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\GoldReceipt;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $inventory = Inventory::first();
        if (! $inventory) {
            $inventory = new Inventory([
                'opening_balance' => 0,
                'received' => 0,
                'given_invoices' => 0,
                'closing_balance' => 0,
                'period_label' => null,
            ]);
        }

        $givenWeight = Invoice::where('status', 'active')->sum('effective_gold');
        $receivedWeight = GoldReceipt::sum('total_khalis_weight')
            + Invoice::where('status', 'active')->sum('total_received_khalis');

        $inventory->received = $receivedWeight;
        $inventory->given_invoices = $givenWeight;
        $closingBalance = $inventory->calculateClosingBalance();

        return view('pages.inventory.index', compact('inventory', 'givenWeight', 'receivedWeight', 'closingBalance'));
    }

    public function update(Request $request): RedirectResponse
    {
        $inventory = Inventory::first();
        if (! $inventory) {
            $inventory = new Inventory();
        }

        $validated = $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'period_label' => 'nullable|string|max:100',
        ]);

        $inventory->fill($validated);

        $inventory->received = GoldReceipt::sum('total_khalis_weight')
            + Invoice::where('status', 'active')->sum('total_received_khalis');
        $inventory->given_invoices = Invoice::where('status', 'active')->sum('effective_gold');
        $inventory->closing_balance = $inventory->calculateClosingBalance();
        $inventory->updated_by = auth()->id();
        $inventory->save();

        return redirect()->route('inventory.index')->with('success', 'Inventory updated successfully.');
    }
}
