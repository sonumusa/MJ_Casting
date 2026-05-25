<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\GoldReceipt;
use App\Models\Inventory;
use App\Models\InvoiceReceive; // ← Added for dashboard stats
use Carbon\Carbon;

class LedgerService
{
    /**
     * Get customer ledger with true chronological transaction flow
     * 
     * BALANCE FORMULA:
     * Balance = Opening 
     *         + Effective Gold (given to party) 
     *         - Total Received Khalis (gold received via invoice) 
     *         - Wasooli (cash received) 
     *         - Receipt Khalis (gold received standalone)
     */
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

        $transactions = [];

        foreach ($invoices as $invoice) {
            $netAmount = 
                ($invoice->effective_gold ?? 0) 
                - ($invoice->total_received_khalis ?? 0) 
                - ($invoice->wasooli ?? 0);
            
            $transactions[] = [
                'type' => 'invoice',
                'date' => $invoice->invoice_date,
                'sort_id' => $invoice->id * 2,
                'invoice' => $invoice,
                'object' => $invoice,
                'invoice_no' => $invoice->invoice_no,
                'effective_gold' => $invoice->effective_gold ?? 0,
                'received_khalis' => $invoice->total_received_khalis ?? 0,
                'wasooli' => $invoice->wasooli ?? 0,
                'net_amount' => round($netAmount, 3),
                'running_balance_before' => 0,
            ];
        }

        foreach ($receipts as $receipt) {
            $transactions[] = [
                'type' => 'receipt',
                'date' => $receipt->receipt_date,
                'sort_id' => $receipt->id * 2 + 1,
                'receipt' => $receipt,
                'object' => $receipt,
                'receipt_no' => $receipt->receipt_no ?? 'RCV-' . str_pad($receipt->id, 5, '0', STR_PAD_LEFT),
                'khalis_weight' => $receipt->total_khalis_weight ?? 0,
                'net_amount' => -($receipt->total_khalis_weight ?? 0),
                'running_balance_before' => 0,
            ];
        }

        usort($transactions, function ($a, $b) {
            $dateCompare = $a['date']->timestamp <=> $b['date']->timestamp;
            if ($dateCompare !== 0) return $dateCompare;
            return $a['sort_id'] <=> $b['sort_id'];
        });

        $runningBalance = $customer->opening_balance ?? 0;
        
        foreach ($transactions as &$txn) {
            $txn['running_balance_before'] = round($runningBalance, 3);
            $runningBalance += $txn['net_amount'];
            $txn['running_balance_after'] = round($runningBalance, 3);
        }
        unset($txn);

        $totalEffectiveGold = $invoices->sum('effective_gold');
        $totalInvoiceReceived = $invoices->sum('total_received_khalis');
        $totalReceiptKhalis = $receipts->sum('total_khalis_weight');
        $totalWasooli = $invoices->sum('wasooli');
        $totalReceived = $totalInvoiceReceived + $totalReceiptKhalis;

        return [
            'customer' => $customer,
            'opening_balance' => round($customer->opening_balance ?? 0, 3),
            'transactions' => $transactions,
            'invoices' => $invoices,
            'receipts' => $receipts,
            'total_casting' => round($invoices->sum('casting_weight'), 3),
            'total_waste' => round($invoices->sum('waste_weight'), 3),
            'total_weight' => round($invoices->sum('total_weight'), 3),
            'total_gold_khalis' => round($invoices->sum('gold_khalis'), 3),
            'total_effective_gold' => round($totalEffectiveGold, 3),
            'total_received_khalis' => round($totalReceived, 3),
            'total_invoice_received' => round($totalInvoiceReceived, 3),
            'total_receipt_khalis' => round($totalReceiptKhalis, 3),
            'total_wasooli' => round($totalWasooli, 3),
            'total_rp_mazdori' => round($invoices->sum('rp_mazdori_amount'), 3),
            'current_balance' => round($runningBalance, 3),
            
            // ✅ FIXED: Use 'calculation_breakdown' to match Blade template
            'calculation_breakdown' => [
                'opening' => round($customer->opening_balance ?? 0, 3),
                '+ given' => round($totalEffectiveGold, 3),
                '- received' => round($totalReceived, 3),
                '- wasooli' => round($totalWasooli, 3),
                '= balance' => round($runningBalance, 3),
            ],
            'formula' => 'Balance = Opening + Given - Received - Wasooli',
        ];
    }

    public function getDailyReport(Carbon $date): array
    {
        $dateString = $date->toDateString();

        $invoices = Invoice::where('status', 'active')
            ->whereDate('invoice_date', '=', $dateString)
            ->with('customer')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        $receipts = GoldReceipt::whereDate('receipt_date', '=', $dateString)
            ->with('customer')
            ->orderBy('receipt_date')
            ->orderBy('id')
            ->get();

        return $this->buildReportArray($date, $invoices, $receipts, true);
    }

    /**
     * ✅ NEW: Get daily report for DATE RANGE
     */
    public function getDailyReportRange(Carbon $from, Carbon $to): array
    {
        $fromString = $from->toDateString();
        $toString = $to->toDateString();

        // Get invoices within date range
        $invoices = Invoice::where('status', 'active')
            ->whereDate('invoice_date', '>=', $fromString)
            ->whereDate('invoice_date', '<=', $toString)
            ->with('customer')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        // Get receipts within date range
        $receipts = GoldReceipt::whereDate('receipt_date', '>=', $fromString)
            ->whereDate('receipt_date', '<=', $toString)
            ->with('customer')
            ->orderBy('receipt_date')
            ->orderBy('id')
            ->get();

        return $this->buildReportArray($from, $invoices, $receipts, false, $to);
    }

    /**
     * ✅ Helper: Build consistent report array for single/range
     */
    private function buildReportArray(
        Carbon $primaryDate, 
        $invoices, 
        $receipts, 
        bool $isSingleDate, 
        ?Carbon $endDate = null
    ): array {
        $totalGoldKhalis = $invoices->sum(fn($i) => $i->gold_khalis ?? 0);
        $totalEffectiveGold = $invoices->sum(fn($i) => $i->effective_gold ?? 0);
        $totalReceivedInvoice = $invoices->sum(fn($i) => $i->total_received_khalis ?? 0);
        $totalWasooli = $invoices->sum(fn($i) => $i->wasooli ?? 0);
        $totalReceiptKhalis = $receipts->sum(fn($r) => $r->total_khalis_weight ?? 0);
        $totalReceivedCombined = $totalReceivedInvoice + $totalReceiptKhalis;

        return [
            'is_range' => !$isSingleDate,
            'date' => $primaryDate,
            'date_end' => $endDate,
            'date_string' => $primaryDate->toDateString(),
            'date_range' => $isSingleDate 
                ? $primaryDate->toDateString() 
                : $primaryDate->toDateString() . ' to ' . $endDate->toDateString(),
            
            // Counts
            'total_invoices' => $invoices->count(),
            'total_receipts' => $receipts->count(),
            
            // Gold totals (rounded to 3 decimals)
            'total_gold_khalis' => round($totalGoldKhalis, 3),
            'total_grand_total' => round($totalEffectiveGold, 3),
            'total_received_invoice' => round($totalReceivedInvoice, 3),
            'total_receipt_khalis' => round($totalReceiptKhalis, 3),
            'total_received_combined' => round($totalReceivedCombined, 3),
            'total_wasooli' => round($totalWasooli, 3),
            
            // Net movement
            'net_movement' => round(
                $totalEffectiveGold - $totalReceivedInvoice - $totalWasooli - $totalReceiptKhalis,
                3
            ),
            
            // Collections for view
            'invoices' => $invoices,
            'receipts' => $receipts,
            
            // Debug (local only)
            'debug' => [
                'from' => $primaryDate->toDateString(),
                'to' => $endDate?->toDateString(),
                'is_range' => !$isSingleDate,
                'invoice_count' => $invoices->count(),
                'receipt_count' => $receipts->count(),
            ],
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

        $transactions = [];
        
        foreach ($invoices as $invoice) {
            $netAmount = 
                ($invoice->effective_gold ?? 0) 
                - ($invoice->total_received_khalis ?? 0) 
                - ($invoice->wasooli ?? 0);
                
            $transactions[] = [
                'type' => 'invoice',
                'date' => $invoice->invoice_date,
                'sort_id' => $invoice->id * 2,
                'invoice' => $invoice,
                'amount' => round($netAmount, 3),
                'effective_gold' => $invoice->effective_gold ?? 0,
                'received_khalis' => $invoice->total_received_khalis ?? 0,
                'wasooli' => $invoice->wasooli ?? 0,
            ];
        }
        
        foreach ($receipts as $receipt) {
            $transactions[] = [
                'type' => 'receipt',
                'date' => $receipt->receipt_date,
                'sort_id' => $receipt->id * 2 + 1,
                'receipt' => $receipt,
                'amount' => -($receipt->total_khalis_weight ?? 0),
                'khalis_weight' => $receipt->total_khalis_weight ?? 0,
            ];
        }

        usort($transactions, function ($a, $b) {
            $dateCompare = $a['date']->timestamp <=> $b['date']->timestamp;
            if ($dateCompare !== 0) return $dateCompare;
            return $a['sort_id'] <=> $b['sort_id'];
        });

        $rangeBalance = $customer->opening_balance ?? 0;
        foreach ($transactions as &$txn) {
            $rangeBalance += $txn['amount'];
            $txn['running_balance'] = round($rangeBalance, 3);
        }
        unset($txn);

        return [
            'customer' => $customer,
            'date_range' => [
                'from' => $from->format('d/m/Y'),
                'to' => $to->format('d/m/Y'),
            ],
            'opening_balance' => round($customer->opening_balance ?? 0, 3),
            'total_invoices' => $invoices->count(),
            'total_gold_khalis' => round($invoices->sum('gold_khalis'), 3),
            'total_grand_total' => round($invoices->sum('effective_gold'), 3),
            'total_received_khalis' => round(
                $invoices->sum('total_received_khalis') + $receipts->sum('total_khalis_weight'), 3
            ),
            'total_wasooli' => round($invoices->sum('wasooli'), 3),
            'total_receipts' => $receipts->count(),
            'current_balance' => round($rangeBalance, 3),
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

        $totalReceived = 
            $activeInvoices->sum('total_received_khalis') 
            + GoldReceipt::sum('total_khalis_weight')
            + (class_exists('App\Models\InvoiceReceive') ? InvoiceReceive::sum('khalis_weight') : 0);

        return [
            'total_customers' => Customer::count(),
            'total_invoices' => $activeInvoices->count(),
            'total_gold_receipts' => GoldReceipt::count(),
            'total_gold_khalis_given' => round($activeInvoices->sum('effective_gold'), 3),
            'total_received_khalis' => round($totalReceived, 3),
            'total_inventory_closing' => round($closingBalance, 3),
            'total_opening_stock' => round(($inventory?->opening_balance ?? 0) + $totalPartyOpening, 3),
            'total_rp_mazdori' => round($activeInvoices->sum('rp_mazdori_amount'), 3),
            'total_wasooli' => round($activeInvoices->sum('wasooli'), 3),
            'total_remaining_balance' => round(
                Customer::get()->sum(fn($c) => $c->getCurrentBalance() ?? 0), 3
            ),
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