<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\GoldReceipt;
use App\Models\Inventory;
use App\Models\User;
use App\Services\GoldCalculationService;
use App\Services\InvoiceService;
use App\Services\LedgerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FullWorkflowCustomerInvoiceReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_customer_invoice_receipt_workflow_updates_reports_and_inventory(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customerPayload = [
            'name' => 'Workflow Test Customer',
            'phone' => '03450000000',
            'cnic' => '12345-6789012-3',
            'address' => '123 Test Road',
            'city' => 'Test City',
            'opening_balance' => 12.5,
            'party_type' => 'customer',
        ];

        $this->post('/customers', $customerPayload)
            ->assertStatus(302)
            ->assertRedirect(route('customers.index'));

        $customer = Customer::where('phone', '03450000000')->first();
        $this->assertNotNull($customer);
        $this->assertSame('Workflow Test Customer', $customer->name);
        $this->assertSame(12.5, $customer->opening_balance);

        $this->get('/customers')
            ->assertStatus(200)
            ->assertSee('Workflow Test Customer');

        $invoiceService = app(InvoiceService::class);
        $calcService = app(GoldCalculationService::class);
        $ledgerService = app(LedgerService::class);

        $invoiceInput = [
            'customer_id' => $customer->id,
            'invoice_date' => Carbon::today()->toDateString(),
            'casting_weight' => 2.5,
            'waste_weight' => 0.5,
            'waste_auto' => 1,
            'ratti' => 2.0,
            'ratti_auto' => 0,
            'ratti_rate' => 0.5,
            'rp_rate' => 65000,
            'rp_mazdori_weight' => 0.12,
            'rp_mazdori_rate' => 5000,
            'casting_mazdori_weight' => 0.15,
            'casting_mazdori_rate' => 5000,
            'male_waste_auto' => 1,
            'wasooli' => 0,
        ];

        $invoice = $invoiceService->create($invoiceInput);
        $invoice->refresh();
        $customer->refresh();

        $expected = $calcService->calculate(array_merge($invoiceInput, [
            'previous_balance' => 12.5,
            'total_received_khalis' => 0,
        ]));

        $this->assertSame($expected['waste_weight'], $invoice->waste_weight);
        $this->assertSame($expected['total_weight'], $invoice->total_weight);
        $this->assertSame($expected['male_waste'], $invoice->male_waste);
        $this->assertSame($expected['gold_khalis'], $invoice->gold_khalis);
        $this->assertSame($expected['effective_gold'], $invoice->effective_gold);
        $this->assertSame($expected['grand_total'], $invoice->grand_total);
        $this->assertSame(12.5, $invoice->previous_balance);
        $this->assertSame($expected['remaining_balance'], $invoice->remaining_balance);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'customer_id' => $customer->id,
            'effective_gold' => $expected['effective_gold'],
            'remaining_balance' => $expected['remaining_balance'],
        ]);

        $dailyReport = $this->getJson('/api/v1/reports/daily?date=' . Carbon::today()->toDateString())
            ->assertStatus(200)
            ->json('data');

        $this->assertSame(1, $dailyReport['total_invoices']);
        $this->assertEqualsWithDelta($expected['gold_khalis'], $dailyReport['total_gold_khalis'], 0.001);
        $this->assertEqualsWithDelta($expected['effective_gold'], $dailyReport['total_grand_total'], 0.001);
        $this->assertEqualsWithDelta(0.0, $dailyReport['total_received'], 0.001);
        $this->assertEqualsWithDelta(0.0, $dailyReport['total_wasooli'], 0.001);
        $this->assertSame(0, $dailyReport['total_receipts']);
        $this->assertEqualsWithDelta(0.0, $dailyReport['total_receipt_khalis'], 0.001);

        $customerReport = $this->getJson('/api/v1/reports/customer?customer_id=' . $customer->id . '&from_date=' . Carbon::today()->toDateString() . '&to_date=' . Carbon::today()->toDateString())
            ->assertStatus(200)
            ->json('data');

        $this->assertSame(1, $customerReport['total_invoices']);
        $this->assertEqualsWithDelta($expected['effective_gold'], $customerReport['total_grand_total'], 0.001);
        $this->assertEqualsWithDelta(0.0, $customerReport['total_received_khalis'], 0.001);
        $this->assertEqualsWithDelta(0.0, $customerReport['total_wasooli'], 0.001);
        $this->assertEqualsWithDelta($customer->getCurrentBalance(), $customerReport['current_balance'], 0.001);

        $this->assertSame(1, $ledgerService->getCustomerLedger($customer)['transactions'][0]['type'] === 'invoice' ? 1 : 0);
        $customer->refresh();

        $receiptPayload = [
            'customer_id' => $customer->id,
            'receipt_type' => 'customer',
            'receipt_date' => Carbon::today()->toDateString(),
            'remarks' => 'Workflow receipt',
            'items' => [
                [
                    'description' => 'Receipt item 1',
                    'gross_weight' => 1.2,
                    'ratti_impurity' => 2.0,
                ],
            ],
        ];

        $this->post('/gold-receipts', $receiptPayload)
            ->assertStatus(302);

        $receipt = GoldReceipt::firstWhere('customer_id', $customer->id);
        $this->assertNotNull($receipt);
        $this->assertSame(1.2, $receipt->total_gross_weight);

        $receiptKhalis = round($calcService->convertToKhalis(1.2, 2.0), 3);
        $this->assertSame($receiptKhalis, $receipt->total_khalis_weight);

        $inventory = Inventory::first();
        $this->assertNotNull($inventory);
        $this->assertEqualsWithDelta(0.0 + $receiptKhalis, $inventory->received, 0.001);
        $this->assertEqualsWithDelta($inventory->opening_balance + $inventory->received - $inventory->given_invoices, $inventory->closing_balance, 0.001);

        $dailyReportAfterReceipt = $this->getJson('/api/v1/reports/daily?date=' . Carbon::today()->toDateString())
            ->assertStatus(200)
            ->json('data');

        $this->assertSame(1, $dailyReportAfterReceipt['total_invoices']);
        $this->assertSame(1, $dailyReportAfterReceipt['total_receipts']);
        $this->assertEqualsWithDelta($receiptKhalis, $dailyReportAfterReceipt['total_receipt_khalis'], 0.001);

        $customerReportAfterReceipt = $this->getJson('/api/v1/reports/customer?customer_id=' . $customer->id . '&from_date=' . Carbon::today()->toDateString() . '&to_date=' . Carbon::today()->toDateString())
            ->assertStatus(200)
            ->json('data');

        $this->assertSame(1, $customerReportAfterReceipt['total_invoices']);
        $this->assertEqualsWithDelta($receiptKhalis, $customerReportAfterReceipt['total_received_khalis'], 0.001);
        $this->assertEqualsWithDelta($customer->getCurrentBalance(), $customerReportAfterReceipt['current_balance'], 0.001);

        $this->get('/customers')
            ->assertStatus(200)
            ->assertSee('Workflow Test Customer');

        $this->get('/customers/' . $customer->id . '/last-balance')
            ->assertStatus(200)
            ->assertJson(['balance' => round($customer->getCurrentBalance(), 3)]);
    }
}
