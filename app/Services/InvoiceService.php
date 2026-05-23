<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;

class InvoiceService
{
    public function __construct(
        private GoldCalculationService $calculationService
    ) {}

    /**
     * Create new invoice with calculations
     */
    public function create(array $data): Invoice
    {
        $customer = Customer::find($data['customer_id']);
        
        // Get previous balance from last invoice or opening balance
        $lastInvoice = $customer->invoices()
            ->where('status', 'active')
            ->latest('invoice_date')
            ->latest('id')
            ->first();
        
        $previousBalance = $lastInvoice?->remaining_balance ?? $customer->opening_balance;

        // Generate invoice number
        $invoiceNo = $this->generateInvoiceNumber();

        // Calculate all fields
        $calculations = $this->calculationService->calculate([
            'casting_weight' => $data['casting_weight'],
            'waste_weight' => $data['waste_weight'],
            'waste_auto' => $data['waste_auto'] ?? true,
            'ratti' => $data['ratti'] ?? 0,
            'ratti_auto' => $data['ratti_auto'] ?? true,
            'ratti_rate' => $data['ratti_rate'] ?? 0,
            'male_waste' => $data['male_waste'] ?? 0,
            'male_waste_auto' => $data['male_waste_auto'] ?? true,
            'rp_rate' => $data['rp_rate'],
            'rp_mazdori_weight' => $data['rp_mazdori_weight'] ?? 0,
            'rp_mazdori_rate' => $data['rp_mazdori_rate'] ?? 0,
            'casting_mazdori_weight' => $data['casting_mazdori_weight'] ?? 0,
            'casting_mazdori_rate' => $data['casting_mazdori_rate'] ?? 0,
            'previous_balance' => $previousBalance,
            'wasooli' => $data['wasooli'] ?? 0,
        ]);

        // Create invoice
        $invoice = Invoice::create([
            'invoice_no' => $invoiceNo,
            'customer_id' => $data['customer_id'],
            'invoice_date' => $data['invoice_date'],
            'casting_weight' => $data['casting_weight'],
            'waste_weight' => $data['waste_weight'],
            'total_weight' => $calculations['total_weight'],
            'ratti' => $calculations['ratti'],
            'ratti_auto' => $calculations['ratti_auto'],
            'ratti_rate' => $data['ratti_rate'] ?? 0,
            'male_waste' => $calculations['male_waste'],
            'male_waste_auto' => $calculations['male_waste_auto'],
            'waste_auto' => $calculations['waste_auto'],
            'gold_khalis' => $calculations['gold_khalis'],
            'rp_rate' => $data['rp_rate'],
            'rp_amount' => $this->calculationService->multiply(
                $calculations['gold_khalis'],
                $data['rp_rate']
            ),
            'rp_mazdori_weight' => $data['rp_mazdori_weight'] ?? 0,
            'rp_mazdori_rate' => $data['rp_mazdori_rate'] ?? 0,
            'rp_mazdori_amount' => $calculations['rp_mazdori_amount'],
            'casting_mazdori_weight' => $data['casting_mazdori_weight'] ?? 0,
            'casting_mazdori_rate' => $data['casting_mazdori_rate'] ?? 0,
            'casting_mazdori_amount' => $calculations['casting_mazdori_amount'],
            'effective_gold' => $calculations['effective_gold'],
            'grand_total' => $calculations['grand_total'],
            'wasooli' => $data['wasooli'] ?? 0,
            'previous_balance' => $previousBalance,
            'remaining_balance' => $calculations['remaining_balance'],
            'manual_book_no' => $data['manual_book_no'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);

        return $invoice;
    }

    /**
     * Update existing invoice
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        $customer = Customer::find($data['customer_id'] ?? $invoice->customer_id);
        
        // If customer changed, recalculate for old customer
        if (isset($data['customer_id']) && $data['customer_id'] !== $invoice->customer_id) {
            $oldCustomer = $invoice->customer;
            $this->calculationService->recalculateChain($oldCustomer);
        }

        // Get previous balance from last invoice (excluding this one) or opening balance
        $lastInvoice = $customer->invoices()
            ->where('status', 'active')
            ->where('id', '!=', $invoice->id)
            ->latest('invoice_date')
            ->latest('id')
            ->first();
        
        $previousBalance = $lastInvoice?->remaining_balance ?? $customer->opening_balance;

        // Calculate all fields
        $calculations = $this->calculationService->calculate([
            'casting_weight' => $data['casting_weight'] ?? $invoice->casting_weight,
            'waste_weight' => $data['waste_weight'] ?? $invoice->waste_weight,
            'waste_auto' => $data['waste_auto'] ?? $invoice->waste_auto,
            'ratti' => $data['ratti'] ?? $invoice->ratti,
            'ratti_auto' => $data['ratti_auto'] ?? $invoice->ratti_auto,
            'ratti_rate' => $data['ratti_rate'] ?? $invoice->ratti_rate,
            'male_waste' => $data['male_waste'] ?? $invoice->male_waste,
            'male_waste_auto' => $data['male_waste_auto'] ?? $invoice->male_waste_auto,
            'rp_rate' => $data['rp_rate'] ?? $invoice->rp_rate,
            'rp_mazdori_weight' => $data['rp_mazdori_weight'] ?? $invoice->rp_mazdori_weight,
            'rp_mazdori_rate' => $data['rp_mazdori_rate'] ?? $invoice->rp_mazdori_rate,
            'casting_mazdori_weight' => $data['casting_mazdori_weight'] ?? $invoice->casting_mazdori_weight,
            'casting_mazdori_rate' => $data['casting_mazdori_rate'] ?? $invoice->casting_mazdori_rate,
            'previous_balance' => $previousBalance,
            'wasooli' => $data['wasooli'] ?? $invoice->wasooli,
        ]);

        // Update invoice
        $invoice->update([
            'customer_id' => $data['customer_id'] ?? $invoice->customer_id,
            'invoice_date' => $data['invoice_date'] ?? $invoice->invoice_date,
            'casting_weight' => $data['casting_weight'] ?? $invoice->casting_weight,
            'waste_weight' => $data['waste_weight'] ?? $invoice->waste_weight,
            'waste_auto' => $calculations['waste_auto'],
            'total_weight' => $calculations['total_weight'],
            'ratti' => $calculations['ratti'],
            'ratti_auto' => $calculations['ratti_auto'],
            'ratti_rate' => $data['ratti_rate'] ?? $invoice->ratti_rate,
            'male_waste' => $calculations['male_waste'],
            'male_waste_auto' => $calculations['male_waste_auto'],
            'gold_khalis' => $calculations['gold_khalis'],
            'rp_rate' => $data['rp_rate'] ?? $invoice->rp_rate,
            'rp_amount' => $this->calculationService->multiply(
                $calculations['gold_khalis'],
                $data['rp_rate'] ?? $invoice->rp_rate
            ),
            'rp_mazdori_weight' => $data['rp_mazdori_weight'] ?? $invoice->rp_mazdori_weight,
            'rp_mazdori_rate' => $data['rp_mazdori_rate'] ?? $invoice->rp_mazdori_rate,
            'rp_mazdori_amount' => $calculations['rp_mazdori_amount'],
            'casting_mazdori_weight' => $data['casting_mazdori_weight'] ?? $invoice->casting_mazdori_weight,
            'casting_mazdori_rate' => $data['casting_mazdori_rate'] ?? $invoice->casting_mazdori_rate,
            'casting_mazdori_amount' => $calculations['casting_mazdori_amount'],
            'effective_gold' => $calculations['effective_gold'],
            'grand_total' => $calculations['grand_total'],
            'wasooli' => $data['wasooli'] ?? $invoice->wasooli,
            'previous_balance' => $previousBalance,
            'remaining_balance' => $calculations['remaining_balance'],
            'manual_book_no' => $data['manual_book_no'] ?? $invoice->manual_book_no,
            'remarks' => $data['remarks'] ?? $invoice->remarks,
            'updated_by' => auth()->id(),
        ]);

        // Recalculate balance chain for current customer
        $this->calculationService->recalculateChain($customer);

        return $invoice;
    }

    /**
     * Delete invoice and recalculate balances
     */
    public function delete(Invoice $invoice): bool
    {
        $customer = $invoice->customer;
        
        // Soft delete
        $invoice->delete();
        
        // Recalculate remaining invoices for customer
        $this->calculationService->recalculateChain($customer);
        
        return true;
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $lastInvoice = Invoice::latest('id')->first();
        $number = ($lastInvoice?->id ?? 0) + 1;
        
        return $prefix . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}
