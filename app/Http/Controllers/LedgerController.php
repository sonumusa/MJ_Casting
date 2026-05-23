<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

use Carbon\Carbon;

class LedgerController extends Controller
{
    public function __construct(
        private LedgerService $ledgerService
    ) {}

    public function index(Request $request): View
    {
        $customers = Customer::active()->orderBy('name')->get();
        $selectedCustomer = null;
        $ledgerData = null;

        if ($request->filled('customer_id')) {
            $selectedCustomer = Customer::find($request->input('customer_id'));
            if ($selectedCustomer) {
                $from = $request->filled('from_date') ? Carbon::parse($request->input('from_date')) : null;
                $to = $request->filled('to_date') ? Carbon::parse($request->input('to_date')) : null;
                $ledgerData = $this->ledgerService->getCustomerLedger($selectedCustomer, $from, $to);
            }
        }

        return view('pages.ledger.index', [
            'customers' => $customers,
            'selectedCustomer' => $selectedCustomer,
            'ledgerData' => $ledgerData,
        ]);
    }
}
