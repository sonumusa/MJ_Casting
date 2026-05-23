<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\LedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class ReportController extends Controller
{
    public function __construct(
        private LedgerService $ledgerService
    ) {}

    public function daily(Request $request): View
    {
        $date = Carbon::parse($request->input('date', now()->toDateString()));
        $report = $this->ledgerService->getDailyReport($date);
        $customers = Customer::active()->orderBy('name')->get();

        return view('pages.reports.daily', compact('report', 'customers', 'date'));
    }

    public function customer(Request $request): View
    {
        $customers = Customer::active()->orderBy('name')->get();
        $report = null;
        $selectedCustomer = null;

        if ($request->filled('customer_id')) {
            $selectedCustomer = Customer::find($request->input('customer_id'));
            $from = Carbon::parse($request->input('from_date', now()->startOfMonth()->toDateString()));
            $to = Carbon::parse($request->input('to_date', now()->toDateString()));

            if ($selectedCustomer) {
                $report = $this->ledgerService->getCustomerReport($selectedCustomer, $from, $to);
            }
        }

        return view('pages.reports.customer', compact('customers', 'report', 'selectedCustomer'));
    }
}
