<?php

namespace App\Observers;

use App\Models\InvoiceReceive;
use App\Models\Inventory;

class InvoiceReceiveObserver
{
    /**
     * Handle invoice receive creation - update inventory.received
     */
    public function created(InvoiceReceive $receive): void
    {
        $this->syncInventoryReceived($receive->khalis_weight, 'add');
    }

    /**
     * Handle invoice receive update - adjust inventory for weight delta
     */
    public function updated(InvoiceReceive $receive): void
    {
        $originalWeight = (float) $receive->getOriginal('khalis_weight');
        $currentWeight = $receive->khalis_weight;

        if ($originalWeight != $currentWeight) {
            $delta = $currentWeight - $originalWeight;
            $this->syncInventoryReceived($delta, 'add');
        }
    }

    /**
     * Handle invoice receive deletion - update inventory
     */
    public function deleted(InvoiceReceive $receive): void
    {
        $this->syncInventoryReceived($receive->khalis_weight, 'subtract');
    }

    /**
     * Sync invoice receive weight to inventory.received
     */
    private function syncInventoryReceived(float $khalisWeightDelta, string $operation = 'add'): void
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

        // Update received total
        if ($operation === 'add') {
            $inventory->received = max(0, $inventory->received + $khalisWeightDelta);
        } else {
            $inventory->received = max(0, $inventory->received - $khalisWeightDelta);
        }

        // Recalculate closing balance
        $inventory->closing_balance = $inventory->opening_balance + $inventory->received - $inventory->given_invoices;
        $inventory->save();
    }
}
