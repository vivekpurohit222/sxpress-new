<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\BranchAccountingService;
use App\Traits\OfficeScopeTrait;
use Illuminate\Http\Request;

class BranchAccountingController extends Controller
{
    use OfficeScopeTrait;

    private BranchAccountingService $service;

    public function __construct(BranchAccountingService $service)
    {
        $this->middleware(['auth', 'role:SuperAdmin']);
        $this->service = $service;
    }

    /**
     * Branch accounting dashboard — summary cards for all branches.
     */
    public function dashboard(Request $request)
    {
        [$defaultFrom, $defaultTo] = $this->service->resolveFinancialYearDates();

        $fromDate = $request->from_date ?? $defaultFrom;
        $toDate = $request->to_date ?? $defaultTo;

        if ($toDate < $fromDate) {
            return redirect()->back()->with('error', 'Invalid date range: "To Date" cannot be before "From Date".');
        }

        $metrics = $this->service->getDashboardMetrics($fromDate, $toDate);
        $branches = $metrics['branches'];
        $totals = $metrics['totals'];

        return view('accounting.branch.dashboard', compact('branches', 'totals', 'fromDate', 'toDate'));
    }

    /**
     * Revenue comparison across branches with optional drill-down.
     */
    public function revenue(Request $request)
    {
        [$defaultFrom, $defaultTo] = $this->service->resolveFinancialYearDates();

        $fromDate = $request->from_date ?? $defaultFrom;
        $toDate = $request->to_date ?? $defaultTo;

        if ($toDate < $fromDate) {
            return redirect()->back()->with('error', 'Invalid date range: "To Date" cannot be before "From Date".');
        }

        $revenueData = $this->service->getRevenueByBranch($fromDate, $toDate);

        // Drill-down for a specific branch
        $selectedBranch = $request->branch;
        $branchDetail = null;
        if ($selectedBranch) {
            $branchDetail = $this->service->getRevenueDetailForBranch($selectedBranch, $fromDate, $toDate);
        }

        return view('accounting.branch.revenue', compact('revenueData', 'selectedBranch', 'branchDetail', 'fromDate', 'toDate'));
    }

    /**
     * Expense breakdown by branch and type with optional drill-down.
     */
    public function expenses(Request $request)
    {
        [$defaultFrom, $defaultTo] = $this->service->resolveFinancialYearDates();

        $fromDate = $request->from_date ?? $defaultFrom;
        $toDate = $request->to_date ?? $defaultTo;

        if ($toDate < $fromDate) {
            return redirect()->back()->with('error', 'Invalid date range: "To Date" cannot be before "From Date".');
        }

        $expenseData = $this->service->getExpensesByBranch($fromDate, $toDate);

        // Drill-down for a specific branch
        $selectedBranch = $request->branch;
        $branchDetail = null;
        if ($selectedBranch) {
            $branchDetail = $this->service->getExpenseDetailForBranch($selectedBranch, $fromDate, $toDate);
        }

        return view('accounting.branch.expenses', compact('expenseData', 'selectedBranch', 'branchDetail', 'fromDate', 'toDate'));
    }

    /**
     * Profitability comparison: revenue vs expenses per branch.
     */
    public function profitability(Request $request)
    {
        [$defaultFrom, $defaultTo] = $this->service->resolveFinancialYearDates();

        $fromDate = $request->from_date ?? $defaultFrom;
        $toDate = $request->to_date ?? $defaultTo;

        if ($toDate < $fromDate) {
            return redirect()->back()->with('error', 'Invalid date range: "To Date" cannot be before "From Date".');
        }

        $profitabilityData = $this->service->getProfitabilityByBranch($fromDate, $toDate);

        return view('accounting.branch.profitability', compact('profitabilityData', 'fromDate', 'toDate'));
    }

    /**
     * Cash position: opening, receipts, payments, closing per branch.
     */
    public function cashPosition(Request $request)
    {
        [$defaultFrom, $defaultTo] = $this->service->resolveFinancialYearDates();

        $fromDate = $request->from_date ?? $defaultFrom;
        $toDate = $request->to_date ?? $defaultTo;

        if ($toDate < $fromDate) {
            return redirect()->back()->with('error', 'Invalid date range: "To Date" cannot be before "From Date".');
        }

        $cashData = $this->service->getCashPositionByBranch($fromDate, $toDate);

        return view('accounting.branch.cash-position', compact('cashData', 'fromDate', 'toDate'));
    }

    /**
     * Outstanding receivables/payables per branch with optional drill-down and pagination.
     */
    public function outstanding(Request $request)
    {
        [$defaultFrom, $defaultTo] = $this->service->resolveFinancialYearDates();

        $fromDate = $request->from_date ?? $defaultFrom;
        $toDate = $request->to_date ?? $defaultTo;

        if ($toDate < $fromDate) {
            return redirect()->back()->with('error', 'Invalid date range: "To Date" cannot be before "From Date".');
        }

        $outstandingData = $this->service->getOutstandingByBranch($fromDate, $toDate);

        // Drill-down for a specific branch with pagination
        $selectedBranch = $request->branch;
        $branchDetail = null;
        if ($selectedBranch) {
            $branchDetail = $this->service->getOutstandingDetailForBranch($selectedBranch, $fromDate, $toDate);
        }

        return view('accounting.branch.outstanding', compact('outstandingData', 'selectedBranch', 'branchDetail', 'fromDate', 'toDate'));
    }
}
