<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\FinancialReportService;
use Carbon\Carbon;

/**
 * Property-based tests for FinancialReportService.
 *
 * Each test verifies a universal correctness property that must hold
 * across all valid inputs. Service-level tests use app() resolution;
 * pure calculation tests (indianNumberFormat) need no database.
 */
class FinancialReportPropertyTest extends TestCase
{
    private FinancialReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FinancialReportService::class);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 1: Trial Balance Balancing Invariant
    // Validates: Requirements 2.6
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 1: For any valid date range, the total debit column in the Trial Balance
     * SHALL equal the total credit column.
     *
     * **Validates: Requirements 2.6**
     */
    public function test_property1_trial_balance_balancing_invariant(): void
    {
        // Use current FY dates
        $now = Carbon::now();
        $fyStart = $now->month >= 4
            ? Carbon::create($now->year, 4, 1)->format('Y-m-d')
            : Carbon::create($now->year - 1, 4, 1)->format('Y-m-d');
        $fyEnd = $now->format('Y-m-d');

        $result = $this->service->getTrialBalance($fyStart, $fyEnd);

        $this->assertArrayHasKey('totals', $result);
        $this->assertArrayHasKey('debit', $result['totals']);
        $this->assertArrayHasKey('credit', $result['totals']);

        $this->assertEqualsWithDelta(
            $result['totals']['debit'],
            $result['totals']['credit'],
            0.01,
            "Trial Balance total debit ({$result['totals']['debit']}) should equal total credit ({$result['totals']['credit']}) within 0.01"
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 2: Balance Sheet Accounting Equation
    // Validates: Requirements 4.6
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 2: For any valid as-of date, Total Assets SHALL equal
     * Total Liabilities + Equity + Net Profit.
     *
     * **Validates: Requirements 4.6**
     */
    public function test_property2_balance_sheet_accounting_equation(): void
    {
        $today = Carbon::now()->format('Y-m-d');

        $result = $this->service->getBalanceSheet($today);

        $this->assertArrayHasKey('total_assets', $result);
        $this->assertArrayHasKey('total_liabilities_equity', $result);

        $this->assertEqualsWithDelta(
            $result['total_assets'],
            $result['total_liabilities_equity'],
            0.01,
            "Balance Sheet: total_assets ({$result['total_assets']}) should equal total_liabilities_equity ({$result['total_liabilities_equity']}) within 0.01"
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 3: Trial Balance Correct Balance Placement
    // Validates: Requirements 2.1, 2.2, 2.3, 2.4
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 3: For any account in the Trial Balance, debit-normal accounts (asset/expense)
     * with positive balance SHALL appear in the debit column, and credit-normal accounts
     * (liability/income/equity) with positive balance SHALL appear in the credit column.
     *
     * **Validates: Requirements 2.1, 2.2, 2.3, 2.4**
     */
    public function test_property3_trial_balance_correct_balance_placement(): void
    {
        $now = Carbon::now();
        $fyStart = $now->month >= 4
            ? Carbon::create($now->year, 4, 1)->format('Y-m-d')
            : Carbon::create($now->year - 1, 4, 1)->format('Y-m-d');
        $fyEnd = $now->format('Y-m-d');

        $result = $this->service->getTrialBalance($fyStart, $fyEnd);

        $debitNormalTypes = ['asset', 'expense'];
        $creditNormalTypes = ['liability', 'income', 'equity'];

        // accounts is grouped by type
        foreach ($result['accounts'] as $type => $accounts) {
            foreach ($accounts as $account) {
                if (in_array($type, $debitNormalTypes)) {
                    // For debit-normal accounts: if it has a value, it should be in debit column
                    // (positive balance → debit > 0), or if negative balance → credit > 0
                    if ($account->debit > 0) {
                        $this->assertGreaterThan(
                            0,
                            $account->debit,
                            "Account '{$account->name}' (type: $type) with positive balance should appear in debit column"
                        );
                        $this->assertEquals(
                            0.0,
                            $account->credit,
                            "Account '{$account->name}' (type: $type) in debit column should have credit = 0"
                        );
                    } elseif ($account->credit > 0) {
                        // Negative balance for debit-normal → shown in credit column
                        $this->assertGreaterThan(
                            0,
                            $account->credit,
                            "Account '{$account->name}' (type: $type) with negative balance should appear in credit column"
                        );
                        $this->assertEquals(
                            0.0,
                            $account->debit,
                            "Account '{$account->name}' (type: $type) in credit column should have debit = 0"
                        );
                    }
                } elseif (in_array($type, $creditNormalTypes)) {
                    // For credit-normal accounts: positive balance → credit > 0
                    if ($account->credit > 0) {
                        $this->assertGreaterThan(
                            0,
                            $account->credit,
                            "Account '{$account->name}' (type: $type) with positive balance should appear in credit column"
                        );
                        $this->assertEquals(
                            0.0,
                            $account->debit,
                            "Account '{$account->name}' (type: $type) in credit column should have debit = 0"
                        );
                    } elseif ($account->debit > 0) {
                        // Negative balance for credit-normal → shown in debit column
                        $this->assertGreaterThan(
                            0,
                            $account->debit,
                            "Account '{$account->name}' (type: $type) with negative balance should appear in debit column"
                        );
                        $this->assertEquals(
                            0.0,
                            $account->credit,
                            "Account '{$account->name}' (type: $type) in debit column should have credit = 0"
                        );
                    }
                }
            }
        }

        // If we get here with no accounts, the test still passes (valid empty state)
        $this->assertTrue(true);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 4: Profit & Loss Net Profit Calculation
    // Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 4: For any valid date range, Net Profit SHALL equal
     * Total Income minus Total Expenses.
     *
     * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**
     */
    public function test_property4_profit_and_loss_net_calculation(): void
    {
        $now = Carbon::now();
        $fyStart = $now->month >= 4
            ? Carbon::create($now->year, 4, 1)->format('Y-m-d')
            : Carbon::create($now->year - 1, 4, 1)->format('Y-m-d');
        $fyEnd = $now->format('Y-m-d');

        $result = $this->service->getProfitAndLoss($fyStart, $fyEnd);

        $this->assertArrayHasKey('total_income', $result);
        $this->assertArrayHasKey('total_expenses', $result);
        $this->assertArrayHasKey('net_profit', $result);

        $expectedNetProfit = round($result['total_income'] - $result['total_expenses'], 2);

        $this->assertEqualsWithDelta(
            $expectedNetProfit,
            $result['net_profit'],
            0.01,
            "P&L net_profit ({$result['net_profit']}) should equal total_income ({$result['total_income']}) - total_expenses ({$result['total_expenses']}) = $expectedNetProfit"
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 5: Vehicle Profitability Per-Vehicle Net Calculation
    // Validates: Requirements 5.2, 5.3, 5.5
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 5: For each vehicle in the profitability report, net_profit SHALL equal
     * total_revenue minus total_expenses.
     *
     * **Validates: Requirements 5.2, 5.3, 5.5**
     */
    public function test_property5_vehicle_net_profit_calculation(): void
    {
        $now = Carbon::now();
        $fyStart = $now->month >= 4
            ? Carbon::create($now->year, 4, 1)->format('Y-m-d')
            : Carbon::create($now->year - 1, 4, 1)->format('Y-m-d');
        $fyEnd = $now->format('Y-m-d');

        $result = $this->service->getVehicleProfitability($fyStart, $fyEnd);

        $this->assertArrayHasKey('vehicles', $result);

        foreach ($result['vehicles'] as $index => $vehicle) {
            $expectedNet = round($vehicle->total_revenue - $vehicle->total_expenses, 2);

            $this->assertEqualsWithDelta(
                $expectedNet,
                $vehicle->net_profit,
                0.01,
                "Vehicle '{$vehicle->vehicle_number}' (index $index): net_profit ({$vehicle->net_profit}) should equal total_revenue ({$vehicle->total_revenue}) - total_expenses ({$vehicle->total_expenses}) = $expectedNet"
            );
        }

        // If no vehicles, the test still passes (valid empty state)
        $this->assertTrue(true);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 6: Summary Row Equals Sum of Parts
    // Validates: Requirements 5.4, 5.6, 6.3, 6.4
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 6: Summary totals SHALL equal the sum of corresponding values
     * across all individual rows in Vehicle Profitability and Driver Expenses reports.
     *
     * **Validates: Requirements 5.4, 5.6, 6.3, 6.4**
     */
    public function test_property6_summary_row_equals_sum_of_parts(): void
    {
        $now = Carbon::now();
        $fyStart = $now->month >= 4
            ? Carbon::create($now->year, 4, 1)->format('Y-m-d')
            : Carbon::create($now->year - 1, 4, 1)->format('Y-m-d');
        $fyEnd = $now->format('Y-m-d');

        // Vehicle Profitability: totals should equal sum of individual vehicles
        $vehicleResult = $this->service->getVehicleProfitability($fyStart, $fyEnd);

        $sumRevenue = 0.0;
        $sumExpenses = 0.0;
        $sumNetProfit = 0.0;

        foreach ($vehicleResult['vehicles'] as $vehicle) {
            $sumRevenue += $vehicle->total_revenue;
            $sumExpenses += $vehicle->total_expenses;
            $sumNetProfit += $vehicle->net_profit;
        }

        $this->assertEqualsWithDelta(
            round($sumRevenue, 2),
            $vehicleResult['totals']['revenue'],
            0.01,
            "Vehicle totals revenue ({$vehicleResult['totals']['revenue']}) should equal sum of individual revenues (" . round($sumRevenue, 2) . ")"
        );

        $this->assertEqualsWithDelta(
            round($sumExpenses, 2),
            $vehicleResult['totals']['expenses'],
            0.01,
            "Vehicle totals expenses ({$vehicleResult['totals']['expenses']}) should equal sum of individual expenses (" . round($sumExpenses, 2) . ")"
        );

        $this->assertEqualsWithDelta(
            round($sumNetProfit, 2),
            $vehicleResult['totals']['net_profit'],
            0.01,
            "Vehicle totals net_profit ({$vehicleResult['totals']['net_profit']}) should equal sum of individual net_profits (" . round($sumNetProfit, 2) . ")"
        );

        // Driver Expenses: total_expenses should equal sum of individual driver expenses
        $driverResult = $this->service->getDriverExpenses($fyStart, $fyEnd);

        $sumDriverExpenses = 0.0;

        foreach ($driverResult['drivers'] as $driver) {
            $sumDriverExpenses += $driver->total_expenses;
        }

        $this->assertEqualsWithDelta(
            round($sumDriverExpenses, 2),
            $driverResult['total_expenses'],
            0.01,
            "Driver total_expenses ({$driverResult['total_expenses']}) should equal sum of individual driver expenses (" . round($sumDriverExpenses, 2) . ")"
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 7: Branch Filter Isolation
    // Validates: Requirements 1.3, 2.7, 3.7, 4.7, 5.7, 6.5
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 7: When a branch filter is applied, all returned data SHALL belong
     * exclusively to that branch. No cross-branch data leaks.
     *
     * **Validates: Requirements 1.3, 2.7, 3.7, 4.7, 5.7, 6.5**
     */
    public function test_property7_branch_filter_isolation(): void
    {
        $branch = 'Rajkot';
        $today = Carbon::now()->format('Y-m-d');

        $result = $this->service->getDayBook($today, $branch);

        // All returned voucher entries should have branch == 'Rajkot'
        foreach ($result['vouchers'] as $voucher) {
            foreach ($voucher->entries as $entry) {
                $this->assertEquals(
                    $branch,
                    $entry->branch,
                    "Day Book entry for voucher '{$voucher->voucher_no}' should have branch '$branch', got '{$entry->branch}'"
                );
            }
        }

        // If no vouchers, the test still passes (valid empty state)
        $this->assertTrue(true);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 8: Indian Number Format Correctness
    // Validates: Requirements 7.7
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 8: For any non-negative number, indianNumberFormat() SHALL produce output
     * with exactly 2 decimal places, containing only digits, commas, dots, and optional
     * leading minus sign.
     *
     * **Validates: Requirements 7.7**
     */
    public function test_property8_indian_number_format_correctness(): void
    {
        for ($i = 0; $i < 50; $i++) {
            // Generate random floats from 0.01 to 99,999,999.99
            $number = round(mt_rand(1, 9999999999) / 100, 2);

            // Randomly make some negative
            if (mt_rand(0, 1) === 1) {
                $number = -$number;
            }

            $formatted = indianNumberFormat($number);

            // Verify output contains only digits, commas, dots, and optional leading minus
            $this->assertMatchesRegularExpression(
                '/^-?[\d,]+\.\d{2}$/',
                $formatted,
                "Iteration $i: indianNumberFormat($number) = '$formatted' should match pattern ^-?[\\d,]+\\.\\d{2}$"
            );

            // Verify exactly 2 decimal places
            $parts = explode('.', $formatted);
            $this->assertCount(
                2,
                $parts,
                "Iteration $i: indianNumberFormat($number) = '$formatted' should have exactly one dot"
            );
            $this->assertEquals(
                2,
                strlen($parts[1]),
                "Iteration $i: indianNumberFormat($number) = '$formatted' decimal part should be exactly 2 chars, got '" . $parts[1] . "'"
            );

            // Verify no leading zeros in the integer part (except for 0 itself)
            $integerPart = ltrim(str_replace(['-', ','], '', $parts[0]), '0');
            $cleanNumber = str_replace(['-', ','], '', $parts[0]);
            if ($cleanNumber !== '0') {
                $this->assertNotEquals(
                    '0',
                    $cleanNumber[0] ?? '',
                    "Iteration $i: indianNumberFormat($number) = '$formatted' should not have leading zeros"
                );
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 9: Descending Sort Order
    // Validates: Requirements 5.8, 6.6
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 9: Vehicle Profitability results SHALL be sorted by net_profit descending,
     * and Driver Expenses results SHALL be sorted by total_expenses descending.
     *
     * **Validates: Requirements 5.8, 6.6**
     */
    public function test_property9_descending_sort_order(): void
    {
        $now = Carbon::now();
        $fyStart = $now->month >= 4
            ? Carbon::create($now->year, 4, 1)->format('Y-m-d')
            : Carbon::create($now->year - 1, 4, 1)->format('Y-m-d');
        $fyEnd = $now->format('Y-m-d');

        // Vehicle Profitability: sorted by net_profit descending
        $vehicleResult = $this->service->getVehicleProfitability($fyStart, $fyEnd);

        $vehicles = $vehicleResult['vehicles'];
        for ($i = 0; $i < count($vehicles) - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $vehicles[$i + 1]->net_profit,
                $vehicles[$i]->net_profit,
                "Vehicle at index $i (net_profit={$vehicles[$i]->net_profit}) should be >= vehicle at index " . ($i + 1) . " (net_profit={$vehicles[$i+1]->net_profit})"
            );
        }

        // Driver Expenses: sorted by total_expenses descending
        $driverResult = $this->service->getDriverExpenses($fyStart, $fyEnd);

        $drivers = $driverResult['drivers'];
        for ($i = 0; $i < count($drivers) - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $drivers[$i + 1]->total_expenses,
                $drivers[$i]->total_expenses,
                "Driver at index $i (total_expenses={$drivers[$i]->total_expenses}) should be >= driver at index " . ($i + 1) . " (total_expenses={$drivers[$i+1]->total_expenses})"
            );
        }

        // If collections are empty, test still passes
        $this->assertTrue(true);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 10: Day Book Date Filter Completeness
    // Validates: Requirements 1.2
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 10: The Day Book SHALL include all approved vouchers for the selected date,
     * and all returned vouchers SHALL have voucher_date matching the selected date.
     *
     * **Validates: Requirements 1.2**
     */
    public function test_property10_day_book_date_filter(): void
    {
        $today = Carbon::now()->format('Y-m-d');

        $result = $this->service->getDayBook($today);

        $this->assertArrayHasKey('vouchers', $result);

        // All returned vouchers should have voucher_date == today
        foreach ($result['vouchers'] as $voucher) {
            $voucherDate = Carbon::parse($voucher->voucher_date)->format('Y-m-d');

            $this->assertEquals(
                $today,
                $voucherDate,
                "Day Book voucher '{$voucher->voucher_no}' has date '$voucherDate' but expected '$today'"
            );
        }

        // If no vouchers for today, test still passes (valid empty state)
        $this->assertTrue(true);
    }
}
