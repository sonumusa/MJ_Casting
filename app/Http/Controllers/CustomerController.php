<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->searchable($search);
        }

        if ($request->filled('party_type') && in_array($request->party_type, ['customer', 'dukandar', 'karigar'])) {
            $query->ofType($request->party_type);
        }

        $customers = $query->latest('name')
            ->paginate(20)
            ->withQueryString();

        return view('pages.customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('pages.customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'cnic' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric|min:0',
            'party_type' => 'required|in:customer,dukandar,karigar',
        ]);

        Customer::create(array_merge($validated, ['status' => 'active']));

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer): View
    {
        $customer->load([
            'invoices' => function ($query) {
                $query->where('status', 'active')->latest('invoice_date')->latest('id');
            },
            'goldReceipts' => function ($query) {
                $query->latest('receipt_date')->latest('id');
            },
        ]);

        return view('pages.customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        return view('pages.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'cnic' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'party_type' => 'required|in:customer,dukandar,karigar',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated successfully.');
    }

    public function lastBalance(Customer $customer)
    {
        // Get all active invoices up to today
        $invoices = $customer->invoices()
            ->where('status', 'active')
            ->whereDate('invoice_date', '<=', now()->toDateString())
            ->latest('invoice_date')
            ->latest('id')
            ->get();

        // Get all receipts up to today
        $receipts = $customer->goldReceipts()
            ->whereDate('receipt_date', '<=', now()->toDateString())
            ->latest('receipt_date')
            ->latest('id')
            ->get();

        // Calculate running balance as of today
        $balance = $customer->opening_balance ?? 0;
        
        // Add invoices
        if ($invoices->isNotEmpty()) {
            $balance = $invoices->first()->remaining_balance ?? $balance;
        }
        
        // Subtract gold receipts from today's perspective
        $totalReceipts = $receipts->sum('total_khalis_weight');
        $balance -= $totalReceipts;

        return response()->json([
            'balance' => round($balance, 3),
            'customer_name' => $customer->name,
        ]);
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->invoices()->where('status', 'active')->exists()) {
            return redirect()->route('customers.index')
                ->with('error', 'Cannot delete customer with active invoices.');
        }

        if ($customer->goldReceipts()->exists()) {
            return redirect()->route('customers.index')
                ->with('error', 'Cannot delete customer with gold receipts.');
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer removed successfully.');
    }

    public function ledger(Customer $customer): View
    {
        $customer->load([
            'invoices' => function ($query) {
                $query->where('status', 'active')->orderBy('invoice_date')->orderBy('id');
            },
            'goldReceipts' => function ($query) {
                $query->orderBy('receipt_date')->orderBy('id');
            },
        ]);

        return view('pages.customers.show', [
            'customer' => $customer,
            'showLedger' => true,
        ]);
    }
}
