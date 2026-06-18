<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\GstSetting;
use App\Services\BranchAccountingService;
use App\Services\GstService;
use App\Traits\OfficeScopeTrait;
use Illuminate\Http\Request;

class GstReportController extends Controller
{
    use OfficeScopeTrait;

    private GstService $gstService;

    public function __construct(GstService $gstService)
    {
        $this->middleware(['auth', 'role:SuperAdmin']);
        $this->gstService = $gstService;
    }

    /**
     * GST Summary Report — consolidated output/input/net tax totals.
     */
    public function summary(Request $request)
    {
        [$defaultFrom, $defaultTo] = $this->gstService->resolveFinancialYearDates();

        $fromDate = $request->from_date ?? $defaultFrom;
        $toDate = $request->to_date ?? $defaultTo;

        if ($toDate < $fromDate) {
            return redirect()->back()->with('error', 'Invalid date range: "To Date" cannot be before "From Date".');
        }

        $selectedBranch = $request->branch;
        $summaryData = $this->gstService->getSummaryData($fromDate, $toDate, $selectedBranch);
        $companyGst = GstSetting::get('company_gst_number');
        $branches = BranchAccountingService::BRANCHES;

        return view('accounting.gst.summary', compact(
            'summaryData', 'companyGst', 'fromDate', 'toDate', 'selectedBranch', 'branches'
        ));
    }

    /**
     * GST Collection Report — output tax collected on GRs.
     */
    public function collection(Request $request)
    {
        [$defaultFrom, $defaultTo] = $this->gstService->resolveFinancialYearDates();

        $fromDate = $request->from_date ?? $defaultFrom;
        $toDate = $request->to_date ?? $defaultTo;

        if ($toDate < $fromDate) {
            return redirect()->back()->with('error', 'Invalid date range: "To Date" cannot be before "From Date".');
        }

        $selectedBranch = $request->branch;
        $selectedGstType = $request->gst_type;
        $collectionData = $this->gstService->getCollectionData($fromDate, $toDate, $selectedBranch, $selectedGstType);
        $entries = $collectionData['entries'];
        $totals = $collectionData['totals'];
        $branches = BranchAccountingService::BRANCHES;

        return view('accounting.gst.collection', compact(
            'entries', 'totals', 'fromDate', 'toDate', 'selectedBranch', 'selectedGstType', 'branches'
        ));
    }

    /**
     * GST Liability Report — monthly output vs input with net liability.
     */
    public function liability(Request $request)
    {
        [$defaultFrom, $defaultTo] = $this->gstService->resolveFinancialYearDates();

        $fromDate = $request->from_date ?? $defaultFrom;
        $toDate = $request->to_date ?? $defaultTo;

        if ($toDate < $fromDate) {
            return redirect()->back()->with('error', 'Invalid date range: "To Date" cannot be before "From Date".');
        }

        $liabilityData = $this->gstService->getLiabilityData($fromDate, $toDate);
        $monthlyData = $liabilityData['months'];
        $grandTotal = $liabilityData['grand_total'];

        return view('accounting.gst.liability', compact(
            'monthlyData', 'grandTotal', 'fromDate', 'toDate'
        ));
    }

    /**
     * Input Tax Report — GST paid on expenses.
     */
    public function inputTax(Request $request)
    {
        [$defaultFrom, $defaultTo] = $this->gstService->resolveFinancialYearDates();

        $fromDate = $request->from_date ?? $defaultFrom;
        $toDate = $request->to_date ?? $defaultTo;

        if ($toDate < $fromDate) {
            return redirect()->back()->with('error', 'Invalid date range: "To Date" cannot be before "From Date".');
        }

        $selectedBranch = $request->branch;
        $selectedExpenseType = $request->expense_type;
        $inputTaxData = $this->gstService->getInputTaxData($fromDate, $toDate, $selectedBranch, $selectedExpenseType);
        $entries = $inputTaxData['entries'];
        $totals = $inputTaxData['totals'];
        $branches = BranchAccountingService::BRANCHES;
        $expenseTypes = ['diesel', 'driver_salary', 'repair', 'tyre', 'office', 'branch', 'misc'];

        return view('accounting.gst.input-tax', compact(
            'entries', 'totals', 'fromDate', 'toDate', 'selectedBranch', 'selectedExpenseType', 'branches', 'expenseTypes'
        ));
    }

    /**
     * Output Tax Report — GST charged on freight services, grouped by type/rate.
     */
    public function outputTax(Request $request)
    {
        [$defaultFrom, $defaultTo] = $this->gstService->resolveFinancialYearDates();

        $fromDate = $request->from_date ?? $defaultFrom;
        $toDate = $request->to_date ?? $defaultTo;

        if ($toDate < $fromDate) {
            return redirect()->back()->with('error', 'Invalid date range: "To Date" cannot be before "From Date".');
        }

        $selectedBranch = $request->branch;
        $selectedRate = $request->rate;
        $outputTaxData = $this->gstService->getOutputTaxData($fromDate, $toDate, $selectedBranch, $selectedRate);
        $entries = $outputTaxData['entries'];
        $summary = $outputTaxData['summary'];
        $branches = BranchAccountingService::BRANCHES;

        return view('accounting.gst.output-tax', compact(
            'entries', 'summary', 'fromDate', 'toDate', 'selectedBranch', 'selectedRate', 'branches'
        ));
    }

    /**
     * GST Settings page — display current settings.
     */
    public function settings(Request $request)
    {
        $companyGstNumber = GstSetting::get('company_gst_number');
        $defaultGstRate = GstSetting::get('default_gst_rate');
        $companyState = GstSetting::get('company_state');

        return view('accounting.gst.settings', compact(
            'companyGstNumber', 'defaultGstRate', 'companyState'
        ));
    }

    /**
     * Update GST settings.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'company_gst_number' => 'nullable|string|max:20',
            'default_gst_rate'   => 'required|in:5,12',
            'company_state'      => 'required|string|max:50',
        ]);

        GstSetting::set('company_gst_number', $request->company_gst_number);
        GstSetting::set('default_gst_rate', $request->default_gst_rate);
        GstSetting::set('company_state', $request->company_state);

        return redirect()->route('accounting.gst.settings')->with('success', 'GST settings updated successfully.');
    }
}
