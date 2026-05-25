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

class InvoiceUpdateDeleteIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_update_delete_and_inventory_recalculation(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = Customer::create([
            'name' => 'Update Party',
            'phone' => '03220000000',
            'address' => 'Update Avenue',
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

        $invoice1 = $invoiceService->create([
            'customer_id' => $customer->id,
            'invoice_date' => Carbon::today(),
            'casting_weight' => 2.5,
            'waste_weight' => 0.5,
            'waste_auto' => 1,
            'ratti' => 2.0,
            'ratti_rate' => 0.5,
            'rp_rate' => 65620,
            'rp_mazdori_weight' => 0.10,
            'rp_mazdori_rate' => 5000,
            'casting_mazdori_weight' => 0.15,
            'casting_mazdori_rate' => 5000,
            'male_waste_auto' => 1,
            'wasooli' => 0,
        ]);

        $invoice2 = $invoiceService->create([
            'customer_id' => $customer->id,
            'invoice_date' => Carbon::today()->addDay(),
            'casting_weight' => 3.0,
            'waste_weight' => 0.6,
            'waste_auto' => 1,
            'ratti' => 2.0,
            'ratti_rate' => 0.5,
            'rp_rate' => 65000,
            'rp_mazdori_weight' => 0.20,
            'rp_mazdori_rate' => 5000,
            'casting_mazdori_weight' => 0.10,
            'casting_mazdori_rate' => 5000,
            'male_waste_auto' => 1,
            'wasooli' => 0,
        ]);

        $invoice1->refresh();
        $invoice2->refresh();
        $inventory = Inventory::first();
        $this->assertNotNull($inventory);

        $this->assertSame($invoice1->remaining_balance, $invoice2->previous_balance);

        $this->assertSame(
            round($invoice1->effective_gold + $invoice2->effective_gold, 3),
            round($inventory->given_invoices, 3)
        );

        $this->assertSame(
            round($inventory->opening_balance + $inventory->received - $inventory->given_invoices, 3),
            round($inventory->closing_balance, 3)
        );

        $updatedInvoice1 = $invoiceService->update($invoice1, [
            'customer_id' => $customer->id,
            'invoice_date' => Carbon::today(),
            'casting_weight' => 2.5,
            'waste_weight' => 0.5,
            'waste_auto' => 1,
            'ratti' => 2.0,
            'ratti_rate' => 0.5,
            'rp_rate' => 65620,
            'rp_mazdori_weight' => 0.15,
            'rp_mazdori_rate' => 5000,
            'casting_mazdori_weight' => 0.15,
            'casting_mazdori_rate' => 5000,
            'male_waste_auto' => 1,
            'wasooli' => 0,
        ]);

        $updatedInvoice1->refresh();
        $invoice2->refresh();
        $inventory->refresh();

        $this->assertSame($updatedInvoice1->remaining_balance, $invoice2->previous_balance);
        $this->assertSame(
            round($updatedInvoice1->effective_gold + $invoice2->effective_gold, 3),
            round($inventory->given_invoices, 3)
        );

        $receiveGross = 0.8;
        $receiveRatti = 2.5;
        $receiveKhalis = round($calcService->convertToKhalis($receiveGross, $receiveRatti), 3);

        $receive = InvoiceReceive::create([
            'invoice_id' => $invoice2->id,
            'description' => 'Receive gold',
            'gross_weight' => $receiveGross,
            'ratti_impurity' => $receiveRatti,
            'khalis_weight' => $receiveKhalis,
        ]);

        $inventory->refresh();
        $this->assertSame($receiveKhalis, round($inventory->received, 3));

        $newReceiveGross = 1.0;
        $newReceiveKhalis = round($calcService->convertToKhalis($newReceiveGross, $receiveRatti), 3);
        $receive->update([
            'gross_weight' => $newReceiveGross,
            'khalis_weight' => $newReceiveKhalis,
        ]);

        $inventory->refresh();
        $this->assertSame(
            round($newReceiveKhalis, 3),
            round($inventory->received, 3)
        );

        $receive->delete();
        $inventory->refresh();
        $this->assertSame(0.000, round($inventory->received, 3));

        $receiptGross = 1.2;
        $receiptRatti = 2.0;
        $receiptKhalis = round($calcService->convertToKhalis($receiptGross, $receiptRatti), 3);

        $receipt = GoldReceipt::create([
            'receipt_no' => 'RCV-00002',
            'customer_id' => $customer->id,
            'receipt_type' => 'customer',
            'receipt_date' => Carbon::today(),
            'remarks' => 'Receipt gold',
            'total_gross_weight' => 0,
            'total_khalis_weight' => 0,
            'created_by' => auth()->id(),
        ]);

        GoldReceiptItem::create([
            'receipt_id' => $receipt->id,
            'description' => 'Receipt item',
            'gross_weight' => $receiptGross,
            'ratti_impurity' => $receiptRatti,
            'khalis_weight' => $receiptKhalis,
        ]);

        $receipt->update([
            'total_gross_weight' => $receiptGross,
            'total_khalis_weight' => $receiptKhalis,
        ]);

        $inventory->refresh();
        $this->assertSame($receiptKhalis, round($inventory->received, 3));

        $receipt->update(['total_khalis_weight' => 0.5]);
        $inventory->refresh();
        $this->assertSame(0.5, round($inventory->received, 3));

        $receipt->delete();
        $inventory->refresh();
        $this->assertSame(0.000, round($inventory->received, 3));

        $invoiceService->delete($updatedInvoice1);
        $invoice2->refresh();
        $inventory->refresh();

        $this->assertSame(5.000, round($invoice2->previous_balance, 3));
        $this->assertSame(
            round($inventory->opening_balance - $inventory->given_invoices, 3),
            round($inventory->closing_balance, 3)
        );

        $stats = $ledgerService->getDashboardStats();
        $this->assertSame(1, $stats['total_invoices']);
        $this->assertSame($invoice2->effective_gold, round($stats['total_gold_khalis_given'], 3));
        $this->assertSame(10.000, round($stats['total_opening_stock'], 3));
    }
}
