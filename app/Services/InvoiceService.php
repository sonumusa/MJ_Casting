<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceService
{
    public function __construct(
        private GoldCalculationService $calculationService
    ) {}

    /**
     * Generate unique invoice number
     */
    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $lastInvoice = Invoice::withTrashed()->latest('id')->first();
        $number = ($lastInvoice?->id ?? 0) + 1;
        
        return $prefix . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Format invoice for display
     */
    public function formatInvoiceData(Invoice $invoice): array
    {
        return [
            'invoice_no' => $invoice->invoice_no,
            'customer_name' => $invoice->customer->name,
            'date' => $invoice->invoice_date->format('d M Y'),
            'gold_khalis' => $this->calculationService->formatWeight($invoice->gold_khalis),
            'effective_gold' => $this->calculationService->formatWeight($invoice->effective_gold),
            'remaining_balance' => $this->calculationService->formatWeight($invoice->remaining_balance),
        ];
    }
}