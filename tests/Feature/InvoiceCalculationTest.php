<?php

namespace Tests\Feature;

use App\Services\GoldCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceCalculationTest extends TestCase
{
    use RefreshDatabase;
    public function test_grand_total_calculation_uses_effective_gold_times_rp_rate(): void
    {
        $service = new GoldCalculationService();

        $result = $service->calculate([
            'casting_weight' => 2.5,
            'waste_weight' => 0.5,
            'ratti' => 2.0,
            'ratti_rate' => 0.5,
            'rp_rate' => 65620,
            'rp_mazdori_weight' => 0.12,
            'rp_mazdori_rate' => 5000,
            'casting_mazdori_weight' => 0.15,
            'casting_mazdori_rate' => 5000,
            'previous_balance' => 0,
            'wasooli' => 0,
        ]);

        // Grand total should be effective gold (grams) × rp_rate (monetary)
        $expectedGrand = round($result['effective_gold'] * 65620, 2);
        $this->assertSame($expectedGrand, $result['grand_total']);
    }

    public function test_mazdori_rate_changes_do_not_affect_grand_total(): void
    {
        $service = new GoldCalculationService();

        $baseInputs = [
            'casting_weight' => 2.5,
            'waste_weight' => 0.5,
            'ratti' => 2.0,
            'ratti_rate' => 0.5,
            'rp_rate' => 65620,
            'rp_mazdori_weight' => 0.12,
            'casting_mazdori_weight' => 0.15,
            'previous_balance' => 0,
            'wasooli' => 0,
        ];

        $first = $service->calculate(array_merge($baseInputs, ['rp_mazdori_rate' => 5000, 'casting_mazdori_rate' => 5000]));
        $second = $service->calculate(array_merge($baseInputs, ['rp_mazdori_rate' => 9999, 'casting_mazdori_rate' => 9999]));

        $this->assertSame($first['grand_total'], $second['grand_total']);
        $this->assertNotSame($first['rp_mazdori_amount'], $second['rp_mazdori_amount']);
        $this->assertNotSame($first['casting_mazdori_amount'], $second['casting_mazdori_amount']);
    }
}
