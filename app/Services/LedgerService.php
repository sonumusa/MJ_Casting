<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;

class LedgerService
{
    /**
     * Get customer ledger with running balance
     */
    public function getCustomerLedger(Customer $customer, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = $customer->invoices()
            ->where('status', 'active')
            ->orderBy('invoice_date')
            ->orderBy('id');

        if ($from) {
            $query->whereDate('invoice_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('invoice_date', '<=', $to);
        }

        $invoices = $query->get();

        return [
            'customer' => $customer,
            'opening_balance' => $customer->opening_balance,
            'invoices' => $invoices,
            'total_casting' => $invoices->sum('casting_weight'),
            'total_waste' => $invoices->sum('waste_weight'),
            'total_weight' => $invoices->sum('total_weight'),
            'total_gold_khalis' => $invoices->sum('gold_khalis'),
            'total_invoiced' => $invoices->sum('grand_total'),
            'total_wasooli' => $invoices->sum('wasooli'),
            'current_balance' => $invoices->isNotEmpty() 
                ? $invoices->last()->remaining_balance 
                : $customer->opening_balance,
        ];
    }

    /**
     * Get daily report
     */
    public function getDailyReport(Carbon $date): array
    {
        $invoices = Invoice::where('status', 'active')
            ->whereDate('invoice_date', $date)
            ->with('customer')
            ->orderBy('id')
            ->get();

        return [
            'date' => $date,
            'total_invoices' => $invoices->count(),
            'total_gold_khalis' => $invoices->sum('gold_khalis'),
            'total_grand_total' => $invoices->sum('grand_total'),
            'total_wasooli' => $invoices->sum('wasooli'),
            'invoices' => $invoices,
        ];
    }

    /**
     * Get customer report for date range
     */
    public function getCustomerReport(Customer $customer, Carbon $from, Carbon $to): array
    {
        $invoices = $customer->invoices()
            ->where('status', 'active')
            ->whereDate('invoice_date', '>=', $from)
            ->whereDate('invoice_date', '<=', $to)
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        return [
            'customer' => $customer,
            'date_range' => [
                'from' => $from->format('d/m/Y'),
                'to' => $to->format('d/m/Y'),
            ],
            'opening_balance' => $customer->opening_balance,
            'total_invoices' => $invoices->count(),
            'total_gold_khalis' => $invoices->sum('gold_khalis'),
            'total_grand_total' => $invoices->sum('grand_total'),
            'total_wasooli' => $invoices->sum('wasooli'),
            'current_balance' => $invoices->isNotEmpty() 
                ? $invoices->last()->remaining_balance 
                : $customer->opening_balance,
            'invoices' => $invoices,
        ];
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        $activeInvoices = Invoice::where('status', 'active');

        return [
            'total_customers' => Customer::active()->count(),
            'total_invoices' => $activeInvoices->count(),
            'total_gold_khalis' => $activeInvoices->sum('gold_khalis'),
            'total_rp_amount' => $activeInvoices->sum('rp_amount'),
            'total_grand_total' => $activeInvoices->sum('grand_total'),
            'total_wasooli' => $activeInvoices->sum('wasooli'),
            'total_remaining_balance' => Customer::active()
                ->get()
                ->sum(fn ($c) => $c->getCurrentBalance()),
            'today_invoices' => Invoice::where('status', 'active')->whereDate('invoice_date', Carbon::today())->count(),
        ];
    }
}
