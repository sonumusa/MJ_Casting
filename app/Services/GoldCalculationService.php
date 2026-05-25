<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;

class GoldCalculationService
{
    /**
     * Calculate all invoice fields from input
     * 
     * Calculation Flow:
     * 1. Waste Weight = Casting Weight ÷ 10 × Ratti Deduction Rate
     * 2. Total Weight = Casting Weight + Waste Weight
     * 3. Male Waste = Total Weight ÷ 96 × Ratti
     * 4. Gold Khalis = Total Weight - Male Waste
     * 5. Effective Gold = Gold Khalis + RP Mazdori Weight + Casting Mazdori Weight
     * 6. Grand Total = Effective Gold (in grams)
     * 7. Remaining Balance = Previous Balance + Effective Gold - Wasooli - Total Received Khalis
     */
    public function calculate(array $input): array
    {
        // Parse inputs
        $castingWeight = $this->parseDecimal($input['casting_weight'] ?? 0);
        $ratti = $this->parseDecimal($input['ratti'] ?? 0);
        $rattiRate = $this->parseDecimal($input['ratti_rate'] ?? 0);
        $rpRate = $this->parseDecimal($input['rp_rate'] ?? 0);
        $rpMazdoriWeight = $this->parseDecimal($input['rp_mazdori_weight'] ?? 0);
        $rpMazdoriRate = $this->parseDecimal($input['rp_mazdori_rate'] ?? 0);
        $castingMazdoriWeight = $this->parseDecimal($input['casting_mazdori_weight'] ?? 0);
        $castingMazdoriRate = $this->parseDecimal($input['casting_mazdori_rate'] ?? 0);
        $wasooli = $this->parseDecimal($input['wasooli'] ?? 0);
        $previousBalance = $this->parseDecimal($input['previous_balance'] ?? 0);
        $totalReceivedKhalis = $this->parseDecimal($input['total_received_khalis'] ?? 0);

        // Step 1: Calculate Waste Weight
        // Formula: Casting Weight ÷ 10 × Ratti Deduction Rate (g)
        $wasteWeight = 0;
        if ($castingWeight > 0 && $rattiRate > 0) {
            $wasteWeight = round(($castingWeight / 10) * $rattiRate, 3);
        }

        // Step 2: Calculate Total Weight
        // Formula: Casting Weight + Waste Weight
        $totalWeight = round($castingWeight + $wasteWeight, 3);

        // Step 3: Calculate Male Waste
        // Formula: Total Weight ÷ 96 × Ratti
        $maleWaste = 0;
        if ($totalWeight > 0 && $ratti > 0) {
            $maleWaste = round(($totalWeight / 96) * $ratti, 3);
        }

        // Step 4: Calculate Gold Khalis
        // Formula: Total Weight - Male Waste
        $goldKhalis = round($totalWeight - $maleWaste, 3);

        // Step 5: Calculate RP Amount (Display Only - for monetary reference)
        $rpAmount = round($goldKhalis * $rpRate, 2);

        // Step 6: Calculate RP Mazdori Amount (Display Only)
        $rpMazdoriAmount = round($rpMazdoriWeight * $rpMazdoriRate, 2);

        // Step 7: Calculate Casting Mazdori Amount (Display Only)
        $castingMazdoriAmount = round($castingMazdoriWeight * $castingMazdoriRate, 2);

        // Step 8: Calculate Effective Gold (Total Gold Given to Party)
        // Formula: Gold Khalis + RP Mazdori Weight + Casting Mazdori Weight
        $effectiveGold = round($goldKhalis + $rpMazdoriWeight + $castingMazdoriWeight, 3);

        // Step 9: Grand Total (Total Gold Out in grams)
        $grandTotal = round($effectiveGold, 3);

        // Step 10: Calculate Remaining Balance
        // Formula: Previous Balance + Effective Gold - Wasooli - Total Received Khalis
        $remainingBalance = round(
            $previousBalance + $effectiveGold - $wasooli - $totalReceivedKhalis, 
            3
        );

        return [
            // Input values (preserved)
            'casting_weight' => $castingWeight,
            'ratti' => $ratti,
            'ratti_rate' => $rattiRate,
            'rp_rate' => $rpRate,
            'rp_mazdori_weight' => $rpMazdoriWeight,
            'rp_mazdori_rate' => $rpMazdoriRate,
            'casting_mazdori_weight' => $castingMazdoriWeight,
            'casting_mazdori_rate' => $castingMazdoriRate,
            'wasooli' => $wasooli,
            'previous_balance' => $previousBalance,
            'total_received_khalis' => $totalReceivedKhalis,
            
            // Calculated values
            'waste_weight' => $wasteWeight,
            'total_weight' => $totalWeight,
            'male_waste' => $maleWaste,
            'gold_khalis' => $goldKhalis,
            'rp_amount' => $rpAmount,
            'rp_mazdori_amount' => $rpMazdoriAmount,
            'casting_mazdori_amount' => $castingMazdoriAmount,
            'effective_gold' => $effectiveGold,
            'grand_total' => $grandTotal,
            'remaining_balance' => $remainingBalance,
        ];
    }

    /**
     * Convert impure gold gross weight to khalis pure gold using ratti formula
     * Formula: gross_weight - (gross_weight ÷ 96 × ratti_impurity)
     * 
     * @param float $grossWeight Total weight including impurities
     * @param float $rattiImpurity Ratti impurity level
     * @return float Pure gold weight (khalis)
     */
    public function convertToKhalis(float $grossWeight, float $rattiImpurity): float
    {
        if ($grossWeight <= 0) {
            return 0;
        }
        
        $khalis = $grossWeight - (($grossWeight / 96) * $rattiImpurity);
        return round($khalis, 3);
    }

    /**
     * Recalculate balance chain for a customer's invoices
     * Used when an invoice is edited or deleted
     * 
     * @param Customer $customer
     * @param int|null $fromInvoiceId Start recalculation from this invoice (optional)
     */
    public function recalculateChain(Customer $customer, ?int $fromInvoiceId = null): void
    {
        // Get all active invoices for customer in chronological order
        $invoices = $customer->invoices()
            ->where('status', 'active')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        // Start with customer's opening balance
        $runningBalance = $customer->opening_balance;

        foreach ($invoices as $invoice) {
            // Update previous balance
            $invoice->previous_balance = $runningBalance;

            // Recalculate remaining balance
            // Formula: Previous Balance + Effective Gold - Wasooli - Total Received Khalis
            $invoice->remaining_balance = round(
                $runningBalance 
                + $invoice->effective_gold 
                - $invoice->wasooli 
                - $invoice->total_received_khalis,
                3
            );

            // Save without triggering events
            $invoice->saveQuietly();

            // Update running balance for next invoice
            $runningBalance = $invoice->remaining_balance;
        }
    }

    /**
     * Helper: Parse decimal value
     */
    private function parseDecimal($value): float
    {
        if (is_null($value) || $value === '') {
            return 0;
        }
        return (float) $value;
    }

    /**
     * Format weight for display (3 decimals)
     */
    public function formatWeight($value): string
    {
        return number_format((float) $value, 3, '.', ',') . ' g';
    }

    /**
     * Format amount for display (2 decimals, currency)
     */
    public function formatAmount($value): string
    {
        return 'Rs. ' . number_format((float) $value, 2, '.', ',');
    }
}