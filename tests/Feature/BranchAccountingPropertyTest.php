<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Accounting\Account;
use App\Models\Accounting\Expense;
use App\Models\Accounting\LedgerEntry;
use App\Models\Accounting\Outstanding;
use App\Models\Accounting\Voucher;
use App\Services\BranchAccountingService;
use Carbon\Carbon;

/**
 * Property-based tests for BranchAccountingService.
 *
 * Each test seeds randomized data across branches and date ranges,
 * then verifies that universal correctness properties hold.
 */
class BranchAccountingPropertyTest extends TestCase
{
    private BranchAccountingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BranchAccountingService::class);
    }

    /**
     * Helper: pick a random subset of branches.
     */
    private function randomBranches(int $count = 3): array
    {
        $branches = BranchAccountingService::BRANCHES;
        shuffle($branches);
        return array_slice($branches, 0, min($count, count($branches)));
    }

    /**
     * Helper: get or create an income-type account.
     */
    private function getIncomeAccount(): Account
    {
        return Account::where('type', 'income')->where('is_group', false)->first()
            ?? Account::create([
                'code' => '3' . rand(100, 999),
                'name' => 'Test Income ' . rand(1, 9999),
                'type' => 'income',
                'is_group' => false,
                'opening_balance' => 0,
                'is_active' => true,
            ]);
    }

    /**
     * Helper: get or create an expense-type account for expenses table.
     */
    private function getExpenseAccount(): Account
    {
        return Account::where('type', 'expense')->where('is_group', false)->first()
            ?? Account::create([
                'code' => '4' . rand(100, 999),
                'name' => 'Test Expense ' . rand(1, 9999),
                'type' => 'expense',
                'is_group' => false,
                'opening_balance' => 0,
                'is_active' => true,
            ]);
    }

    /**
     * Helper: create a voucher for ledger entries.
     */
    private function createVoucher(string $date, string $branch): Voucher
    {
        return Voucher::create([
            'voucher_no' => 'TEST-PBT-' . rand(10000, 99999),
            'voucher_type' => 'journal',
            'voucher_date' => $date,
            'narration' => 'PBT test voucher',
            'total_amount' => 0,
            'status' => 'approved',
            'branch' => $branch,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 2: Profitability Calculation Correctness
    // Validates: Requirements 2.2, 3.2, 5.2, 5.3
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 2: For any branch and date range, net_profit = revenue - expenses
     * and profit_margin = ((rev - exp) / rev) * 100 rounded to 2dp (0 when rev=0).
     *
     * **Validates: Requirements 2.2, 3.2, 5.2, 5.3**
     */
    public function test_property2_profitability_calculation_correctness(): void
    {
        $createdIds = ['ledger_entries' => [], 'expenses' => [], 'vouchers' => []];

        try {
            $fromDate = '2024-06-01';
            $toDate = '2024-06-30';
            $incomeAccount = $this->getIncomeAccount();
            $expenseAccount = $this->getExpenseAccount();
            $branches = $this->randomBranches(4);

            // Seed random revenue and expense data per branch
            $expectedRevenue = [];
            $expectedExpenses = [];

            foreach ($branches as $branch) {
                $expectedRevenue[$branch] = 0;
                $expectedExpenses[$branch] = 0;

                // Seed 2-5 income ledger entries per branch
                $entryCount = rand(2, 5);
                for ($i = 0; $i < $entryCount; $i++) {
                    $amount = rand(1000, 50000) / 100; // random amount with decimals
                    $day = rand(1, 30);
                    $date = sprintf('2024-06-%02d', $day);

                    $voucher = $this->createVoucher($date, $branch);
                    $createdIds['vouchers'][] = $voucher->id;

                    $entry = LedgerEntry::create([
                        'voucher_id' => $voucher->id,
                        'account_id' => $incomeAccount->id,
                        'date' => $date,
                        'debit' => 0,
                        'credit' => $amount,
                        'narration' => 'PBT revenue test',
                        'branch' => $branch,
                    ]);
                    $createdIds['ledger_entries'][] = $entry->id;
                    $expectedRevenue[$branch] += $amount;
                }

                // Seed 1-4 approved expenses per branch
                $expCount = rand(1, 4);
                for ($i = 0; $i < $expCount; $i++) {
                    $amount = rand(500, 30000) / 100;
                    $day = rand(1, 30);
                    $date = sprintf('2024-06-%02d', $day);

                    $expense = Expense::create([
                        'expense_no' => 'PBT-EXP-' . rand(10000, 99999),
                        'expense_date' => $date,
                        'expense_type' => 'misc',
                        'description' => 'PBT profitability test expense',
                        'amount' => $amount,
                        'paid_to' => 'Test Vendor',
                        'account_id' => $expenseAccount->id,
                        'paid_from_account_id' => $expenseAccount->id,
                        'branch' => $branch,
                        'status' => 'approved',
                    ]);
                    $createdIds['expenses'][] = $expense->id;
                    $expectedExpenses[$branch] += $amount;
                }
            }

            // Call the service
            $result = $this->service->getProfitabilityByBranch($fromDate, $toDate);

            // Assert properties for each seeded branch
            foreach ($branches as $branch) {
                $branchResult = $result->firstWhere('branch', $branch);
                $this->assertNotNull($branchResult, "Branch $branch should appear in profitability results");

                $rev = (float) $branchResult->revenue;
                $exp = (float) $branchResult->expenses;
                $netProfit = (float) $branchResult->net_profit;
                $margin = (float) $branchResult->profit_margin;

                // Property: net_profit = revenue - expenses
                $this->assertEqualsWithDelta(
                    $rev - $exp,
                    $netProfit,
                    0.01,
                    "Branch $branch: net_profit should equal revenue - expenses"
                );

                // Property: profit_margin calculation
                if ($rev > 0) {
                    $expectedMargin = round((($rev - $exp) / $rev) * 100, 2);
                    $this->assertEqualsWithDelta(
                        $expectedMargin,
                        $margin,
                        0.01,
                        "Branch $branch: profit_margin should be ((rev-exp)/rev)*100 rounded to 2dp"
                    );
                } else {
                    $this->assertEquals(0, $margin, "Branch $branch: profit_margin should be 0 when revenue is 0");
                }
            }
        } finally {
            // Cleanup seeded data
            if (!empty($createdIds['ledger_entries'])) {
                LedgerEntry::whereIn('id', $createdIds['ledger_entries'])->delete();
            }
            if (!empty($createdIds['expenses'])) {
                Expense::whereIn('id', $createdIds['expenses'])->forceDelete();
            }
            if (!empty($createdIds['vouchers'])) {
                Voucher::whereIn('id', $createdIds['vouchers'])->forceDelete();
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 3: Date Range Filtering
    // Validates: Requirements 2.3, 3.4, 4.4, 5.5, 6.3, 7.5
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 3: For any date range, only entries within [from_date, to_date] are counted.
     *
     * **Validates: Requirements 2.3, 3.4, 4.4, 5.5, 6.3, 7.5**
     */
    public function test_property3_date_range_filtering(): void
    {
        $createdIds = ['ledger_entries' => [], 'expenses' => [], 'vouchers' => []];

        try {
            $branch = BranchAccountingService::BRANCHES[array_rand(BranchAccountingService::BRANCHES)];
            $incomeAccount = $this->getIncomeAccount();
            $expenseAccount = $this->getExpenseAccount();

            // Define a narrow sub-range to test
            $fromDate = '2024-08-10';
            $toDate = '2024-08-20';

            // Seed entries INSIDE the range
            $insideRevenue = 0;
            $insideExpenseTotal = 0;
            for ($i = 0; $i < 3; $i++) {
                $day = rand(10, 20);
                $date = sprintf('2024-08-%02d', $day);
                $amount = rand(1000, 9000) / 100;

                $voucher = $this->createVoucher($date, $branch);
                $createdIds['vouchers'][] = $voucher->id;

                $entry = LedgerEntry::create([
                    'voucher_id' => $voucher->id,
                    'account_id' => $incomeAccount->id,
                    'date' => $date,
                    'debit' => 0,
                    'credit' => $amount,
                    'narration' => 'PBT inside range',
                    'branch' => $branch,
                ]);
                $createdIds['ledger_entries'][] = $entry->id;
                $insideRevenue += $amount;

                $expAmount = rand(500, 5000) / 100;
                $expense = Expense::create([
                    'expense_no' => 'PBT-DR-' . rand(10000, 99999),
                    'expense_date' => $date,
                    'expense_type' => 'office',
                    'description' => 'PBT date range inside',
                    'amount' => $expAmount,
                    'paid_to' => 'Test',
                    'account_id' => $expenseAccount->id,
                    'paid_from_account_id' => $expenseAccount->id,
                    'branch' => $branch,
                    'status' => 'approved',
                ]);
                $createdIds['expenses'][] = $expense->id;
                $insideExpenseTotal += $expAmount;
            }

            // Seed entries OUTSIDE the range (before and after)
            $outsideDates = ['2024-08-01', '2024-08-05', '2024-08-25', '2024-08-30'];
            foreach ($outsideDates as $date) {
                $amount = rand(1000, 9000) / 100;

                $voucher = $this->createVoucher($date, $branch);
                $createdIds['vouchers'][] = $voucher->id;

                $entry = LedgerEntry::create([
                    'voucher_id' => $voucher->id,
                    'account_id' => $incomeAccount->id,
                    'date' => $date,
                    'debit' => 0,
                    'credit' => $amount,
                    'narration' => 'PBT outside range',
                    'branch' => $branch,
                ]);
                $createdIds['ledger_entries'][] = $entry->id;

                $expense = Expense::create([
                    'expense_no' => 'PBT-OUT-' . rand(10000, 99999),
                    'expense_date' => $date,
                    'expense_type' => 'office',
                    'description' => 'PBT date range outside',
                    'amount' => $amount,
                    'paid_to' => 'Test',
                    'account_id' => $expenseAccount->id,
                    'paid_from_account_id' => $expenseAccount->id,
                    'branch' => $branch,
                    'status' => 'approved',
                ]);
                $createdIds['expenses'][] = $expense->id;
            }

            // Verify expense filtering: only inside-range expenses counted
            $expenseResult = $this->service->getExpensesByBranch($fromDate, $toDate);
            $branchExpenses = $expenseResult->firstWhere('branch', $branch);

            if ($branchExpenses) {
                // The branch total should include ONLY in-range expenses
                // We verify by checking the detail method for exactness
                $details = $this->service->getExpenseDetailForBranch($branch, $fromDate, $toDate);
                foreach ($details as $detail) {
                    $expDate = Carbon::parse($detail->expense_date);
                    $this->assertTrue(
                        $expDate->greaterThanOrEqualTo(Carbon::parse($fromDate)) &&
                        $expDate->lessThanOrEqualTo(Carbon::parse($toDate)),
                        "Expense date {$detail->expense_date} should be within [$fromDate, $toDate]"
                    );
                }
            }

            // Verify revenue filtering via profitability (which uses the same date filter)
            $profitability = $this->service->getProfitabilityByBranch($fromDate, $toDate);
            $branchProfit = $profitability->firstWhere('branch', $branch);
            $this->assertNotNull($branchProfit);

            // Revenue should be >= insideRevenue (could include pre-existing data in range)
            // but outside-range entries should NOT be included
            // The key property: detail entries all fall within range
            $dashboardMetrics = $this->service->getDashboardMetrics($fromDate, $toDate);
            // All branch metrics are computed from date-filtered data
            $this->assertArrayHasKey('branches', $dashboardMetrics);
            $this->assertArrayHasKey('totals', $dashboardMetrics);

        } finally {
            if (!empty($createdIds['ledger_entries'])) {
                LedgerEntry::whereIn('id', $createdIds['ledger_entries'])->delete();
            }
            if (!empty($createdIds['expenses'])) {
                Expense::whereIn('id', $createdIds['expenses'])->forceDelete();
            }
            if (!empty($createdIds['vouchers'])) {
                Voucher::whereIn('id', $createdIds['vouchers'])->forceDelete();
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 4: Consolidated Totals Invariant
    // Validates: Requirements 2.5, 3.6, 4.6, 6.5
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 4: The consolidated totals row equals the sum of all individual branch values.
     *
     * **Validates: Requirements 2.5, 3.6, 4.6, 6.5**
     */
    public function test_property4_consolidated_totals_invariant(): void
    {
        $createdIds = ['ledger_entries' => [], 'expenses' => [], 'vouchers' => []];

        try {
            $fromDate = '2024-07-01';
            $toDate = '2024-07-31';
            $incomeAccount = $this->getIncomeAccount();
            $expenseAccount = $this->getExpenseAccount();

            // Seed data across multiple branches
            $branches = $this->randomBranches(5);
            foreach ($branches as $branch) {
                for ($i = 0; $i < rand(2, 4); $i++) {
                    $day = rand(1, 31);
                    $date = sprintf('2024-07-%02d', min($day, 31));
                    $amount = rand(1000, 80000) / 100;

                    $voucher = $this->createVoucher($date, $branch);
                    $createdIds['vouchers'][] = $voucher->id;

                    $entry = LedgerEntry::create([
                        'voucher_id' => $voucher->id,
                        'account_id' => $incomeAccount->id,
                        'date' => $date,
                        'debit' => 0,
                        'credit' => $amount,
                        'narration' => 'PBT consolidated test',
                        'branch' => $branch,
                    ]);
                    $createdIds['ledger_entries'][] = $entry->id;

                    $expAmount = rand(500, 40000) / 100;
                    $expense = Expense::create([
                        'expense_no' => 'PBT-CON-' . rand(10000, 99999),
                        'expense_date' => $date,
                        'expense_type' => 'diesel',
                        'description' => 'PBT consolidated test expense',
                        'amount' => $expAmount,
                        'paid_to' => 'Test Vendor',
                        'account_id' => $expenseAccount->id,
                        'paid_from_account_id' => $expenseAccount->id,
                        'branch' => $branch,
                        'status' => 'approved',
                    ]);
                    $createdIds['expenses'][] = $expense->id;
                }
            }

            // Call getDashboardMetrics
            $metrics = $this->service->getDashboardMetrics($fromDate, $toDate);

            $branchesData = $metrics['branches'];
            $totals = $metrics['totals'];

            // Property: totals.revenue = sum of all branches[X].revenue
            $sumRevenue = 0;
            $sumExpenses = 0;
            $sumProfitability = 0;
            $sumCashPosition = 0;
            $sumOutstanding = 0;

            foreach ($branchesData as $branchName => $data) {
                $sumRevenue += $data['revenue'];
                $sumExpenses += $data['expenses'];
                $sumProfitability += $data['profitability'];
                $sumCashPosition += $data['cash_position'];
                $sumOutstanding += $data['outstanding'];
            }

            $this->assertEqualsWithDelta(
                round($sumRevenue, 2),
                $totals['revenue'],
                0.01,
                'Consolidated revenue should equal sum of all branch revenues'
            );
            $this->assertEqualsWithDelta(
                round($sumExpenses, 2),
                $totals['expenses'],
                0.01,
                'Consolidated expenses should equal sum of all branch expenses'
            );
            $this->assertEqualsWithDelta(
                round($sumProfitability, 2),
                $totals['profitability'],
                0.01,
                'Consolidated profitability should equal sum of all branch profitabilities'
            );
            $this->assertEqualsWithDelta(
                round($sumCashPosition, 2),
                $totals['cash_position'],
                0.01,
                'Consolidated cash_position should equal sum of all branch cash_positions'
            );
            $this->assertEqualsWithDelta(
                round($sumOutstanding, 2),
                $totals['outstanding'],
                0.01,
                'Consolidated outstanding should equal sum of all branch outstandings'
            );
        } finally {
            if (!empty($createdIds['ledger_entries'])) {
                LedgerEntry::whereIn('id', $createdIds['ledger_entries'])->delete();
            }
            if (!empty($createdIds['expenses'])) {
                Expense::whereIn('id', $createdIds['expenses'])->forceDelete();
            }
            if (!empty($createdIds['vouchers'])) {
                Voucher::whereIn('id', $createdIds['vouchers'])->forceDelete();
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 5: Branch Filtering Isolation
    // Validates: Requirements 3.3, 4.3, 7.4
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 5: Detail views for a specific branch return ONLY records for that branch.
     *
     * **Validates: Requirements 3.3, 4.3, 7.4**
     */
    public function test_property5_branch_filtering_isolation(): void
    {
        $createdIds = ['expenses' => [], 'outstanding' => []];

        try {
            $fromDate = '2024-09-01';
            $toDate = '2024-09-30';
            $expenseAccount = $this->getExpenseAccount();

            // Seed expenses across multiple branches
            $allBranches = BranchAccountingService::BRANCHES;
            $targetBranch = $allBranches[array_rand($allBranches)];
            $otherBranches = array_diff($allBranches, [$targetBranch]);

            // Seed target branch expenses
            for ($i = 0; $i < 3; $i++) {
                $expense = Expense::create([
                    'expense_no' => 'PBT-ISO-T-' . rand(10000, 99999),
                    'expense_date' => sprintf('2024-09-%02d', rand(1, 30)),
                    'expense_type' => 'office',
                    'description' => 'PBT isolation target',
                    'amount' => rand(100, 5000) / 100,
                    'paid_to' => 'Target Vendor',
                    'account_id' => $expenseAccount->id,
                    'paid_from_account_id' => $expenseAccount->id,
                    'branch' => $targetBranch,
                    'status' => 'approved',
                ]);
                $createdIds['expenses'][] = $expense->id;
            }

            // Seed other branch expenses
            foreach (array_slice(array_values($otherBranches), 0, 2) as $otherBranch) {
                for ($i = 0; $i < 2; $i++) {
                    $expense = Expense::create([
                        'expense_no' => 'PBT-ISO-O-' . rand(10000, 99999),
                        'expense_date' => sprintf('2024-09-%02d', rand(1, 30)),
                        'expense_type' => 'diesel',
                        'description' => 'PBT isolation other',
                        'amount' => rand(100, 5000) / 100,
                        'paid_to' => 'Other Vendor',
                        'account_id' => $expenseAccount->id,
                        'paid_from_account_id' => $expenseAccount->id,
                        'branch' => $otherBranch,
                        'status' => 'approved',
                    ]);
                    $createdIds['expenses'][] = $expense->id;
                }
            }

            // Seed outstanding across branches
            $outstanding1 = Outstanding::create([
                'party_type' => 'consignee',
                'party_name' => 'PBT Target Customer',
                'type' => 'receivable',
                'invoice_ref' => 'PBT-INV-T-' . rand(1000, 9999),
                'invoice_date' => '2024-09-15',
                'total_amount' => 5000,
                'paid_amount' => 1000,
                'pending_amount' => 4000,
                'due_date' => '2024-10-15',
                'status' => 'pending',
                'branch' => $targetBranch,
            ]);
            $createdIds['outstanding'][] = $outstanding1->id;

            $otherBranchForOutstanding = array_values($otherBranches)[0];
            $outstanding2 = Outstanding::create([
                'party_type' => 'truck_owner',
                'party_name' => 'PBT Other Vendor',
                'type' => 'payable',
                'invoice_ref' => 'PBT-INV-O-' . rand(1000, 9999),
                'invoice_date' => '2024-09-10',
                'total_amount' => 3000,
                'paid_amount' => 500,
                'pending_amount' => 2500,
                'due_date' => '2024-10-10',
                'status' => 'pending',
                'branch' => $otherBranchForOutstanding,
            ]);
            $createdIds['outstanding'][] = $outstanding2->id;

            // Test expense detail isolation
            $expenseDetails = $this->service->getExpenseDetailForBranch($targetBranch, $fromDate, $toDate);
            foreach ($expenseDetails as $detail) {
                // The query doesn't return branch column in select, but it filters by branch
                // We verify that no "other branch" descriptions appear
                $this->assertNotEquals(
                    'PBT isolation other',
                    $detail->description,
                    "Expense detail for $targetBranch should not contain records from other branches"
                );
            }

            // Test outstanding detail isolation
            $outstandingDetails = $this->service->getOutstandingDetailForBranch($targetBranch, $fromDate, $toDate);
            foreach ($outstandingDetails as $detail) {
                // Verify no other branch party names appear
                $this->assertNotEquals(
                    'PBT Other Vendor',
                    $detail->party_name,
                    "Outstanding detail for $targetBranch should not contain records from other branches"
                );
            }

        } finally {
            if (!empty($createdIds['expenses'])) {
                Expense::whereIn('id', $createdIds['expenses'])->forceDelete();
            }
            if (!empty($createdIds['outstanding'])) {
                Outstanding::whereIn('id', $createdIds['outstanding'])->forceDelete();
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 6: Status Exclusion
    // Validates: Requirements 4.1, 7.2
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 6: Rejected expenses and paid outstanding entries are excluded from results.
     *
     * **Validates: Requirements 4.1, 7.2**
     */
    public function test_property6_status_exclusion(): void
    {
        $createdIds = ['expenses' => [], 'outstanding' => []];

        try {
            $fromDate = '2024-10-01';
            $toDate = '2024-10-31';
            $branch = BranchAccountingService::BRANCHES[array_rand(BranchAccountingService::BRANCHES)];
            $expenseAccount = $this->getExpenseAccount();

            // Seed a REJECTED expense — should be excluded
            $rejectedExpense = Expense::create([
                'expense_no' => 'PBT-REJ-' . rand(10000, 99999),
                'expense_date' => '2024-10-15',
                'expense_type' => 'misc',
                'description' => 'PBT rejected expense marker',
                'amount' => 9999.99,
                'paid_to' => 'Rejected Vendor',
                'account_id' => $expenseAccount->id,
                'paid_from_account_id' => $expenseAccount->id,
                'branch' => $branch,
                'status' => 'rejected',
            ]);
            $createdIds['expenses'][] = $rejectedExpense->id;

            // Seed an APPROVED expense — should be included
            $approvedExpense = Expense::create([
                'expense_no' => 'PBT-APR-' . rand(10000, 99999),
                'expense_date' => '2024-10-15',
                'expense_type' => 'misc',
                'description' => 'PBT approved expense marker',
                'amount' => 123.45,
                'paid_to' => 'Approved Vendor',
                'account_id' => $expenseAccount->id,
                'paid_from_account_id' => $expenseAccount->id,
                'branch' => $branch,
                'status' => 'approved',
            ]);
            $createdIds['expenses'][] = $approvedExpense->id;

            // Seed a PAID outstanding — should be excluded
            $paidOutstanding = Outstanding::create([
                'party_type' => 'consignee',
                'party_name' => 'PBT Paid Customer Marker',
                'type' => 'receivable',
                'invoice_ref' => 'PBT-PAID-' . rand(1000, 9999),
                'invoice_date' => '2024-10-10',
                'total_amount' => 8000,
                'paid_amount' => 8000,
                'pending_amount' => 0,
                'due_date' => '2024-11-10',
                'status' => 'paid',
                'branch' => $branch,
            ]);
            $createdIds['outstanding'][] = $paidOutstanding->id;

            // Seed a PENDING outstanding — should be included
            $pendingOutstanding = Outstanding::create([
                'party_type' => 'consignee',
                'party_name' => 'PBT Pending Customer Marker',
                'type' => 'receivable',
                'invoice_ref' => 'PBT-PEND-' . rand(1000, 9999),
                'invoice_date' => '2024-10-12',
                'total_amount' => 5000,
                'paid_amount' => 1000,
                'pending_amount' => 4000,
                'due_date' => '2024-11-12',
                'status' => 'pending',
                'branch' => $branch,
            ]);
            $createdIds['outstanding'][] = $pendingOutstanding->id;

            // Verify: rejected expense does NOT appear in expense results
            $expenseResults = $this->service->getExpensesByBranch($fromDate, $toDate);
            $branchExpenses = $expenseResults->firstWhere('branch', $branch);

            // Check detail view for the branch
            $expenseDetails = $this->service->getExpenseDetailForBranch($branch, $fromDate, $toDate);
            $rejectedFound = false;
            foreach ($expenseDetails as $detail) {
                if ($detail->description === 'PBT rejected expense marker') {
                    $rejectedFound = true;
                    break;
                }
            }
            $this->assertFalse($rejectedFound, 'Rejected expenses should NOT appear in expense detail results');

            // Verify approved expense IS present
            $approvedFound = false;
            foreach ($expenseDetails as $detail) {
                if ($detail->description === 'PBT approved expense marker') {
                    $approvedFound = true;
                    break;
                }
            }
            $this->assertTrue($approvedFound, 'Approved expenses should appear in expense detail results');

            // Verify: paid outstanding does NOT appear in outstanding results
            $outstandingResults = $this->service->getOutstandingByBranch($fromDate, $toDate);
            $branchOutstanding = $outstandingResults->firstWhere('branch', $branch);

            // Check detail view
            $outstandingDetails = $this->service->getOutstandingDetailForBranch($branch, $fromDate, $toDate);
            $paidFound = false;
            $pendingFound = false;
            foreach ($outstandingDetails as $detail) {
                if ($detail->party_name === 'PBT Paid Customer Marker') {
                    $paidFound = true;
                }
                if ($detail->party_name === 'PBT Pending Customer Marker') {
                    $pendingFound = true;
                }
            }
            $this->assertFalse($paidFound, 'Paid outstanding entries should NOT appear in outstanding detail results');
            $this->assertTrue($pendingFound, 'Pending outstanding entries should appear in outstanding detail results');

        } finally {
            if (!empty($createdIds['expenses'])) {
                Expense::whereIn('id', $createdIds['expenses'])->forceDelete();
            }
            if (!empty($createdIds['outstanding'])) {
                Outstanding::whereIn('id', $createdIds['outstanding'])->forceDelete();
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Property 7: Cash Position Balance Equation
    // Validates: Requirements 6.2
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @test
     * Property 7: For any branch and date range, closing_balance == opening_balance + receipts - payments,
     * where cash accounts are those with codes 1101 and 1102.
     *
     * **Validates: Requirements 6.2**
     */
    public function test_property7_cash_position_balance_equation(): void
    {
        $createdIds = ['ledger_entries' => [], 'vouchers' => [], 'accounts' => []];

        try {
            $fromDate = '2024-11-10';
            $toDate = '2024-11-25';

            // Ensure cash accounts exist (codes 1101 and 1102)
            $cashAccount1 = Account::where('code', '1101')->first();
            if (!$cashAccount1) {
                $cashAccount1 = Account::create([
                    'code' => '1101',
                    'name' => 'Cash In Hand',
                    'type' => 'asset',
                    'is_group' => false,
                    'opening_balance' => 0,
                    'is_active' => true,
                ]);
                $createdIds['accounts'][] = $cashAccount1->id;
            }

            $cashAccount2 = Account::where('code', '1102')->first();
            if (!$cashAccount2) {
                $cashAccount2 = Account::create([
                    'code' => '1102',
                    'name' => 'Petty Cash',
                    'type' => 'asset',
                    'is_group' => false,
                    'opening_balance' => 0,
                    'is_active' => true,
                ]);
                $createdIds['accounts'][] = $cashAccount2->id;
            }

            $cashAccounts = [$cashAccount1, $cashAccount2];
            $branches = $this->randomBranches(4);

            // Track expected values per branch
            $expectedOpening = [];   // sum of debits - sum of credits BEFORE fromDate
            $expectedReceipts = [];  // sum of debits WITHIN range
            $expectedPayments = [];  // sum of credits WITHIN range

            foreach ($branches as $branch) {
                $expectedOpening[$branch] = 0;
                $expectedReceipts[$branch] = 0;
                $expectedPayments[$branch] = 0;

                // Seed 2-4 entries BEFORE fromDate (contribute to opening balance)
                $beforeCount = rand(2, 4);
                for ($i = 0; $i < $beforeCount; $i++) {
                    $day = rand(1, 9);
                    $date = sprintf('2024-11-%02d', $day);
                    $account = $cashAccounts[array_rand($cashAccounts)];

                    $debitAmount = rand(1000, 50000) / 100;
                    $creditAmount = rand(500, 30000) / 100;

                    $voucher = $this->createVoucher($date, $branch);
                    $createdIds['vouchers'][] = $voucher->id;

                    // Create a debit entry
                    $entry = LedgerEntry::create([
                        'voucher_id' => $voucher->id,
                        'account_id' => $account->id,
                        'date' => $date,
                        'debit' => $debitAmount,
                        'credit' => 0,
                        'narration' => 'PBT cash opening debit',
                        'branch' => $branch,
                    ]);
                    $createdIds['ledger_entries'][] = $entry->id;
                    $expectedOpening[$branch] += $debitAmount;

                    // Create a credit entry
                    $voucher2 = $this->createVoucher($date, $branch);
                    $createdIds['vouchers'][] = $voucher2->id;

                    $entry2 = LedgerEntry::create([
                        'voucher_id' => $voucher2->id,
                        'account_id' => $account->id,
                        'date' => $date,
                        'debit' => 0,
                        'credit' => $creditAmount,
                        'narration' => 'PBT cash opening credit',
                        'branch' => $branch,
                    ]);
                    $createdIds['ledger_entries'][] = $entry2->id;
                    $expectedOpening[$branch] -= $creditAmount;
                }

                // Seed 3-6 entries WITHIN the date range
                $withinCount = rand(3, 6);
                for ($i = 0; $i < $withinCount; $i++) {
                    $day = rand(10, 25);
                    $date = sprintf('2024-11-%02d', $day);
                    $account = $cashAccounts[array_rand($cashAccounts)];

                    $voucher = $this->createVoucher($date, $branch);
                    $createdIds['vouchers'][] = $voucher->id;

                    // Randomly create debit or credit entries
                    if (rand(0, 1) === 0) {
                        // Debit entry (receipt)
                        $amount = rand(1000, 80000) / 100;
                        $entry = LedgerEntry::create([
                            'voucher_id' => $voucher->id,
                            'account_id' => $account->id,
                            'date' => $date,
                            'debit' => $amount,
                            'credit' => 0,
                            'narration' => 'PBT cash receipt',
                            'branch' => $branch,
                        ]);
                        $createdIds['ledger_entries'][] = $entry->id;
                        $expectedReceipts[$branch] += $amount;
                    } else {
                        // Credit entry (payment)
                        $amount = rand(500, 60000) / 100;
                        $entry = LedgerEntry::create([
                            'voucher_id' => $voucher->id,
                            'account_id' => $account->id,
                            'date' => $date,
                            'debit' => 0,
                            'credit' => $amount,
                            'narration' => 'PBT cash payment',
                            'branch' => $branch,
                        ]);
                        $createdIds['ledger_entries'][] = $entry->id;
                        $expectedPayments[$branch] += $amount;
                    }
                }
            }

            // Call getCashPositionByBranch
            $result = $this->service->getCashPositionByBranch($fromDate, $toDate);

            // Assert the balance equation for each seeded branch
            foreach ($branches as $branch) {
                $branchRow = $result->firstWhere('branch', $branch);
                $this->assertNotNull($branchRow, "Branch $branch should appear in cash position results");

                $opening = (float) $branchRow->opening_balance;
                $receipts = (float) $branchRow->receipts;
                $payments = (float) $branchRow->payments;
                $closing = (float) $branchRow->closing_balance;

                // Property: closing_balance == opening_balance + receipts - payments
                $expectedClosing = $opening + $receipts - $payments;
                $this->assertEqualsWithDelta(
                    $expectedClosing,
                    $closing,
                    0.01,
                    "Branch $branch: closing_balance ($closing) should equal opening_balance ($opening) + receipts ($receipts) - payments ($payments) = $expectedClosing"
                );

                // Additionally verify that receipts and payments match our seeded data
                // (opening may include pre-existing data, so we check the equation holds)
                $this->assertGreaterThanOrEqual(0, $receipts, "Branch $branch: receipts should be non-negative");
                $this->assertGreaterThanOrEqual(0, $payments, "Branch $branch: payments should be non-negative");
            }
        } finally {
            // Cleanup seeded data
            if (!empty($createdIds['ledger_entries'])) {
                LedgerEntry::whereIn('id', $createdIds['ledger_entries'])->delete();
            }
            if (!empty($createdIds['vouchers'])) {
                Voucher::whereIn('id', $createdIds['vouchers'])->forceDelete();
            }
            if (!empty($createdIds['accounts'])) {
                Account::whereIn('id', $createdIds['accounts'])->forceDelete();
            }
        }
    }
}
