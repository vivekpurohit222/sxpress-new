<?php

namespace App\Services;

use App\Models\Accounting\Account;
use App\Models\Accounting\Expense;
use App\Models\Accounting\LedgerEntry;
use App\Models\Accounting\Outstanding;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * BranchAccountingService - Encapsulates all branch-level aggregation queries
 * for the Branch Accounting reports (Phase 10).
 */
class BranchAccountingService
{
    /**
     * All 7 SXpress office branches.
     */
    const BRANCHES = [
        'Rajkot',
        'Navagam',
        'Shapar (1)',
        'Shapar (2)',
        'Dayabasti',
        'Kashmore Gate',
        'Swarup Nagar',
    ];

    /**
     * Resolve the current Indian Financial Year boundaries (April 1 - March 31).
     */
    public function resolveFinancialYearDates(): array
    {
        $today = Carbon::today();

        if ($today->month >= 4) {
            $from = Carbon::create($today->year, 4, 1);
            $to = Carbon::create($today->year + 1, 3, 31);
        } else {
            $from = Carbon::create($today->year - 1, 4, 1);
            $to = Carbon::create($today->year, 3, 31);
        }

        return [$from->format('Y-m-d'), $to->format('Y-m-d')];
    }

    /**
     * Get dashboard metrics for all branches within a date range.
     *
     * @param string $fromDate Y-m-d
     * @param string $toDate   Y-m-d
     * @return array
     */
    public function getDashboardMetrics(string $fromDate, string $toDate): array
    {
        // Revenue per branch: sum of credit from ledger_entries where account.type = 'income'
        $revenueData = LedgerEntry::query()
            ->join('accounts', 'ledger_entries.account_id', '=', 'accounts.id')
            ->where('accounts.type', 'income')
            ->whereBetween('ledger_entries.date', [$fromDate, $toDate])
            ->groupBy('ledger_entries.branch')
            ->select('ledger_entries.branch', DB::raw('COALESCE(SUM(ledger_entries.credit), 0) as total_revenue'))
            ->pluck('total_revenue', 'branch');

        // Expenses per branch: sum of amount from expenses where status != 'rejected'
        $expenseData = Expense::query()
            ->where('status', '!=', 'rejected')
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->groupBy('branch')
            ->select('branch', DB::raw('COALESCE(SUM(amount), 0) as total_expenses'))
            ->pluck('total_expenses', 'branch');

        // Cash Position per branch: net balance of accounts with codes '1101' and '1102'
        // opening_balance + sum(debit) - sum(credit) for all entries up to toDate
        $cashAccountIds = Account::whereIn('code', ['1101', '1102'])->pluck('id');

        $cashPositionData = collect();
        if ($cashAccountIds->isNotEmpty()) {
            $cashPositionData = LedgerEntry::query()
                ->whereIn('account_id', $cashAccountIds)
                ->where('date', '<=', $toDate)
                ->groupBy('branch')
                ->select(
                    'branch',
                    DB::raw('COALESCE(SUM(debit), 0) as total_debit'),
                    DB::raw('COALESCE(SUM(credit), 0) as total_credit')
                )
                ->get()
                ->keyBy('branch');
        }

        // Outstanding per branch: sum of pending_amount where status != 'paid'
        $outstandingData = Outstanding::query()
            ->where('status', '!=', 'paid')
            ->groupBy('branch')
            ->select('branch', DB::raw('COALESCE(SUM(pending_amount), 0) as total_outstanding'))
            ->pluck('total_outstanding', 'branch');

        // Build per-branch metrics
        $branches = [];
        $totalRevenue = 0;
        $totalExpenses = 0;
        $totalProfitability = 0;
        $totalCashPosition = 0;
        $totalOutstanding = 0;

        foreach (self::BRANCHES as $branch) {
            $revenue = (float) ($revenueData[$branch] ?? 0);
            $expenses = (float) ($expenseData[$branch] ?? 0);
            $profitability = $revenue - $expenses;

            // Cash position: net balance of cash accounts for this branch
            $cashPosition = 0;
            if ($cashPositionData->has($branch)) {
                $entry = $cashPositionData[$branch];
                $cashPosition = (float) $entry->total_debit - (float) $entry->total_credit;
            }

            $outstanding = (float) ($outstandingData[$branch] ?? 0);

            $branches[$branch] = [
                'revenue'       => round($revenue, 2),
                'expenses'      => round($expenses, 2),
                'profitability' => round($profitability, 2),
                'cash_position' => round($cashPosition, 2),
                'outstanding'   => round($outstanding, 2),
            ];

            $totalRevenue += $revenue;
            $totalExpenses += $expenses;
            $totalProfitability += $profitability;
            $totalCashPosition += $cashPosition;
            $totalOutstanding += $outstanding;
        }

        return [
            'branches' => $branches,
            'totals'   => [
                'revenue'       => round($totalRevenue, 2),
                'expenses'      => round($totalExpenses, 2),
                'profitability' => round($totalProfitability, 2),
                'cash_position' => round($totalCashPosition, 2),
                'outstanding'   => round($totalOutstanding, 2),
            ],
        ];
    }

    /**
     * Get aggregated revenue per branch with percentage of total.
     */
    public function getRevenueByBranch(string $fromDate, string $toDate): Collection
    {
        // TODO: Implement in task 2.3
        return collect();
    }

    /**
     * Get detailed revenue line items for a specific branch.
     */
    public function getRevenueDetailForBranch(string $branch, string $fromDate, string $toDate): Collection
    {
        // TODO: Implement in task 2.3
        return collect();
    }

    /**
     * Get expenses grouped by branch and expense_type.
     *
     * Query: SELECT branch, expense_type, COUNT(*) as count, SUM(amount) as total
     *        FROM expenses WHERE status != 'rejected'
     *        AND expense_date BETWEEN fromDate AND toDate
     *        GROUP BY branch, expense_type
     *
     * Returns collection grouped by branch with expense_type breakdown
     * and consolidated total per branch (sum across all types).
     *
     * @param string $fromDate Y-m-d
     * @param string $toDate   Y-m-d
     * @return Collection
     */
    public function getExpensesByBranch(string $fromDate, string $toDate): Collection
    {
        $rows = Expense::query()
            ->select('branch', 'expense_type', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->where('status', '!=', 'rejected')
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->groupBy('branch', 'expense_type')
            ->get();

        // Group by branch and include consolidated total per branch
        $grouped = $rows->groupBy('branch')->map(function ($items, $branch) {
            $types = $items->map(function ($item) {
                return [
                    'expense_type' => $item->expense_type,
                    'count'        => (int) $item->count,
                    'total'        => (float) $item->total,
                ];
            })->values();

            return (object) [
                'branch' => $branch,
                'types'  => $types,
                'total'  => (float) $items->sum('total'),
                'count'  => (int) $items->sum('count'),
            ];
        });

        return $grouped->values();
    }

    /**
     * Get detailed expense line items for a specific branch.
     *
     * Query: SELECT expense_no, expense_date, expense_type, description, amount, paid_to, status
     *        FROM expenses WHERE branch = X AND status != 'rejected'
     *        AND expense_date BETWEEN fromDate AND toDate
     *        ORDER BY expense_date DESC
     *
     * @param string $branch
     * @param string $fromDate Y-m-d
     * @param string $toDate   Y-m-d
     * @return Collection
     */
    public function getExpenseDetailForBranch(string $branch, string $fromDate, string $toDate): Collection
    {
        return Expense::query()
            ->select('expense_no', 'expense_date', 'expense_type', 'description', 'amount', 'paid_to', 'status')
            ->where('branch', $branch)
            ->where('status', '!=', 'rejected')
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->orderBy('expense_date', 'desc')
            ->get();
    }

    /**
     * Get profitability comparison for all branches.
     */
    public function getProfitabilityByBranch(string $fromDate, string $toDate): Collection
    {
        $revenueData = LedgerEntry::query()
            ->join('accounts', 'ledger_entries.account_id', '=', 'accounts.id')
            ->where('accounts.type', 'income')
            ->whereBetween('ledger_entries.date', [$fromDate, $toDate])
            ->groupBy('ledger_entries.branch')
            ->select('ledger_entries.branch', DB::raw('COALESCE(SUM(ledger_entries.credit), 0) as total_revenue'))
            ->pluck('total_revenue', 'branch');

        $expenseData = Expense::query()
            ->where('status', '!=', 'rejected')
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->groupBy('branch')
            ->select('branch', DB::raw('COALESCE(SUM(amount), 0) as total_expenses'))
            ->pluck('total_expenses', 'branch');

        $profitability = collect();
        foreach (self::BRANCHES as $branch) {
            $revenue = (float) ($revenueData[$branch] ?? 0);
            $expenses = (float) ($expenseData[$branch] ?? 0);
            $netProfit = $revenue - $expenses;
            $profitMargin = $revenue > 0
                ? round((($revenue - $expenses) / $revenue) * 100, 2)
                : 0;

            $profitability->push((object) [
                'branch'        => $branch,
                'revenue'       => round($revenue, 2),
                'expenses'      => round($expenses, 2),
                'net_profit'    => round($netProfit, 2),
                'profit_margin' => $profitMargin,
            ]);
        }
        return $profitability;
    }

    /**
     * Get cash position (opening, receipts, payments, closing) per branch.
     *
     * Opening balance = account opening_balance + sum(debit) - sum(credit) for entries before from_date
     * Receipts = sum(debit) within the date range
     * Payments = sum(credit) within the date range
     * Closing = opening + receipts - payments
     *
     * Uses cash-type accounts with codes 1101 (Cash In Hand) and 1102 (Petty Cash).
     */
    public function getCashPositionByBranch(string $fromDate, string $toDate): Collection
    {
        $cashAccountIds = Account::whereIn('code', ['1101', '1102'])->pluck('id');

        if ($cashAccountIds->isEmpty()) {
            // No cash accounts exist — return all branches with zero values
            $result = collect();
            foreach (self::BRANCHES as $branch) {
                $result->push((object) [
                    'branch'          => $branch,
                    'opening_balance' => 0,
                    'receipts'        => 0,
                    'payments'        => 0,
                    'closing_balance' => 0,
                ]);
            }
            return $result;
        }

        // Get opening balance components per branch (entries before from_date)
        $openingData = LedgerEntry::query()
            ->whereIn('account_id', $cashAccountIds)
            ->where('date', '<', $fromDate)
            ->groupBy('branch')
            ->select(
                'branch',
                DB::raw('COALESCE(SUM(debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(credit), 0) as total_credit')
            )
            ->get()
            ->keyBy('branch');

        // Get period activity per branch (entries within date range)
        $periodData = LedgerEntry::query()
            ->whereIn('account_id', $cashAccountIds)
            ->whereBetween('date', [$fromDate, $toDate])
            ->groupBy('branch')
            ->select(
                'branch',
                DB::raw('COALESCE(SUM(debit), 0) as receipts'),
                DB::raw('COALESCE(SUM(credit), 0) as payments')
            )
            ->get()
            ->keyBy('branch');

        // Get sum of opening_balance from cash accounts (shared across branches)
        $accountOpeningBalance = (float) Account::whereIn('id', $cashAccountIds)->sum('opening_balance');

        // Build result for all branches
        $result = collect();
        foreach (self::BRANCHES as $branch) {
            $openingEntry = $openingData->get($branch);
            $periodEntry = $periodData->get($branch);

            // Opening = sum(debit) - sum(credit) for entries before from_date
            // Note: account opening_balance is not branch-specific, so we exclude it
            // to avoid double-counting. The opening is purely from ledger entries before from_date.
            $openingBalance = 0;
            if ($openingEntry) {
                $openingBalance = (float) $openingEntry->total_debit - (float) $openingEntry->total_credit;
            }

            $receipts = $periodEntry ? (float) $periodEntry->receipts : 0;
            $payments = $periodEntry ? (float) $periodEntry->payments : 0;
            $closingBalance = $openingBalance + $receipts - $payments;

            $result->push((object) [
                'branch'          => $branch,
                'opening_balance' => round($openingBalance, 2),
                'receipts'        => round($receipts, 2),
                'payments'        => round($payments, 2),
                'closing_balance' => round($closingBalance, 2),
            ]);
        }

        return $result;
    }

    /**
     * Get outstanding summary per branch (receivable, payable, net, count).
     *
     * Query: outstanding WHERE status != 'paid', grouped by branch and type
     * If fromDate/toDate provided: filter by invoice_date within range
     * If null: show all non-paid records regardless of invoice_date
     *
     * Returns collection with ALL 7 branches, each containing:
     * total_receivable, total_payable, net_position, entry_count
     *
     * @param string|null $fromDate Y-m-d
     * @param string|null $toDate   Y-m-d
     * @return Collection
     */
    public function getOutstandingByBranch(?string $fromDate, ?string $toDate): Collection
    {
        $query = Outstanding::query()
            ->select(
                'branch',
                'type',
                DB::raw('COALESCE(SUM(pending_amount), 0) as total_pending'),
                DB::raw('COUNT(*) as entry_count')
            )
            ->where('status', '!=', 'paid')
            ->groupBy('branch', 'type');

        // Only filter by invoice_date if both dates are provided
        if ($fromDate && $toDate) {
            $query->whereBetween('invoice_date', [$fromDate, $toDate]);
        }

        $rows = $query->get();

        // Group by branch for easy lookup
        $grouped = $rows->groupBy('branch');

        // Build result for ALL 7 branches
        $result = collect();
        foreach (self::BRANCHES as $branch) {
            $branchData = $grouped->get($branch, collect());

            $receivableRow = $branchData->firstWhere('type', 'receivable');
            $payableRow = $branchData->firstWhere('type', 'payable');

            $totalReceivable = $receivableRow ? (float) $receivableRow->total_pending : 0;
            $totalPayable = $payableRow ? (float) $payableRow->total_pending : 0;
            $receivableCount = $receivableRow ? (int) $receivableRow->entry_count : 0;
            $payableCount = $payableRow ? (int) $payableRow->entry_count : 0;

            $result->push((object) [
                'branch'           => $branch,
                'total_receivable' => round($totalReceivable, 2),
                'total_payable'    => round($totalPayable, 2),
                'net_position'     => round($totalReceivable - $totalPayable, 2),
                'entry_count'      => $receivableCount + $payableCount,
            ]);
        }

        return $result;
    }

    /**
     * Get paginated outstanding detail for a specific branch.
     *
     * Query: outstanding WHERE branch = X AND status != 'paid'
     * If fromDate/toDate provided: filter by invoice_date
     * Order by invoice_date DESC, paginated at 30 per page.
     *
     * @param string      $branch
     * @param string|null $fromDate Y-m-d
     * @param string|null $toDate   Y-m-d
     * @return LengthAwarePaginator
     */
    public function getOutstandingDetailForBranch(string $branch, ?string $fromDate, ?string $toDate): LengthAwarePaginator
    {
        $query = Outstanding::query()
            ->select(
                'party_type',
                'party_name',
                'invoice_ref',
                'invoice_date',
                'total_amount',
                'paid_amount',
                'pending_amount',
                'due_date',
                'status'
            )
            ->where('branch', $branch)
            ->where('status', '!=', 'paid');

        // Only filter by invoice_date if both dates are provided
        if ($fromDate && $toDate) {
            $query->whereBetween('invoice_date', [$fromDate, $toDate]);
        }

        return $query->orderBy('invoice_date', 'desc')->paginate(30);
    }
}
