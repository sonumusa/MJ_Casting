<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\LedgerService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private LedgerService $ledgerService
    ) {}

    public function index(): View
    {
        $stats = $this->ledgerService->getDashboardStats();
        $recentInvoices = Invoice::with('customer')
            ->where('status', 'active')
            ->latest('invoice_date')
            ->latest('id')
            ->limit(10)
            ->get();

        $topCustomers = Customer::active()
            ->withCount(['invoices'])
            ->get()
            ->sortByDesc(fn($c) => $c->getCurrentBalance())
            ->take(5);

        return view('pages.dashboard', [
            'stats' => $stats,
            'recentInvoices' => $recentInvoices,
            'topCustomers' => $topCustomers,
        ]);
    }
}
