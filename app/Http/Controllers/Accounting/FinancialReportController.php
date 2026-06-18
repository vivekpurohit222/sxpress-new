<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\BranchAccountingService;
use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    private FinancialReportService $reportService;
    private BranchAccountingService $branchService;

    public function __construct(
        FinancialReportService $reportService,
        BranchAccountingService $branchService
    ) {
        $this->middleware(['auth', 'role:SuperAdmin']);
        $this->reportService = $reportService;
        $this->branchService = $branchService;
    }

    /**
     * Reports index page — lists all available financial reports.
     */
    public function index()
    {
        return view('accounting.reports.index');
    }

    /**
     * Day Book — all approved vouchers and entries for a specific date.
     */
    public function dayBook(Request $request)
    {
        $date = $request->date ?? Carbon::today()->format('Y-m-d');
        $selectedBranch = $request->branch;

        $data = $this->reportService->getDayBook($date, $selectedBranch);
        $vouchers = $data['vouchers'];
        $totals = $data['totals'];
        $branches = BranchAccountingService::BRANCHES;

        return view('accounting.reports.day-book', compact(
            'vouchers', 'totals', 'date', 'selectedBranch', 'branches'
        ));
    }

    /**
     * Trial Balance — all accounts with debit/credit balances for a period.
     */
    public function trialBalance(Request $request)
    {
        [$defaultFrom, $defaultTo] = $this->branchService->resolveFinancialYearDates();

        $fromDate = $request->from_date ?? $defaultFrom;
        $toDate = $request->to_date ?? $defaultTo;

        if ($toDate < $fromDate) {
            return redirect()->back()->with('error', 'Invalid date range: "To Date" cannot be before "From Date".');
        }

        $selectedBranch = $request->branch;
        $data = $this->reportService->getTrialBalance($fromDate, $toDate, $selectedBranch);
        $accounts = $data['accounts'];
        $totals = $data['totals'];
        $branches = BranchAccountingService::BRANCHES;

        return view('accounting.reports.trial-balance', compact(
            'accounts', 'totals', 'fromDate', 'toDate', 'selectedBranch', 'branches'
        ));
    }

    /**
     * Profit & Loss Statement — income vs expenses for a period.
     */
    public function profitAndLoss(Request $request)
    {
        [$defaultFrom, $defaultTo] = $this->branchService->resolveFinancialYearDates();

        $fromDate = $request->from_date ?? $defaultFrom;
        $toDate = $request->to_date ?? $defaultTo;

        if ($toDate < $fromDate) {
            return redirect()->back()->with('error', 'Invalid date range: "To Date" cannot be before "From Date".');
        }

        $selectedBranch = $request->branch;
        $data = $this->reportService->getProfitAndLoss($fromDate, $toDate, $selectedBranch);
        $income = $data['income'];
        $expenses = $data['expenses'];
        $totalIncome = $data['total_income'];
        $totalExpenses = $data['total_expenses'];
        $netProfit = $data['net_profit'];
        $branches = BranchAccountingService::BRANCHES;

        return view('accounting.reports.profit-and-loss', compact(
            'income', 'expenses', 'totalIncome', 'totalExpenses', 'netProfit',
            'fromDate', 'toDate', 'selectedBranch', 'branches'
        ));
    }

    /**
     * Balance Sheet — assets = liabilities + equity + net profit as of a date.
     */
    public function balanceSheet(Request $request)
    {
        $asOfDate = $request->as_of_date ?? Carbon::today()->format('Y-m-d');
        $selectedBranch = $request->branch;

        $data = $this->reportService->getBalanceSheet($asOfDate, $selectedBranch);
        $assets = $data['assets'];
        $liabilities = $data['liabilities'];
        $equity = $data['equity'];
        $netProfit = $data['net_profit'];
        $totalAssets = $data['total_assets'];
        $totalLiabilitiesEquity = $data['total_liabilities_equity'];
        $branches = BranchAccountingService::BRANCHES;

        return view('accounting.reports.balance-sheet', compact(
            'assets', 'liabilities', 'equity', 'netProfit',
            'totalAssets', 'totalLiabilitiesEquity',
            'asOfDate', 'selectedBranch', 'branches'
        ));
    }

    /**
     * Vehicle Profitability Report — revenue vs expenses per vehicle.
     */
    public function vehicleProfitability(Request $request)
    {
        [$defaultFrom, $defaultTo] = $this->branchService->resolveFinancialYearDates();

        $fromDate = $request->from_date ?? $defaultFrom;
        $toDate = $request->to_date ?? $defaultTo;

        if ($toDate < $fromDate) {
            return redirect()->back()->with('error', 'Invalid date range: "To Date" cannot be before "From Date".');
        }

        $selectedBranch = $request->branch;
        $data = $this->reportService->getVehicleProfitability($fromDate, $toDate, $selectedBranch);
        $vehicles = $data['vehicles'];
        $totals = $data['totals'];
        $branches = BranchAccountingService::BRANCHES;

        return view('accounting.reports.vehicle-profitability', compact(
            'vehicles', 'totals', 'fromDate', 'toDate', 'selectedBranch', 'branches'
        ));
    }

    /**
     * Driver Expense Report — expenses grouped by driver.
     */
    public function driverExpenses(Request $request)
    {
        [$defaultFrom, $defaultTo] = $this->branchService->resolveFinancialYearDates();

        $fromDate = $request->from_date ?? $defaultFrom;
        $toDate = $request->to_date ?? $defaultTo;

        if ($toDate < $fromDate) {
            return redirect()->back()->with('error', 'Invalid date range: "To Date" cannot be before "From Date".');
        }

        $selectedBranch = $request->branch;
        $data = $this->reportService->getDriverExpenses($fromDate, $toDate, $selectedBranch);
        $drivers = $data['drivers'];
        $totalExpenses = $data['total_expenses'];
        $branches = BranchAccountingService::BRANCHES;

        return view('accounting.reports.driver-expenses', compact(
            'drivers', 'totalExpenses', 'fromDate', 'toDate', 'selectedBranch', 'branches'
        ));
    }
}
