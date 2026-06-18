<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Models\Accounting\Expense;
use App\Models\Accounting\LedgerEntry;
use App\Models\Accounting\Voucher;
use App\Models\Vehicle;
use App\Models\truckdriver;
use App\Services\AccountingService;
use App\Traits\OfficeScopeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    use OfficeScopeTrait;

    private AccountingService $accounting;

    public function __construct(AccountingService $accounting)
    {
        $this->middleware(['auth', 'role:SuperAdmin']);
        $this->accounting = $accounting;
    }

    /**
     * List expenses with filters.
     */
    public function index(Request $request)
    {
        $query = Expense::with(['account', 'creator'])->orderByDesc('expense_date')->orderByDesc('id');

        if (!$this->isSuperAdmin()) {
            $query->where('branch', $this->currentOffice());
        } elseif ($request->branch) {
            $query->where('branch', $request->branch);
        }

        if ($type = $request->type) $query->where('expense_type', $type);
        if ($status = $request->status) $query->where('status', $status);
        if ($from = $request->from_date) $query->whereDate('expense_date', '>=', $from);
        if ($to = $request->to_date) $query->whereDate('expense_date', '<=', $to);

        $expenses = $query->paginate(25)->withQueryString();

        // Summary
        $summaryQuery = Expense::query();
        if (!$this->isSuperAdmin()) $summaryQuery->where('branch', $this->currentOffice());
        elseif ($request->branch) $summaryQuery->where('branch', $request->branch);

        $summary = [
            'total_month' => (clone $summaryQuery)->whereMonth('expense_date', now()->month)->whereYear('expense_date', now()->year)->sum('amount'),
            'pending_count' => (clone $summaryQuery)->pending()->count(),
            'pending_amount' => (clone $summaryQuery)->pending()->sum('amount'),
        ];

        $branches = $this->getBranchOptions();
        $types = ['diesel', 'driver_salary', 'repair', 'tyre', 'office', 'branch', 'misc'];

        return view('accounting.expenses.index', compact('expenses', 'summary', 'branches', 'types'));
    }

    /**
     * Create expense form.
     */
    public function create()
    {
        $expenseAccounts = Account::active()->transactional()->where('type', 'expense')->orderBy('code')->get();
        $cashAccounts = Account::active()->transactional()->where('type', 'asset')->whereIn('code', ['1101', '1102', '1110', '1111'])->get();
        $vehicles = Vehicle::where('status', 'active')->orderBy('vehicle_number')->get();
        $drivers = truckdriver::where('status', 1)->orderBy('driver_name')->get();
        $office = $this->currentOffice();
        $types = ['diesel', 'driver_salary', 'repair', 'tyre', 'office', 'branch', 'misc'];

        return view('accounting.expenses.create', compact('expenseAccounts', 'cashAccounts', 'vehicles', 'drivers', 'office', 'types'));
    }

    /**
     * Store expense — creates expense record and auto-posts to ledger.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_date'        => 'required|date|before_or_equal:today',
            'expense_type'        => 'required|in:diesel,driver_salary,repair,tyre,office,branch,misc',
            'description'         => 'required|string|max:300',
            'amount'              => 'required|numeric|min:0.01',
            'paid_to'             => 'nullable|string|max:200',
            'account_id'          => 'required|exists:accounts,id',
            'paid_from_account_id'=> 'required|exists:accounts,id',
            'vehicle_id'          => 'nullable|exists:vehicles,id',
            'driver_id'           => 'nullable|exists:truckdrivers,id',
            'notes'               => 'nullable|string|max:500',
        ]);

        $branch = $this->currentOffice();

        // Generate expense number
        $expenseNo = $this->generateExpenseNo($branch);

        // Create expense record
        $expense = Expense::create([
            'expense_no'          => $expenseNo,
            'expense_date'        => $validated['expense_date'],
            'expense_type'        => $validated['expense_type'],
            'description'         => $validated['description'],
            'amount'              => $validated['amount'],
            'paid_to'             => $validated['paid_to'] ?? '',
            'account_id'          => $validated['account_id'],
            'paid_from_account_id'=> $validated['paid_from_account_id'],
            'vehicle_id'          => $validated['vehicle_id'] ?? null,
            'driver_id'           => $validated['driver_id'] ?? null,
            'branch'              => $branch,
            'status'              => 'approved',
            'approved_by'         => auth()->id(),
            'approved_at'         => now(),
            'created_by_id'       => auth()->id(),
            'notes'               => $validated['notes'] ?? null,
        ]);

        // Auto-post to ledger: Debit Expense, Credit Cash/Bank
        try {
            $voucher = $this->accounting->createVoucher(
                type: 'payment',
                date: $validated['expense_date'],
                narration: Expense::getTypeLabel($validated['expense_type']) . " — {$validated['description']}" . ($validated['paid_to'] ? " | Paid to: {$validated['paid_to']}" : ''),
                entries: [
                    ['account_id' => $validated['account_id'], 'debit' => $validated['amount'], 'credit' => 0],
                    ['account_id' => $validated['paid_from_account_id'], 'debit' => 0, 'credit' => $validated['amount']],
                ],
                branch: $branch,
                meta: [
                    'reference_type' => 'expense',
                    'reference_id' => $expense->id,
                    'created_by_id' => auth()->id(),
                ]
            );

            $expense->update(['voucher_id' => $voucher->id]);

        } catch (\Throwable $e) {
            \Log::error("Expense accounting post failed: " . $e->getMessage());
        }

        return redirect()->route('accounting.expenses.index')
            ->with('success', "Expense {$expenseNo} recorded — ₹" . number_format($validated['amount'], 2));
    }

    /**
     * Expense report — summary by type for a period.
     */
    public function report(Request $request)
    {
        $fromDate = $request->from_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? Carbon::now()->format('Y-m-d');
        $branch = $this->isSuperAdmin() ? $request->branch : $this->currentOffice();

        $query = Expense::whereDate('expense_date', '>=', $fromDate)
            ->whereDate('expense_date', '<=', $toDate)
            ->where('status', '!=', 'rejected');

        if (!$this->isSuperAdmin()) $query->where('branch', $this->currentOffice());
        elseif ($branch) $query->where('branch', $branch);

        $byType = $query->selectRaw('expense_type, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('expense_type')
            ->orderByDesc('total')
            ->get();

        $grandTotal = $byType->sum('total');
        $branches = $this->getBranchOptions();

        return view('accounting.expenses.report', compact('byType', 'grandTotal', 'fromDate', 'toDate', 'branches', 'branch'));
    }

    private function generateExpenseNo(string $branch): string
    {
        $prefix = 'EXP';
        $branchCode = strtoupper(substr($branch, 0, 2));
        $last = Expense::where('expense_no', 'like', "{$prefix}-{$branchCode}-%")
            ->orderByRaw("CAST(SUBSTRING(expense_no, -4) AS UNSIGNED) DESC")
            ->first();
        $seq = $last ? ((int) substr($last->expense_no, -4)) + 1 : 1;
        return "{$prefix}-{$branchCode}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
