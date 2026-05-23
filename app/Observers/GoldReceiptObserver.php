<?php

namespace App\Observers;

use App\Models\GoldReceipt;
use App\Models\Inventory;

class GoldReceiptObserver
{
    /**
     * Handle receipt creation - update inventory and ledger
     */
    public function created(GoldReceipt $receipt): void
    {
        $this->syncInventoryReceived($receipt->total_khalis_weight, 'add');
    }

    /**
     * Handle receipt update - adjust inventory for weight delta
     */
    public function updated(GoldReceipt $receipt): void
    {
        $originalWeight = (float) $receipt->getOriginal('total_khalis_weight');
        $currentWeight = $receipt->total_khalis_weight;

        if ($originalWeight != $currentWeight) {
            $delta = $currentWeight - $originalWeight;
            $this->syncInventoryReceived($delta, 'add');
        }
    }

    /**
     * Handle receipt deletion - update inventory
     */
    public function deleted(GoldReceipt $receipt): void
    {
        $this->syncInventoryReceived($receipt->total_khalis_weight, 'subtract');
    }

    /**
     * Sync receipt weight to inventory.received and recalculate closing balance
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

        // Recalculate closing balance: opening + received - given
        $inventory->closing_balance = $inventory->opening_balance + $inventory->received - $inventory->given_invoices;
        $inventory->save();
    }
}
