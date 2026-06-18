<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\GstService;

class GstServiceTest extends TestCase
{
    private GstService $gstService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gstService = new GstService();
    }

    // ─── calculateGst Tests ─────────────────────────────────────────────────

    public function test_calculate_gst_5_percent_intra_state(): void
    {
        $result = $this->gstService->calculateGst(1000, 5, 'cgst_sgst');

        $this->assertEquals(25.00, $result['cgst_amount']);
        $this->assertEquals(25.00, $result['sgst_amount']);
        $this->assertEquals(0, $result['igst_amount']);
        $this->assertEquals(50.00, $result['total_tax']);
        $this->assertEquals('cgst_sgst', $result['gst_type']);
    }

    public function test_calculate_gst_12_percent_inter_state(): void
    {
        $result = $this->gstService->calculateGst(1000, 12, 'igst');

        $this->assertEquals(0, $result['cgst_amount']);
        $this->assertEquals(0, $result['sgst_amount']);
        $this->assertEquals(120.00, $result['igst_amount']);
        $this->assertEquals(120.00, $result['total_tax']);
        $this->assertEquals('igst', $result['gst_type']);
    }

    public function test_calculate_gst_odd_amount_split(): void
    {
        $result = $this->gstService->calculateGst(999, 5, 'cgst_sgst');

        // total_tax = round(999 * 5 / 100, 2) = 49.95
        $this->assertEquals(49.95, $result['total_tax']);
        $this->assertEquals(0, $result['igst_amount']);
        $this->assertEquals('cgst_sgst', $result['gst_type']);

        // CGST + SGST must sum to total_tax
        $this->assertEquals(49.95, $result['cgst_amount'] + $result['sgst_amount']);

        // The split should be within 0.01 of each other (one gets the extra paisa)
        $this->assertLessThanOrEqual(0.01, abs($result['cgst_amount'] - $result['sgst_amount']));
    }

    // ─── determineGstType Tests ─────────────────────────────────────────────

    public function test_determine_gst_type_same_state(): void
    {
        $result = $this->gstService->determineGstType('Gujarat', 'Gujarat');

        $this->assertEquals('cgst_sgst', $result);
    }

    public function test_determine_gst_type_different_state(): void
    {
        $result = $this->gstService->determineGstType('Gujarat', 'Maharashtra');

        $this->assertEquals('igst', $result);
    }

    public function test_determine_gst_type_case_insensitive(): void
    {
        $result = $this->gstService->determineGstType('gujarat', 'GUJARAT');

        $this->assertEquals('cgst_sgst', $result);
    }

    // ─── resolveFinancialYearDates Tests ────────────────────────────────────

    public function test_resolve_financial_year_dates_returns_valid_range(): void
    {
        $result = $this->gstService->resolveFinancialYearDates();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        [$fromDate, $toDate] = $result;

        // Both should be valid Y-m-d dates
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $fromDate);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $toDate);

        // From date should be April 1
        $this->assertStringEndsWith('-04-01', $fromDate);

        // To date should be March 31
        $this->assertStringEndsWith('-03-31', $toDate);

        // To date year should be exactly one more than from date year
        $fromYear = (int) substr($fromDate, 0, 4);
        $toYear = (int) substr($toDate, 0, 4);
        $this->assertEquals($fromYear + 1, $toYear);
    }
}
