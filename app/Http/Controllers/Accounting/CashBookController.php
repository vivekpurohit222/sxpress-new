<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Models\Accounting\LedgerEntry;
use App\Traits\OfficeScopeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Cash Book Controller
 *
 * Displays receipts and payments for cash accounts (1101 Cash In Hand, 1102 Petty Cash)
 * with opening/closing balance, date filtering, and branch isolation.
 *
 * This reads directly from ledger_entries — works with:
 * - Manual vouchers (created via Voucher module)
 * - Auto-generated entries (from GR/Freight Memo integration in Phase 8)
 */
class CashBookController extends Controller
{
    use OfficeScopeTrait;

    public function __construct()
    {
        $this->middleware(['auth', 'role:SuperAdmin']);
    }

    /**
     * Daily Cash Book — shows all cash receipts and payments for a date.
     */
    public function daily(Request $request)
    {
        $date = $request->date ?? Carbon::now()->format('Y-m-d');
        $branch = $this->isSuperAdmin() ? ($request->branch ?: null) : $this->currentOffice();

        // Get all cash accounts (1101, 1102)
        $cashAccounts = Account::whereIn('code', ['1101', '1102'])->pluck('id')->toArray();

        if (empty($cashAccounts)) {
            return view('accounting.cashbook.daily', [
                'date' => $date, 'branch' => $branch,
                'openingBalance' => 0, 'receipts' => collect(), 'payments' => collect(),
                'totalReceipts' => 0, 'totalPayments' => 0, 'closingBalance' => 0,
                'branches' => $this->getBranchOptions(),
            ]);
        }

        // Opening balance = all cash entries BEFORE this date
        $openingQuery = LedgerEntry::whereIn('account_id', $cashAccounts)
            ->whereDate('date', '<', $date);
        if ($branch) $openingQuery->where('branch', $branch);

        $openingData = $openingQuery->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')->first();
        $cashAccountOpening = Account::whereIn('id', $cashAccounts)->sum('opening_balance');
        $openingBalance = (float)$cashAccountOpening + (float)$openingData->d - (float)$openingData->c;

        // Today's entries
        $todayQuery = LedgerEntry::with(['voucher', 'account'])
            ->whereIn('account_id', $cashAccounts)
            ->whereDate('date', $date);
        if ($branch) $todayQuery->where('branch', $branch);

        $entries = $todayQuery->orderBy('id')->get();

        $receipts = $entries->where('debit', '>', 0); // money coming IN
        $payments = $entries->where('credit', '>', 0); // money going OUT

        $totalReceipts = $receipts->sum('debit');
        $totalPayments = $payments->sum('credit');
        $closingBalance = $openingBalance + $totalReceipts - $totalPayments;

        $branches = $this->getBranchOptions();

        return view('accounting.cashbook.daily', compact(
            'date', 'branch', 'openingBalance', 'receipts', 'payments',
            'totalReceipts', 'totalPayments', 'closingBalance', 'branches'
        ));
    }

    /**
     * Monthly Cash Book Summary — daily totals for a month.
     */
    public function monthly(Request $request)
    {
        $month = $request->month ?? Carbon::now()->format('Y-m');
        $branch = $this->isSuperAdmin() ? ($request->branch ?: null) : $this->currentOffice();

        $startDate = Carbon::parse($month . '-01');
        $endDate = $startDate->copy()->endOfMonth();

        $cashAccounts = Account::whereIn('code', ['1101', '1102'])->pluck('id')->toArray();

        // Opening balance at start of month
        $openingQuery = LedgerEntry::whereIn('account_id', $cashAccounts)
            ->whereDate('date', '<', $startDate);
        if ($branch) $openingQuery->where('branch', $branch);

        $openingData = $openingQuery->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')->first();
        $cashAccountOpening = Account::whereIn('id', $cashAccounts)->sum('opening_balance');
        $openingBalance = (float)$cashAccountOpening + (float)$openingData->d - (float)$openingData->c;

        // Daily summaries for the month
        $dailyQuery = LedgerEntry::whereIn('account_id', $cashAccounts)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);
        if ($branch) $dailyQuery->where('branch', $branch);

        $dailyData = $dailyQuery
            ->selectRaw('DATE(date) as day, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Build daily summary with running balance
        $runningBalance = $openingBalance;
        $days = [];
        foreach ($dailyData as $day) {
            $runningBalance += (float)$day->total_debit - (float)$day->total_credit;
            $days[] = [
                'date' => $day->day,
                'receipts' => (float) $day->total_debit,
                'payments' => (float) $day->total_credit,
                'balance' => round($runningBalance, 2),
            ];
        }

        $closingBalance = $runningBalance;
        $totalReceipts = $dailyData->sum('total_debit');
        $totalPayments = $dailyData->sum('total_credit');

        $branches = $this->getBranchOptions();

        return view('accounting.cashbook.monthly', compact(
            'month', 'branch', 'openingBalance', 'days',
            'totalReceipts', 'totalPayments', 'closingBalance', 'branches'
        ));
    }

    /**
     * Cash Flow Report — receipts and payments grouped by contra account (category).
     * Shows WHERE the cash came from (which income accounts) and WHERE it went (which expense accounts).
     */
    public function cashFlow(Request $request)
    {
        $fromDate = $request->from_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? Carbon::now()->format('Y-m-d');
        $branch = $this->isSuperAdmin() ? ($request->branch ?: null) : $this->currentOffice();

        $cashAccounts = Account::whereIn('code', ['1101', '1102'])->pluck('id')->toArray();

        if (empty($cashAccounts)) {
            return view('accounting.cashbook.cashflow', [
                'fromDate' => $fromDate, 'toDate' => $toDate, 'branch' => $branch,
                'inflows' => collect(), 'outflows' => collect(),
                'totalIn' => 0, 'totalOut' => 0, 'branches' => $this->getBranchOptions(),
            ]);
        }

        // Get all vouchers that have a cash entry in this period
        $cashEntries = LedgerEntry::whereIn('account_id', $cashAccounts)
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate);
        if ($branch) $cashEntries->where('branch', $branch);
        $voucherIds = $cashEntries->pluck('voucher_id')->unique();

        // Get the CONTRA entries (non-cash side) grouped by account
        $contraEntries = LedgerEntry::whereIn('voucher_id', $voucherIds)
            ->whereNotIn('account_id', $cashAccounts)
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate);
        if ($branch) $contraEntries->where('branch', $branch);

        $grouped = $contraEntries->get()->groupBy('account_id');

        $inflows = collect(); // credit side of contra = cash received FROM these accounts
        $outflows = collect(); // debit side of contra = cash paid TO these accounts

        foreach ($grouped as $accountId => $entries) {
            $account = Account::find($accountId);
            if (!$account) continue;

            $totalCredit = $entries->sum('credit'); // contra credit = cash came IN
            $totalDebit = $entries->sum('debit');   // contra debit = cash went OUT

            if ($totalCredit > 0) {
                $inflows->push(['account' => $account, 'amount' => $totalCredit]);
            }
            if ($totalDebit > 0) {
                $outflows->push(['account' => $account, 'amount' => $totalDebit]);
            }
        }

        $inflows = $inflows->sortByDesc('amount')->values();
        $outflows = $outflows->sortByDesc('amount')->values();
        $totalIn = $inflows->sum('amount');
        $totalOut = $outflows->sum('amount');
        $branches = $this->getBranchOptions();

        return view('accounting.cashbook.cashflow', compact(
            'fromDate', 'toDate', 'branch', 'inflows', 'outflows', 'totalIn', 'totalOut', 'branches'
        ));
    }
}
