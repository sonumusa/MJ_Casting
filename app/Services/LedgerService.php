<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\GoldReceipt;
use App\Models\Inventory;   // ← Added this import
use Carbon\Carbon;

class LedgerService
{
    public function getCustomerLedger(Customer $customer, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $invoiceQuery = $customer->invoices()
            ->where('status', 'active')
            ->orderBy('invoice_date')
            ->orderBy('id');

        $receiptQuery = $customer->goldReceipts()
            ->orderBy('receipt_date')
            ->orderBy('id');

        if ($from) {
            $invoiceQuery->whereDate('invoice_date', '>=', $from);
            $receiptQuery->whereDate('receipt_date', '>=', $from);
        }
        if ($to) {
            $invoiceQuery->whereDate('invoice_date', '<=', $to);
            $receiptQuery->whereDate('receipt_date', '<=', $to);
        }

        $invoices = $invoiceQuery->get();
        $receipts = $receiptQuery->get();

        $totalGoldKhalis = $invoices->sum('gold_khalis');
        $totalReceivedKhalis = $invoices->sum('total_received_khalis') + $receipts->sum('total_khalis_weight');
        $totalWasooli = $invoices->sum('wasooli');
        $totalInvoiced = $invoices->sum('effective_gold');

        $currentBalance = $customer->opening_balance ?? 0;
        foreach ($invoices as $inv) {
            $currentBalance = $inv->remaining_balance;
        }
        $currentBalance -= $receipts->sum('total_khalis_weight');

        return [
            'customer' => $customer,
            'opening_balance' => $customer->opening_balance ?? 0,
            'invoices' => $invoices,
            'receipts' => $receipts,
            'total_casting' => $invoices->sum('casting_weight'),
            'total_waste' => $invoices->sum('waste_weight'),
            'total_weight' => $invoices->sum('total_weight'),
            'total_gold_khalis' => $totalGoldKhalis,
            'total_received_khalis' => $totalReceivedKhalis,
            'total_invoiced' => $totalInvoiced,
            'total_wasooli' => $totalWasooli,
            'total_rp_mazdori' => $invoices->sum('rp_mazdori_amount'),
            'current_balance' => $currentBalance,
        ];
    }

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
            'total_grand_total' => $invoices->sum('effective_gold'),
            'total_received' => $invoices->sum('total_received_khalis'),
            'total_wasooli' => $invoices->sum('wasooli'),
            'invoices' => $invoices,
        ];
    }

    public function getCustomerReport(Customer $customer, Carbon $from, Carbon $to): array
    {
        $invoices = $customer->invoices()
            ->where('status', 'active')
            ->whereDate('invoice_date', '>=', $from)
            ->whereDate('invoice_date', '<=', $to)
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        $receipts = $customer->goldReceipts()
            ->whereDate('receipt_date', '>=', $from)
            ->whereDate('receipt_date', '<=', $to)
            ->get();

        return [
            'customer' => $customer,
            'date_range' => [
                'from' => $from->format('d/m/Y'),
                'to' => $to->format('d/m/Y'),
            ],
            'opening_balance' => $customer->opening_balance ?? 0,
            'total_invoices' => $invoices->count(),
            'total_gold_khalis' => $invoices->sum('gold_khalis'),
            'total_grand_total' => $invoices->sum('effective_gold'),
            'total_received_khalis' => $invoices->sum('total_received_khalis') + $receipts->sum('total_khalis_weight'),
            'total_wasooli' => $invoices->sum('wasooli'),
            'current_balance' => $invoices->isNotEmpty() 
                ? $invoices->last()->remaining_balance 
                : ($customer->opening_balance ?? 0),
            'invoices' => $invoices,
            'receipts' => $receipts,
        ];
    }

    public function getDashboardStats(): array
    {
        $activeInvoices = Invoice::where('status', 'active');

        $inventory = Inventory::first();
        $closingBalance = $inventory?->calculateClosingBalance() ?? 0;

        $totalPartyOpening = Customer::sum('opening_balance');

        return [
            'total_customers' => Customer::count(),
            'total_invoices' => $activeInvoices->count(),
            'total_gold_receipts' => GoldReceipt::count(),
            'total_gold_khalis_given' => $activeInvoices->sum('effective_gold'),
            'total_received_khalis' => $activeInvoices->sum('total_received_khalis') 
                + GoldReceipt::sum('total_khalis_weight'),
            'total_inventory_closing' => $closingBalance,
            'total_opening_stock' => ($inventory?->opening_balance ?? 0) + $totalPartyOpening,
            'total_rp_mazdori' => $activeInvoices->sum('rp_mazdori_amount'),
            'total_wasooli' => $activeInvoices->sum('wasooli'),
            'total_remaining_balance' => Customer::get()->sum(fn ($c) => $c->getCurrentBalance() ?? 0),
            'today_invoices' => Invoice::where('status', 'active')
                ->whereDate('invoice_date', Carbon::today())->count(),
            'today_receipts' => GoldReceipt::whereDate('receipt_date', Carbon::today())->count(),
            'customers_by_type' => [
                'customer' => Customer::where('party_type', 'customer')->count(),
                'dukandar' => Customer::where('party_type', 'dukandar')->count(),
                'karigar' => Customer::where('party_type', 'karigar')->count(),
            ],
        ];
    }
}
