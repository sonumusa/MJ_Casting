<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;

class GoldCalculationService
{
    /**
     * Calculate all invoice fields from input
     */
    public function calculate(array $input): array
    {
        $rattiTiers = \App\Models\Setting::getSetting('ratti_tiers', []);

        // 1. Inputs
        $castingWeight = $this->parseDecimal($input['casting_weight'] ?? 0);
        $wasteWeight = $this->parseDecimal($input['waste_weight'] ?? 0);
        $rattiRate = $this->parseDecimal($input['ratti_rate'] ?? 0);
        $rpRate = $this->parseDecimal($input['rp_rate'] ?? 0);
        $rpMazdoriWeight = $this->parseDecimal($input['rp_mazdori_weight'] ?? 0);
        $castingMazdoriWeight = $this->parseDecimal($input['casting_mazdori_weight'] ?? 0);
        $wasooli = $this->parseDecimal($input['wasooli'] ?? 0);
        $previousBalance = $this->parseDecimal($input['previous_balance'] ?? 0);
        $totalReceivedKhalis = $this->parseDecimal($input['total_received_khalis'] ?? 0);

        if (!empty($input['waste_auto'])) {
            $wasteRate = $this->parseDecimal(
                $input['waste_rate'] ?? \App\Models\Setting::getSetting('default_waste_rate', 0)
            );
            $wasteWeight = round($castingWeight / 10 * $wasteRate, 3);
        }

        // Step 1: total_weight = round(casting_weight + waste_weight, 3)
        $totalWeight = round($castingWeight + $wasteWeight, 3);

        // Step 2: ratti auto-calculation
        $ratti = $this->parseDecimal($input['ratti'] ?? 0);
        $rattiTierApplied = '';
        if (!empty($input['ratti_auto'])) {
            $ratti = 0.5; // Default fallback
            $rattiTierApplied = 'Default > last tier';
            foreach ($rattiTiers as $tier) {
                if ($totalWeight <= $tier['max_weight']) {
                    $ratti = (float) $tier['ratti'];
                    $rattiTierApplied = "Total <= {$tier['max_weight']}";
                    break;
                }
            }
        }

        // Step 3: male_waste = round(total_weight / 96 * ratti, 3)
        $maleWaste = $this->parseDecimal($input['male_waste'] ?? 0);
        if (!empty($input['male_waste_auto'])) {
            $maleWaste = round($totalWeight / 96 * $ratti, 3);
        }

        // Step 4: gold_khalis = round(total_weight - male_waste, 3)
        $goldKhalis = round($totalWeight - $maleWaste, 3);

        // Step 5: rp_amount = round(gold_khalis × rp_rate, 2) [DISPLAY ONLY]
        $rpAmount = round($goldKhalis * $rpRate, 2);

        // Step 6: rp_mazdori_amount = round(rp_mazdori_weight × rp_mazdori_rate, 2) [DISPLAY ONLY]
        $rpMazdoriRate = $this->parseDecimal($input['rp_mazdori_rate'] ?? 0);
        $rpMazdoriAmount = round($rpMazdoriWeight * $rpMazdoriRate, 2);

        // Step 7: casting_mazdori_amount = round(casting_mazdori_weight × casting_mazdori_rate, 2) [DISPLAY ONLY]
        $castingMazdoriRate = $this->parseDecimal($input['casting_mazdori_rate'] ?? 0);
        $castingMazdoriAmount = round($castingMazdoriWeight * $castingMazdoriRate, 2);

        // Step 8: effective_gold = round(gold_khalis + rp_mazdori_weight + casting_mazdori_weight, 3)
        $effectiveGold = round($goldKhalis + $rpMazdoriWeight + $castingMazdoriWeight, 3);

        // Step 9: grand_total = round(effective_gold, 3) — grams total only
        $grandTotal = round($effectiveGold, 3);

        // Step 10: remaining_balance = round(previous_balance + grand_total - wasooli - total_received_khalis, 3)
        // If party gives gold (received), it reduces their balance
        $remainingBalance = round($previousBalance + $effectiveGold - $wasooli - $totalReceivedKhalis, 3);

        return [
            'total_weight' => $totalWeight,
            'ratti' => $ratti,
            'ratti_auto' => !empty($input['ratti_auto']),
            'ratti_tier_applied' => $rattiTierApplied,
            'male_waste' => $maleWaste,
            'male_waste_auto' => !empty($input['male_waste_auto']),
            'gold_khalis' => $goldKhalis,
            'total_received_khalis' => $totalReceivedKhalis,
            'rp_amount' => $rpAmount,
            'rp_mazdori_amount' => $rpMazdoriAmount,
            'casting_mazdori_amount' => $castingMazdoriAmount,
            'effective_gold' => $effectiveGold,
            'grand_total' => $grandTotal,
            'remaining_balance' => $remainingBalance,
            'waste_weight' => $wasteWeight,
            'waste_auto' => !empty($input['waste_auto']),
        ];
    }

    /**
     * Convert impure gold gross weight to khalis pure gold using ratti formula
     * Formula: gross_weight - (gross_weight / 96 * ratti_impurity)
     */
    public function convertToKhalis(float $grossWeight, float $rattiImpurity): float
    {
        if ($grossWeight <= 0) return 0;
        return round($grossWeight - (($grossWeight / 96) * $rattiImpurity), 3);
    }

    /**
     * Calculate grand total
     */
    public function calculateGrandTotal(
        float $effectiveGold,
        float $rpRate
    ): float {
        return round($effectiveGold, 3);
    }

    /**
     * Calculate effective gold
     */
    public function calculateEffectiveGold(
        float $goldKhalis,
        float $rpMazdoriWeight,
        float $castingMazdoriWeight
    ): float {
        return $goldKhalis + $rpMazdoriWeight + $castingMazdoriWeight;
    }

    /**
     * Calculate male waste (ratti deduction)
     */
    public function calculateMaleWaste(float $totalWeight, float $ratti, float $rattiRate): float
    {
        return round($totalWeight / 96 * $ratti, 3);
    }

    /**
     * Calculate gold khalis
     */
    public function calculateGoldKhalis(float $totalWeight, float $maleWaste): float
    {
        return $totalWeight - $maleWaste;
    }

    /**
     * Calculate remaining balance
     */
    public function calculateRemainingBalance(float $previousBalance, float $effectiveGold, float $wasooli, float $totalReceivedKhalis = 0): float
    {
        return $previousBalance + $effectiveGold - $wasooli - $totalReceivedKhalis;
    }

    /**
     * Recalculate balance chain for a customer's invoices
     * Used when an invoice is edited or deleted
     */
    public function recalculateChain($customer, $fromInvoiceId = null): void
    {
        $invoices = $customer->invoices()
            ->where('status', 'active')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        $runningBalance = $customer->opening_balance;

        foreach ($invoices as $invoice) {
            $invoice->update([
                'previous_balance' => $runningBalance,
                'remaining_balance' => $runningBalance + $invoice->effective_gold - $invoice->wasooli - $invoice->total_received_khalis,
            ]);

            $runningBalance = $invoice->remaining_balance;
        }
    }

    /**
     * Get mazdori amounts for display
     */
    public function getMazdoriAmounts(array $input): array
    {
        $rpMazdoriWeight = $this->parseDecimal($input['rp_mazdori_weight'] ?? 0);
        $rpMazdoriRate = $this->parseDecimal($input['rp_mazdori_rate'] ?? 0);
        $castingMazdoriWeight = $this->parseDecimal($input['casting_mazdori_weight'] ?? 0);
        $castingMazdoriRate = $this->parseDecimal($input['casting_mazdori_rate'] ?? 0);

        return [
            'rp_mazdori_amount' => $this->multiply($rpMazdoriWeight, $rpMazdoriRate),
            'casting_mazdori_amount' => $this->multiply($castingMazdoriWeight, $castingMazdoriRate),
        ];
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
     * Helper: Add two decimals
     */
    private function add(float $a, float $b): float
    {
        return round($a + $b, 3);
    }

    /**
     * Helper: Subtract two decimals
     */
    private function subtract(float $a, float $b): float
    {
        return round($a - $b, 3);
    }

    /**
     * Helper: Multiply two decimals
     */
    public function multiply(float $a, float $b): float
    {
        return round($a * $b, 2);
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
