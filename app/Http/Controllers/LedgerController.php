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
        $dateRangeLabel = 'All Time';

        if ($request->filled('customer_id')) {
            $selectedCustomer = Customer::find($request->input('customer_id'));
            
            if ($selectedCustomer) {
                $from = $request->filled('from_date') 
                    ? Carbon::parse($request->input('from_date'))->startOfDay() 
                    : null;
                $to = $request->filled('to_date') 
                    ? Carbon::parse($request->input('to_date'))->endOfDay() 
                    : null;
                
                $dateRangeLabel = $this->formatDateRange($from, $to);
                $ledgerData = $this->ledgerService->getCustomerLedger($selectedCustomer, $from, $to);
            }
        }

        return view('pages.ledger.index', [
            'customers' => $customers,
            'selectedCustomer' => $selectedCustomer,
            'ledgerData' => $ledgerData,
            'dateRangeLabel' => $dateRangeLabel,
            'filters' => [
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
            ]
        ]);
    }

    private function formatDateRange(?Carbon $from, ?Carbon $to): string
    {
        if (!$from && !$to) return 'All Time';
        if ($from && $to) return $from->format('d M Y') . ' - ' . $to->format('d M Y');
        if ($from) return 'From ' . $from->format('d M Y');
        if ($to) return 'Until ' . $to->format('d M Y');
        return 'Custom Range';
    }
}