<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Inventory;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        $this->adjustInventory($invoice->gold_khalis);
    }

    public function updated(Invoice $invoice): void
    {
        $originalActive = $invoice->getOriginal('status') === 'active';
        $currentActive = $invoice->status === 'active';
        $originalGold = (float) $invoice->getOriginal('gold_khalis');
        $currentGold = $invoice->gold_khalis;

        if ($originalActive && $currentActive) {
            $delta = $currentGold - $originalGold;
        } elseif ($originalActive && ! $currentActive) {
            $delta = -$originalGold;
        } elseif (! $originalActive && $currentActive) {
            $delta = $currentGold;
        } else {
            $delta = 0;
        }

        if ($delta !== 0.0) {
            $this->adjustInventory($delta);
        }
    }

    public function deleted(Invoice $invoice): void
    {
        if ($invoice->status === 'active') {
            $this->adjustInventory(-$invoice->gold_khalis);
        }
    }

    private function adjustInventory(float $goldKhalisDelta): void
    {
        $inventory = Inventory::first();

        if (! $inventory) {
            $inventory = Inventory::create([
                'opening_balance' => 0,
                'received' => 0,
                'given_invoices' => 0,
                'closing_balance' => 0,
            ]);
        }

        $inventory->given_invoices = max(0, $inventory->given_invoices + $goldKhalisDelta);
        $inventory->closing_balance = $inventory->opening_balance + $inventory->received - $inventory->given_invoices;
        $inventory->save();
    }
}
