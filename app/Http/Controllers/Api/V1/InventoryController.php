<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Get current inventory
     */
    public function show(): JsonResponse
    {
        $inventory = Inventory::first() ?? Inventory::create();

        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }

    /**
     * Update inventory
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opening_balance' => 'nullable|numeric|min:0',
            'received' => 'nullable|numeric|min:0',
            'period_label' => 'nullable|string|max:100',
        ]);

        $inventory = Inventory::first() ?? Inventory::create();

        $inventory->update([
            'opening_balance' => $validated['opening_balance'] ?? $inventory->opening_balance,
            'received' => $validated['received'] ?? $inventory->received,
            'period_label' => $validated['period_label'] ?? $inventory->period_label,
            'updated_by' => auth()->id(),
        ]);

        // Recalculate closing balance
        $inventory->closing_balance = $inventory->opening_balance + $inventory->received - $inventory->given_invoices;
        $inventory->save();

        return response()->json([
            'success' => true,
            'message' => 'Inventory updated successfully',
            'data' => $inventory,
        ]);
    }
}
