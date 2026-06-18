<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\GstService;

/**
 * Property-based tests for GstService.
 *
 * Each test generates randomized inputs and verifies that universal
 * correctness properties hold across all generated cases.
 * These tests exercise pure calculation logic — no database needed.
 */
class GstPropertyTest extends TestCase
{
    private GstService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GstService();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 1: Tax Amount Mutual Exclusivity
    // Validates: Requirements 1.5, 1.6, 4.2, 4.3
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 1: For any GST calculation, IF gst_type is 'igst' THEN cgst=0 AND sgst=0
     * AND igst=total_tax. IF gst_type is 'cgst_sgst' THEN igst=0 AND cgst+sgst=total_tax.
     *
     * **Validates: Requirements 1.5, 1.6, 4.2, 4.3**
     */
    public function test_property1_tax_amount_mutual_exclusivity(): void
    {
        $gstTypes = ['igst', 'cgst_sgst'];

        for ($i = 0; $i < 50; $i++) {
            $taxableValue = round(mt_rand(1, 9999900) / 100, 2); // 0.01 to 99999.00
            $gstType = $gstTypes[array_rand($gstTypes)];
            $rate = GstService::VALID_RATES[array_rand(GstService::VALID_RATES)];

            $result = $this->service->calculateGst($taxableValue, $rate, $gstType);

            if ($result['gst_type'] === 'igst') {
                $this->assertEquals(
                    0,
                    $result['cgst_amount'],
                    "Iteration $i: IGST type should have cgst_amount=0 (taxableValue=$taxableValue, rate=$rate)"
                );
                $this->assertEquals(
                    0,
                    $result['sgst_amount'],
                    "Iteration $i: IGST type should have sgst_amount=0 (taxableValue=$taxableValue, rate=$rate)"
                );
                $this->assertEquals(
                    $result['total_tax'],
                    $result['igst_amount'],
                    "Iteration $i: IGST type should have igst_amount=total_tax (taxableValue=$taxableValue, rate=$rate)"
                );
            } elseif ($result['gst_type'] === 'cgst_sgst') {
                $this->assertEquals(
                    0,
                    $result['igst_amount'],
                    "Iteration $i: CGST+SGST type should have igst_amount=0 (taxableValue=$taxableValue, rate=$rate)"
                );
                $this->assertEqualsWithDelta(
                    $result['total_tax'],
                    $result['cgst_amount'] + $result['sgst_amount'],
                    0.01,
                    "Iteration $i: CGST+SGST type should have cgst+sgst=total_tax (taxableValue=$taxableValue, rate=$rate)"
                );
            } else {
                $this->fail("Iteration $i: Unknown gst_type returned: {$result['gst_type']}");
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 2: Tax Calculation Accuracy
    // Validates: Requirements 2.1, 2.2, 2.4
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 2: For any taxable value V and rate R, total_tax SHALL equal round(V × R / 100, 2).
     *
     * **Validates: Requirements 2.1, 2.2, 2.4**
     */
    public function test_property2_tax_calculation_accuracy(): void
    {
        $gstTypes = ['igst', 'cgst_sgst'];

        for ($i = 0; $i < 50; $i++) {
            $taxableValue = round(mt_rand(1, 9999900) / 100, 2); // 0.01 to 99999.00
            $rate = GstService::VALID_RATES[array_rand(GstService::VALID_RATES)];
            $gstType = $gstTypes[array_rand($gstTypes)];

            $expectedTotalTax = round($taxableValue * $rate / 100, 2);

            $result = $this->service->calculateGst($taxableValue, $rate, $gstType);

            $this->assertEqualsWithDelta(
                $expectedTotalTax,
                $result['total_tax'],
                0.001,
                "Iteration $i: total_tax should equal round(value*rate/100, 2) — taxableValue=$taxableValue, rate=$rate, gstType=$gstType"
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 3: CGST/SGST Symmetry
    // Validates: Requirements 1.6, 2.1
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 3: For any intra-state (cgst_sgst) calculation, the absolute difference
     * between cgst_amount and sgst_amount SHALL be at most 0.01 (one paisa rounding).
     *
     * **Validates: Requirements 1.6, 2.1**
     */
    public function test_property3_cgst_sgst_symmetry(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $taxableValue = round(mt_rand(1, 9999900) / 100, 2); // 0.01 to 99999.00
            $rate = GstService::VALID_RATES[array_rand(GstService::VALID_RATES)];

            $result = $this->service->calculateGst($taxableValue, $rate, 'cgst_sgst');

            $difference = round(abs($result['cgst_amount'] - $result['sgst_amount']), 2);

            $this->assertLessThanOrEqual(
                0.01,
                $difference,
                "Iteration $i: |cgst - sgst| should be <= 0.01 — taxableValue=$taxableValue, rate=$rate, cgst={$result['cgst_amount']}, sgst={$result['sgst_amount']}"
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 4: Total Tax Invariant
    // Validates: Requirement 1.4
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 4: For any GST calculation, total_tax SHALL equal cgst_amount + sgst_amount + igst_amount.
     * This holds regardless of gst_type, rate, or taxable value.
     *
     * **Validates: Requirement 1.4**
     */
    public function test_property4_total_tax_invariant(): void
    {
        $gstTypes = ['igst', 'cgst_sgst'];

        for ($i = 0; $i < 50; $i++) {
            $taxableValue = round(mt_rand(1, 9999900) / 100, 2); // 0.01 to 99999.00
            $rate = GstService::VALID_RATES[array_rand(GstService::VALID_RATES)];
            $gstType = $gstTypes[array_rand($gstTypes)];

            $result = $this->service->calculateGst($taxableValue, $rate, $gstType);

            $componentSum = $result['cgst_amount'] + $result['sgst_amount'] + $result['igst_amount'];

            $this->assertEqualsWithDelta(
                $result['total_tax'],
                $componentSum,
                0.001,
                "Iteration $i: cgst+sgst+igst should equal total_tax — taxableValue=$taxableValue, rate=$rate, gstType=$gstType, total_tax={$result['total_tax']}, sum=$componentSum"
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 5: State-Based Type Determination
    // Validates: Requirements 2.1, 2.2, 2.5
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 5: For any pair of states, IF origin equals destination THEN determineGstType
     * SHALL return 'cgst_sgst', ELSE it SHALL return 'igst'. Case-insensitive and trim-safe.
     *
     * **Validates: Requirements 2.1, 2.2, 2.5**
     */
    public function test_property5_state_based_type_determination(): void
    {
        $states = ['Gujarat', 'Maharashtra', 'Rajasthan', 'Madhya Pradesh', 'Karnataka', 'Tamil Nadu', 'Delhi', 'Punjab'];

        for ($i = 0; $i < 50; $i++) {
            $originIndex = array_rand($states);
            $destinationIndex = array_rand($states);
            $origin = $states[$originIndex];
            $destination = $states[$destinationIndex];

            // Apply random case variations to test case-insensitivity
            $caseVariations = ['strtolower', 'strtoupper', 'ucfirst'];
            $originVariant = call_user_func($caseVariations[array_rand($caseVariations)], $origin);
            $destinationVariant = call_user_func($caseVariations[array_rand($caseVariations)], $destination);

            $result = $this->service->determineGstType($originVariant, $destinationVariant);

            if ($originIndex === $destinationIndex) {
                // Same state → intra-state
                $this->assertEquals(
                    'cgst_sgst',
                    $result,
                    "Iteration $i: Same state ('$originVariant', '$destinationVariant') should return 'cgst_sgst'"
                );
            } else {
                // Different states → inter-state
                $this->assertEquals(
                    'igst',
                    $result,
                    "Iteration $i: Different states ('$originVariant', '$destinationVariant') should return 'igst'"
                );
            }
        }

        // Additional: verify determinism and idempotence
        for ($i = 0; $i < 10; $i++) {
            $origin = $states[array_rand($states)];
            $destination = $states[array_rand($states)];

            $result1 = $this->service->determineGstType($origin, $destination);
            $result2 = $this->service->determineGstType($origin, $destination);

            $this->assertEquals(
                $result1,
                $result2,
                "determineGstType should be deterministic for ('$origin', '$destination')"
            );
        }
    }
}
