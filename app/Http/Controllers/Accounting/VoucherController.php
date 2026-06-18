<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Models\Accounting\LedgerEntry;
use App\Models\Accounting\Voucher;
use App\Services\AccountingService;
use App\Traits\OfficeScopeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    use OfficeScopeTrait;

    private AccountingService $accounting;

    public function __construct(AccountingService $accounting)
    {
        $this->middleware(['auth', 'role:SuperAdmin']);
        $this->accounting = $accounting;
    }

    /**
     * List vouchers with filters.
     */
    public function index(Request $request)
    {
        $query = Voucher::with('creator')->orderByDesc('voucher_date')->orderByDesc('id');

        // Office scope
        if (!$this->isSuperAdmin()) {
            $query->where('branch', $this->currentOffice());
        } elseif ($request->branch) {
            $query->where('branch', $request->branch);
        }

        if ($type = $request->type) {
            $query->where('voucher_type', $type);
        }
        if ($from = $request->from_date) {
            $query->whereDate('voucher_date', '>=', $from);
        }
        if ($to = $request->to_date) {
            $query->whereDate('voucher_date', '<=', $to);
        }
        if ($status = $request->status) {
            $query->where('status', $status);
        }

        $vouchers = $query->paginate(25)->withQueryString();
        $branches = $this->getBranchOptions();
        $types = ['receipt', 'payment', 'contra', 'journal'];

        return view('accounting.vouchers.index', compact('vouchers', 'branches', 'types'));
    }

    /**
     * Show create form for a specific voucher type.
     */
    public function create(Request $request)
    {
        $type = $request->type ?? 'receipt';
        $accounts = Account::active()->transactional()->orderBy('code')->get();
        $office = $this->currentOffice();

        return view('accounting.vouchers.create', compact('type', 'accounts', 'office'));
    }

    /**
     * Store a new voucher with entries.
     */
    public function store(Request $request)
    {
        $request->validate([
            'voucher_type'      => 'required|in:receipt,payment,contra,journal',
            'voucher_date'      => 'required|date|before_or_equal:today',
            'narration'         => 'required|string|max:500',
            'entries'           => 'required|array|min:2',
            'entries.*.account_id' => 'required|exists:accounts,id',
            'entries.*.debit'   => 'nullable|numeric|min:0',
            'entries.*.credit'  => 'nullable|numeric|min:0',
        ], [
            'entries.required' => 'At least two entries are required (debit and credit).',
            'narration.required' => 'Please provide a description for this voucher.',
        ]);

        $entries = collect($request->entries)->map(function ($e) {
            return [
                'account_id' => $e['account_id'],
                'debit'      => floatval($e['debit'] ?? 0),
                'credit'     => floatval($e['credit'] ?? 0),
            ];
        })->filter(function ($e) {
            return $e['debit'] > 0 || $e['credit'] > 0;
        })->values()->toArray();

        if (count($entries) < 2) {
            return back()->withInput()->withErrors(['entries' => 'At least two non-zero entries required.']);
        }

        try {
            $voucher = $this->accounting->createVoucher(
                type: $request->voucher_type,
                date: $request->voucher_date,
                narration: $request->narration,
                entries: $entries,
                branch: $this->currentOffice(),
                meta: ['created_by_id' => auth()->id()]
            );

            return redirect()->route('accounting.vouchers.index')
                ->with('success', Voucher::getTypeLabel($request->voucher_type) . " {$voucher->voucher_no} created.");

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['entries' => $e->getMessage()]);
        }
    }

    /**
     * Show voucher detail.
     */
    public function show($id)
    {
        $voucher = Voucher::with(['entries.account', 'creator', 'approver'])->findOrFail($id);

        if (!$this->isSuperAdmin() && $voucher->branch !== $this->currentOffice()) {
            abort(403);
        }

        return view('accounting.vouchers.show', compact('voucher'));
    }

    /**
     * Print voucher.
     */
    public function print($id)
    {
        $voucher = Voucher::with(['entries.account', 'creator', 'approver'])->findOrFail($id);

        if (!$this->isSuperAdmin() && $voucher->branch !== $this->currentOffice()) {
            abort(403);
        }

        return view('accounting.vouchers.print', compact('voucher'));
    }

    /**
     * Cancel a voucher (soft reversal — marks as cancelled, keeps audit trail).
     */
    public function cancel($id)
    {
        $voucher = Voucher::findOrFail($id);

        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) {
            abort(403, 'Only SuperAdmin or BranchManager can cancel vouchers.');
        }

        if (!$this->isSuperAdmin() && $voucher->branch !== $this->currentOffice()) {
            abort(403);
        }

        if ($voucher->status === 'cancelled') {
            return back()->withErrors(['Voucher is already cancelled.']);
        }

        $voucher->update(['status' => 'cancelled']);

        // Remove ledger entries (soft — keep voucher for audit trail)
        LedgerEntry::where('voucher_id', $voucher->id)->delete();

        return redirect()->route('accounting.vouchers.index')
            ->with('success', "Voucher {$voucher->voucher_no} cancelled.");
    }
}
