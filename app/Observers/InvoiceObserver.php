<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Inventory;

class InvoiceObserver
{
    /**
     * Handle invoice creation - update inventory with effective gold given
     */
    public function created(Invoice $invoice): void
    {
        if ($invoice->status === 'active') {
            $this->adjustInventory($invoice->effective_gold, 'add');
        }
    }

    /**
     * Handle invoice update - adjust inventory for effective gold delta
     */
    public function updated(Invoice $invoice): void
    {
        $originalActive = $invoice->getOriginal('status') === 'active';
        $currentActive = $invoice->status === 'active';
        $originalEffectiveGold = (float) $invoice->getOriginal('effective_gold');
        $currentEffectiveGold = $invoice->effective_gold;

        if ($originalActive && $currentActive) {
            $delta = $currentEffectiveGold - $originalEffectiveGold;
        } elseif ($originalActive && !$currentActive) {
            $delta = -$originalEffectiveGold;
        } elseif (!$originalActive && $currentActive) {
            $delta = $currentEffectiveGold;
        } else {
            $delta = 0;
        }

        if ($delta !== 0.0) {
            $this->adjustInventory($delta, 'add');
        }
    }

    /**
     * Handle invoice deletion - remove from inventory
     */
    public function deleted(Invoice $invoice): void
    {
        if ($invoice->status === 'active') {
            $this->adjustInventory($invoice->effective_gold, 'subtract');
        }
    }

    /**
     * Update inventory.given_invoices with delta
     * effective_gold is the total gold leaving inventory (includes mazdori weights)
     */
    private function adjustInventory(float $effectiveGoldDelta, string $operation = 'add'): void
    {
        $inventory = Inventory::first();

        if (!$inventory) {
            $inventory = Inventory::create([
                'opening_balance' => 0,
                'received' => 0,
                'given_invoices' => 0,
                'closing_balance' => 0,
            ]);
        }

        // Update given_invoices total
        if ($operation === 'add') {
            $inventory->given_invoices = max(0, $inventory->given_invoices + $effectiveGoldDelta);
        } else {
            $inventory->given_invoices = max(0, $inventory->given_invoices - $effectiveGoldDelta);
        }

        // Recalculate closing balance: opening + received - given
        $inventory->closing_balance = $inventory->opening_balance + $inventory->received - $inventory->given_invoices;
        $inventory->save();
    }
}
