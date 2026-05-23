<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\GoldReceipt;
use App\Models\Inventory;   // ← Added this import
use Carbon\Carbon;

class LedgerService
{
    /**
     * Get customer ledger with true chronological transaction flow
     * Invoices and receipts integrated into single running balance
     */
    public function getCustomerLedger(Customer $customer, ?Carbon $from = null, ?Carbon $to = null): array
    {
        // Get all invoices
        $invoiceQuery = $customer->invoices()
            ->where('status', 'active')
            ->orderBy('invoice_date')
            ->orderBy('id');

        // Get all receipts
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

        // Build integrated transaction list with running balance
        $transactions = [];
        $runningBalance = $customer->opening_balance ?? 0;

        // Add invoices to transaction list
        foreach ($invoices as $invoice) {
            $transactions[] = [
                'type' => 'invoice',
                'date' => $invoice->invoice_date,
                'sort_id' => $invoice->id,
                'invoice' => $invoice,
                'invoice_no' => $invoice->invoice_no,
                'amount' => $invoice->effective_gold,
                'received' => $invoice->total_received_khalis,
                'wasooli' => $invoice->wasooli,
                'net_amount' => $invoice->effective_gold - $invoice->total_received_khalis,
                'running_balance_before' => $runningBalance,
            ];
        }

        // Add receipts to transaction list
        foreach ($receipts as $receipt) {
            $transactions[] = [
                'type' => 'receipt',
                'date' => $receipt->receipt_date,
                'sort_id' => $receipt->id,
                'receipt' => $receipt,
                'receipt_no' => $receipt->receipt_no ?? 'RCV-' . str_pad($receipt->id, 5, '0', STR_PAD_LEFT),
                'amount' => -$receipt->total_khalis_weight,
                'net_amount' => -$receipt->total_khalis_weight,
                'running_balance_before' => $runningBalance,
            ];
        }

        // Sort transactions by date, then by id for stable order
        usort($transactions, function ($a, $b) {
            $dateCompare = $a['date']->compare($b['date']);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }
            return $a['sort_id'] <=> $b['sort_id'];
        });

        // Calculate running balance for each transaction
        $runningBalance = $customer->opening_balance ?? 0;
        foreach ($transactions as &$txn) {
            $txn['running_balance_before'] = $runningBalance;
            $runningBalance += $txn['net_amount'];
            $txn['running_balance_after'] = $runningBalance;
        }
        unset($txn);

        // Calculate totals
        $totalGoldKhalis = $invoices->sum('gold_khalis');
        $totalReceivedKhalis = $invoices->sum('total_received_khalis') + $receipts->sum('total_khalis_weight');
        $totalWasooli = $invoices->sum('wasooli');
        $totalInvoiced = $invoices->sum('effective_gold');

        return [
            'customer' => $customer,
            'opening_balance' => $customer->opening_balance ?? 0,
            'transactions' => $transactions,  // NEW: integrated transaction list
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
            'current_balance' => $runningBalance,
        ];
    }

    /**
     * Get daily report - now includes receipts for that day
     */
    public function getDailyReport(Carbon $date): array
    {
        $invoices = Invoice::where('status', 'active')
            ->whereDate('invoice_date', $date)
            ->with('customer')
            ->orderBy('id')
            ->get();

        $receipts = GoldReceipt::whereDate('receipt_date', $date)
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
            'total_receipts' => $receipts->count(),
            'total_receipt_khalis' => $receipts->sum('total_khalis_weight'),
            'invoices' => $invoices,
            'receipts' => $receipts,
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

        // Build chronological transactions for date range
        $transactions = [];
        foreach ($invoices as $invoice) {
            $transactions[] = [
                'type' => 'invoice',
                'date' => $invoice->invoice_date,
                'sort_id' => $invoice->id,
                'invoice' => $invoice,
                'amount' => $invoice->effective_gold - $invoice->total_received_khalis,
            ];
        }
        foreach ($receipts as $receipt) {
            $transactions[] = [
                'type' => 'receipt',
                'date' => $receipt->receipt_date,
                'sort_id' => $receipt->id,
                'receipt' => $receipt,
                'amount' => -$receipt->total_khalis_weight,
            ];
        }

        usort($transactions, function ($a, $b) {
            $dateCompare = $a['date']->compare($b['date']);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }
            return $a['sort_id'] <=> $b['sort_id'];
        });

        // Calculate running balance for date range only
        $rangeBalance = $customer->opening_balance ?? 0;
        foreach ($transactions as &$txn) {
            $rangeBalance += $txn['amount'];
            $txn['running_balance'] = $rangeBalance;
        }
        unset($txn);

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
            'total_receipts' => $receipts->count(),
            'current_balance' => $rangeBalance,
            'transactions' => $transactions,
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
