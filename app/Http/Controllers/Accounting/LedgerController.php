<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Models\Accounting\LedgerEntry;
use App\Services\AccountingService;
use App\Traits\OfficeScopeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    use OfficeScopeTrait;

    private AccountingService $accounting;

    public function __construct(AccountingService $accounting)
    {
        $this->middleware(['auth', 'role:SuperAdmin']);
        $this->accounting = $accounting;
    }

    /**
     * Ledger index — list all accounts with current balance.
     * Supports type-based quick filters (Customer, Vehicle, Driver, Branch ledgers).
     */
    public function index(Request $request)
    {
        $type = $request->type;
        $branch = $this->isSuperAdmin() ? $request->branch : $this->currentOffice();
        $filter = $request->filter; // customer, vehicle, driver, branch, expense

        $query = Account::active()->transactional()->orderBy('code');

        if ($type) {
            $query->where('type', $type);
        }

        // Quick filters per document: Customer/Consignor/Consignee/Vehicle/Driver/Branch/Expense
        if ($filter === 'receivable') {
            $query->where('code', 'like', '120%'); // 1201-1204 receivables
        } elseif ($filter === 'payable') {
            $query->where('code', 'like', '210%'); // 2101-2104 payables
        } elseif ($filter === 'vehicle') {
            $query->where('code', 'like', '410%'); // 4100 vehicle expenses
        } elseif ($filter === 'staff') {
            $query->where('code', 'like', '430%'); // 4300 staff expenses
        } elseif ($filter === 'office') {
            $query->where('code', 'like', '440%'); // 4400 office expenses
        }

        $accounts = $query->get()->map(function ($account) use ($branch) {
            $account->current_balance = LedgerEntry::getBalance(
                $account->id,
                now()->format('Y-m-d'),
                $this->isSuperAdmin() ? null : $branch
            );
            return $account;
        });

        $types = ['asset', 'liability', 'income', 'expense', 'equity'];
        $branches = $this->getBranchOptions();

        return view('accounting.ledger.index', compact('accounts', 'types', 'type', 'branches', 'branch', 'filter'));
    }

    /**
     * Show detailed ledger statement for a single account.
     */
    public function show(Request $request, $id)
    {
        $fromDate = $request->from_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? Carbon::now()->format('Y-m-d');
        $branch = $this->isSuperAdmin() ? $request->branch : $this->currentOffice();

        $statement = $this->accounting->getLedgerStatement($id, $fromDate, $toDate, $branch);

        $branches = $this->getBranchOptions();

        return view('accounting.ledger.show', compact('statement', 'fromDate', 'toDate', 'branches', 'branch'));
    }
}
