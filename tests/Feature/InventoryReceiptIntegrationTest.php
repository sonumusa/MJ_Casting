<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\GoldReceipt;
use App\Models\GoldReceiptItem;
use App\Models\InvoiceReceive;
use App\Models\Inventory;
use App\Models\User;
use App\Services\GoldCalculationService;
use App\Services\InvoiceService;
use App\Services\LedgerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryReceiptIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_receipt_inventory_and_dashboard_sync(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = Customer::create([
            'name' => 'Inventory Party',
            'phone' => '03110000000',
            'address' => 'Warehouse Lane',
            'city' => 'Test City',
            'opening_balance' => 5.000,
            'status' => 'active',
            'party_type' => 'customer',
        ]);

        Inventory::create([
            'opening_balance' => 5.000,
            'received' => 0.000,
            'given_invoices' => 0.000,
            'closing_balance' => 5.000,
        ]);

        $invoiceService = app(InvoiceService::class);
        $calcService = app(GoldCalculationService::class);
        $ledgerService = app(LedgerService::class);

        $invoice = $invoiceService->create([
            'customer_id' => $customer->id,
            'invoice_date' => Carbon::today(),
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
            'wasooli' => 0,
        ]);

        $inventory = Inventory::first();
        $this->assertNotNull($inventory, 'Inventory record should be created by invoice observer.');

        $this->assertSame(
            round($invoice->effective_gold, 3),
            round($inventory->given_invoices, 3),
            'Inventory given_invoices should reflect invoice effective_gold.'
        );

        $this->assertSame(
            round($inventory->opening_balance + $inventory->received - $inventory->given_invoices, 3),
            $inventory->closing_balance,
            'Inventory closing_balance should equal opening + received - given_invoices.'
        );

        $receiveGross = 0.8;
        $receiveRatti = 3.0;
        $receiveKhalis = round($calcService->convertToKhalis($receiveGross, $receiveRatti), 3);

        InvoiceReceive::create([
            'invoice_id' => $invoice->id,
            'description' => 'Payment receive',
            'gross_weight' => $receiveGross,
            'ratti_impurity' => $receiveRatti,
            'khalis_weight' => $receiveKhalis,
        ]);

        $inventory->refresh();
        $this->assertSame(
            $receiveKhalis,
            $inventory->received,
            'Inventory received should update when invoice receive is created.'
        );

        $receiptGross = 1.2;
        $receiptRatti = 2.0;
        $receiptKhalis = round($calcService->convertToKhalis($receiptGross, $receiptRatti), 3);

        $receipt = GoldReceipt::create([
            'receipt_no' => 'RCV-00001',
            'customer_id' => $customer->id,
            'receipt_type' => 'customer',
            'receipt_date' => Carbon::today(),
            'remarks' => 'Plain gold receipt',
            'total_gross_weight' => 0,
            'total_khalis_weight' => 0,
            'created_by' => auth()->id(),
        ]);

        GoldReceiptItem::create([
            'receipt_id' => $receipt->id,
            'description' => 'Gold item',
            'gross_weight' => $receiptGross,
            'ratti_impurity' => $receiptRatti,
            'khalis_weight' => $receiptKhalis,
        ]);

        $receipt->update([
            'total_gross_weight' => round($receiptGross, 3),
            'total_khalis_weight' => $receiptKhalis,
        ]);

        $inventory->refresh();
        $this->assertSame(
            round($receiveKhalis + $receiptKhalis, 3),
            $inventory->received,
            'Inventory received should include both invoice receives and gold receipts.'
        );

        $expectedClosing = round($inventory->opening_balance + $inventory->received - $inventory->given_invoices, 3);
        $this->assertSame($expectedClosing, $inventory->closing_balance);

        $stats = $ledgerService->getDashboardStats();
        $this->assertSame(1, $stats['total_invoices']);
        $this->assertSame($invoice->effective_gold, $stats['total_gold_khalis_given']);
        $this->assertSame($receiptKhalis, $stats['total_received_khalis']);
        $this->assertSame($expectedClosing, $stats['total_inventory_closing']);
        $this->assertSame(10.000, $stats['total_opening_stock']);

        $this->assertSame(
            round($customer->getCurrentBalance(), 3),
            round($invoice->remaining_balance - $receiptKhalis + $invoice->total_received_khalis, 3)
        );
    }
}
