<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\GoldReceipt;
use App\Models\User;
use App\Services\GoldCalculationService;
use App\Services\InvoiceService;
use App\Services\LedgerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndInvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_creation_receipt_and_ledger_integration(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = Customer::create([
            'name' => 'Test Party',
            'phone' => '03000000000',
            'address' => 'Test Address',
            'city' => 'Test City',
            'opening_balance' => 10.000,
            'status' => 'active',
            'party_type' => 'customer',
        ]);

        $input = [
            'casting_weight' => 2.5,
            'waste_weight' => 0.5,
            'waste_auto' => 1,
            'ratti' => 2.0,
            'ratti_rate' => 0.5,
            'rp_rate' => 65620,
            'rp_mazdori_weight' => 0.12,
            'rp_mazdori_rate' => 5000,
            'casting_mazdori_weight' => 0.15,
            'casting_mazdori_rate' => 5000,
            'male_waste_auto' => 1,
            'ratti_auto' => 0,
            'wasooli' => 0,
        ];

        $invoiceService = app(InvoiceService::class);
        $calculationService = app(GoldCalculationService::class);
        $ledgerService = app(LedgerService::class);

        $expected = $calculationService->calculate(array_merge($input, [
            'previous_balance' => 10.0,
            'total_received_khalis' => 0,
        ]));

        $invoice = $invoiceService->create(array_merge($input, [
            'customer_id' => $customer->id,
            'invoice_date' => Carbon::today(),
        ]));

        $invoice->refresh();

        $this->assertSame($expected['waste_weight'], $invoice->waste_weight);
        $this->assertSame($expected['total_weight'], $invoice->total_weight);
        $this->assertSame($expected['male_waste'], $invoice->male_waste);
        $this->assertSame($expected['gold_khalis'], $invoice->gold_khalis);
        $this->assertSame($expected['effective_gold'], $invoice->effective_gold);
        $this->assertSame($expected['grand_total'], $invoice->grand_total);
        $this->assertSame($expected['remaining_balance'], $invoice->remaining_balance);
        $this->assertSame(10.0, $invoice->previous_balance);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'customer_id' => $customer->id,
            'effective_gold' => $expected['effective_gold'],
            'remaining_balance' => $expected['remaining_balance'],
        ]);

        $receipt = GoldReceipt::create([
            'receipt_no' => 'RCV-00001',
            'customer_id' => $customer->id,
            'receipt_date' => Carbon::today(),
            'total_gross_weight' => 0.5,
            'total_khalis_weight' => 0.5,
        ]);

        $customer->refresh();

        $ledger = $ledgerService->getCustomerLedger($customer);

        $this->assertSame(2, count($ledger['transactions']));

        $invoiceTxn = $ledger['transactions'][0];
        $receiptTxn = $ledger['transactions'][1];

        $this->assertSame('invoice', $invoiceTxn['type']);
        $this->assertSame($expected['effective_gold'], $invoiceTxn['amount']);
        $this->assertSame(10.000, $invoiceTxn['running_balance_before']);
        $this->assertSame(10.000 + $expected['effective_gold'], $invoiceTxn['running_balance_after']);

        $this->assertSame('receipt', $receiptTxn['type']);
        $this->assertSame(-0.5, $receiptTxn['amount']);
        $this->assertSame(10.000 + $expected['effective_gold'], $receiptTxn['running_balance_before']);
        $this->assertSame(10.000 + $expected['effective_gold'] - 0.5, $receiptTxn['running_balance_after']);

        $this->assertSame(round(10.000 + $expected['effective_gold'] - 0.5, 3), $customer->getCurrentBalance());
        $this->assertSame(round(10.000 + $expected['effective_gold'] - 0.5, 3), $ledger['current_balance']);

        $dailyReport = $ledgerService->getDailyReport(Carbon::today());
        $this->assertSame(1, $dailyReport['total_invoices']);
        $this->assertSame($expected['gold_khalis'], $dailyReport['total_gold_khalis']);
        $this->assertSame($expected['effective_gold'], $dailyReport['total_grand_total']);
        $this->assertSame(0.0, $dailyReport['total_received']);
        $this->assertSame(0.0, $dailyReport['total_wasooli']);
        $this->assertSame(1, $dailyReport['total_receipts']);
        $this->assertSame(0.5, $dailyReport['total_receipt_khalis']);

        $customerReport = $ledgerService->getCustomerReport($customer, Carbon::today(), Carbon::today());
        $this->assertSame(1, $customerReport['total_invoices']);
        $this->assertSame($expected['effective_gold'], $customerReport['total_grand_total']);
        $this->assertSame(0.5, $customerReport['total_received_khalis']);
        $this->assertSame(round(10.000 + $expected['effective_gold'] - 0.5, 3), $customerReport['current_balance']);
    }
}
