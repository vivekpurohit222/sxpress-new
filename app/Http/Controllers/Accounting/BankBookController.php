<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Models\Accounting\BankAccount;
use App\Models\Accounting\LedgerEntry;
use App\Traits\OfficeScopeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Bank Book Controller
 *
 * Shows deposits/withdrawals for bank accounts with cheque tracking
 * and reconciliation status (cleared/uncleared/bounced).
 */
class BankBookController extends Controller
{
    use OfficeScopeTrait;

    public function __construct()
    {
        $this->middleware(['auth', 'role:SuperAdmin']);
    }

    /**
     * Bank Book main view — select bank, show statement.
     */
    public function index(Request $request)
    {
        $banks = BankAccount::active()->with('account')->get();
        $selectedBankId = $request->bank_id;
        $fromDate = $request->from_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? Carbon::now()->format('Y-m-d');
        $branch = $this->isSuperAdmin() ? ($request->branch ?: null) : $this->currentOffice();

        $statement = null;

        if ($selectedBankId) {
            $bank = BankAccount::with('account')->findOrFail($selectedBankId);
            $accountId = $bank->account_id;

            // Opening balance before fromDate
            $openingQuery = LedgerEntry::where('account_id', $accountId)->whereDate('date', '<', $fromDate);
            if ($branch) $openingQuery->where('branch', $branch);
            $openingData = $openingQuery->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')->first();
            $openingBalance = (float)$bank->opening_balance + (float)$openingData->d - (float)$openingData->c;

            // Entries in date range
            $query = LedgerEntry::with('voucher')
                ->where('account_id', $accountId)
                ->whereDate('date', '>=', $fromDate)
                ->whereDate('date', '<=', $toDate);
            if ($branch) $query->where('branch', $branch);
            $entries = $query->orderBy('date')->orderBy('id')->get();

            // Running balance
            $runningBalance = $openingBalance;
            $entriesWithBalance = [];
            foreach ($entries as $entry) {
                $runningBalance += (float)$entry->debit - (float)$entry->credit;
                $entriesWithBalance[] = ['entry' => $entry, 'balance' => round($runningBalance, 2)];
            }

            $statement = [
                'bank' => $bank,
                'opening_balance' => round($openingBalance, 2),
                'entries' => $entriesWithBalance,
                'closing_balance' => round($runningBalance, 2),
                'total_deposits' => $entries->sum('debit'),
                'total_withdrawals' => $entries->sum('credit'),
                'uncleared_count' => $entries->where('reconciliation_status', 'uncleared')->where('cheque_no', '!=', '')->count(),
            ];
        }

        $branches = $this->getBranchOptions();

        return view('accounting.bankbook.index', compact(
            'banks', 'selectedBankId', 'fromDate', 'toDate', 'branch', 'statement', 'branches'
        ));
    }

    /**
     * Toggle reconciliation status for a ledger entry.
     * POST /accounting/bank-book/reconcile/{entry_id}
     */
    public function reconcile(Request $request, $entryId)
    {
        $entry = LedgerEntry::findOrFail($entryId);

        if (!$this->isSuperAdmin() && $entry->branch !== $this->currentOffice()) {
            abort(403);
        }

        $newStatus = $request->input('status', 'cleared');

        $entry->update([
            'reconciliation_status' => $newStatus,
            'cleared_date' => $newStatus === 'cleared' ? now()->format('Y-m-d') : null,
        ]);

        return response()->json(['success' => true, 'status' => $newStatus]);
    }

    /**
     * Bank account management (CRUD) — SuperAdmin only.
     */
    public function manageBanks()
    {
        $banks = BankAccount::with('account')->get();
        $bankLedgerAccounts = Account::where('code', 'like', '111%')->active()->get(); // 1110, 1111 etc.

        return view('accounting.bankbook.manage', compact('banks', 'bankLedgerAccounts'));
    }

    public function storeBank(Request $request)
    {
        $validated = $request->validate([
            'bank_name'      => 'required|string|max:150',
            'account_number' => 'required|string|max:30|unique:bank_accounts,account_number',
            'ifsc_code'      => 'nullable|string|max:15',
            'branch_name'    => 'nullable|string|max:150',
            'account_type'   => 'required|in:current,savings',
            'account_id'     => 'required|exists:accounts,id',
            'opening_balance'=> 'nullable|numeric',
        ]);

        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $validated['is_active'] = true;

        BankAccount::create($validated);

        return redirect()->route('accounting.bankbook.manage')
            ->with('success', 'Bank account added.');
    }
}
