<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\GoldReceipt;
use App\Models\GoldReceiptItem;
use App\Models\Invoice;
use App\Models\User;
use App\Services\GoldCalculationService;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiReportEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_report_api_returns_expected_values(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $customer = Customer::create([
            'name' => 'API Report Customer',
            'phone' => '03330000000',
            'address' => 'Report Street',
            'city' => 'Test City',
            'opening_balance' => 15.000,
            'status' => 'active',
            'party_type' => 'customer',
        ]);

        $invoiceService = app(InvoiceService::class);
        $calcService = app(GoldCalculationService::class);

        $invoice = $invoiceService->create([
            'customer_id' => $customer->id,
            'invoice_date' => Carbon::today(),
            'casting_weight' => 2.5,
            'waste_weight' => 0.5,
            'waste_auto' => 1,
            'ratti' => 2.0,
            'ratti_rate' => 0.5,
            'rp_rate' => 65000,
            'rp_mazdori_weight' => 0.10,
            'rp_mazdori_rate' => 5000,
            'casting_mazdori_weight' => 0.12,
            'casting_mazdori_rate' => 5000,
            'male_waste_auto' => 1,
            'wasooli' => 0,
        ]);

        $receiptGross = 1.4;
        $receiptRatti = 2.0;
        $receiptKhalis = round($calcService->convertToKhalis($receiptGross, $receiptRatti), 3);

        $receipt = GoldReceipt::create([
            'receipt_no' => 'RCV-00010',
            'customer_id' => $customer->id,
            'receipt_type' => 'customer',
            'receipt_date' => Carbon::today(),
            'remarks' => 'API daily receipt',
            'total_gross_weight' => round($receiptGross, 3),
            'total_khalis_weight' => $receiptKhalis,
            'created_by' => $user->id,
        ]);

        $response = $this->getJson('/api/v1/reports/daily?date=' . Carbon::today()->toDateString());

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['date', 'total_invoices', 'total_gold_khalis', 'total_grand_total', 'total_received', 'total_wasooli', 'total_receipts', 'total_receipt_khalis', 'invoices', 'receipts']])
            ->assertJson([ 
                'success' => true,
                'data' => [
                    'total_invoices' => 1,
                    'total_gold_khalis' => $invoice->gold_khalis,
                    'total_grand_total' => $invoice->effective_gold,
                    'total_received' => $invoice->total_received_khalis,
                    'total_wasooli' => $invoice->wasooli,
                    'total_receipts' => 1,
                    'total_receipt_khalis' => $receiptKhalis,
                ],
            ]);
    }

    public function test_customer_report_api_returns_invoice_and_receipt_totals(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $customer = Customer::create([
            'name' => 'Customer Report API',
            'phone' => '03340000000',
            'address' => 'Report Road',
            'city' => 'Test City',
            'opening_balance' => 20.000,
            'status' => 'active',
            'party_type' => 'customer',
        ]);

        $invoiceService = app(InvoiceService::class);
        $calcService = app(GoldCalculationService::class);

        $invoice = $invoiceService->create([
            'customer_id' => $customer->id,
            'invoice_date' => Carbon::today(),
            'casting_weight' => 3.0,
            'waste_weight' => 0.6,
            'waste_auto' => 1,
            'ratti' => 2.0,
            'ratti_rate' => 0.5,
            'rp_rate' => 65000,
            'rp_mazdori_weight' => 0.20,
            'rp_mazdori_rate' => 5000,
            'casting_mazdori_weight' => 0.15,
            'casting_mazdori_rate' => 5000,
            'male_waste_auto' => 1,
            'wasooli' => 0,
        ]);

        $receiptGross = 0.9;
        $receiptRatti = 3.0;
        $receiptKhalis = round($calcService->convertToKhalis($receiptGross, $receiptRatti), 3);

        $receipt = GoldReceipt::create([
            'receipt_no' => 'RCV-00011',
            'customer_id' => $customer->id,
            'receipt_type' => 'customer',
            'receipt_date' => Carbon::today(),
            'remarks' => 'Customer report receipt',
            'total_gross_weight' => round($receiptGross, 3),
            'total_khalis_weight' => $receiptKhalis,
            'created_by' => $user->id,
        ]);

        $response = $this->getJson('/api/v1/reports/customer?customer_id=' . $customer->id . '&from_date=' . Carbon::today()->toDateString() . '&to_date=' . Carbon::today()->toDateString());

        $data = $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data' => ['total_invoices', 'total_grand_total', 'total_received_khalis', 'total_wasooli', 'current_balance']])
            ->json('data');

        $this->assertSame(1, $data['total_invoices']);
        $this->assertEqualsWithDelta($invoice->effective_gold, $data['total_grand_total'], 0.001);
        $this->assertEqualsWithDelta($invoice->total_received_khalis + $receiptKhalis, $data['total_received_khalis'], 0.001);
        $this->assertEqualsWithDelta($invoice->wasooli, $data['total_wasooli'], 0.001);
        $this->assertEqualsWithDelta($customer->getCurrentBalance(), $data['current_balance'], 0.001);
    }
}
