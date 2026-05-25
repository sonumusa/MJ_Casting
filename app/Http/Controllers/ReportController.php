<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\GoldReceipt;
use App\Services\LedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class ReportController extends Controller
{
    public function __construct(
        private LedgerService $ledgerService
    ) {}

    /**
     * Daily Report - Supports single date OR date range
     */
    public function daily(Request $request): View
    {
        $hasRange = $request->filled('from_date') && $request->filled('to_date');
        $hasSingle = $request->filled('date');
        
        if ($hasRange) {
            // ✅ Date Range Mode
            $from = Carbon::parse($request->input('from_date'))->startOfDay();
            $to = Carbon::parse($request->input('to_date'))->endOfDay();
            $report = $this->ledgerService->getDailyReportRange($from, $to);
            $dateLabel = $from->format('d M Y') . ' - ' . $to->format('d M Y');
            $dateForView = $from;
        } elseif ($hasSingle) {
            // ✅ Single Date Mode
            $date = Carbon::parse($request->input('date'))->startOfDay();
            $report = $this->ledgerService->getDailyReport($date);
            $dateLabel = $date->format('l, d F Y');
            $dateForView = $date;
        } else {
            // ✅ Default: Today
            $date = Carbon::today()->startOfDay();
            $report = $this->ledgerService->getDailyReport($date);
            $dateLabel = $date->format('l, d F Y');
            $dateForView = $date;
        }

        $customers = Customer::active()->orderBy('name')->get();

        // ✅ FIX: Create filters array BEFORE compact()
        $filters = [
            'date' => $request->input('date'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
        ];

        // ✅ Now pass variable names to compact()
        return view('pages.reports.daily', compact(
            'report', 
            'customers', 
            'dateForView',
            'dateLabel',
            'hasRange',
            'filters'  // ← Just the variable name, no =>
        ));
    }

    /**
     * Customer Report - Date range filtering
     */
    public function customer(Request $request): View
    {
        $customers = Customer::active()->orderBy('name')->get();
        $report = null;
        $selectedCustomer = null;

        if ($request->filled('customer_id')) {
            $selectedCustomer = Customer::find($request->input('customer_id'));
            
            if ($selectedCustomer) {
                $from = $request->filled('from_date') 
                    ? Carbon::parse($request->input('from_date'))->startOfDay() 
                    : Carbon::now()->startOfMonth()->startOfDay();
                    
                $to = $request->filled('to_date') 
                    ? Carbon::parse($request->input('to_date'))->endOfDay() 
                    : Carbon::now()->endOfDay();

                $report = $this->ledgerService->getCustomerReport($selectedCustomer, $from, $to);
            }
        }

        return view('pages.reports.customer', compact('customers', 'report', 'selectedCustomer'));
    }
}