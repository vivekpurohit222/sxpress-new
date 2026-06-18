<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Outstanding;
use App\Models\Gr;
use App\Models\Freight;
use App\Traits\OfficeScopeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Outstanding Management Controller
 *
 * Tracks receivables (TO-PAY from consignees) and payables (truck owner balances).
 * Integrates with existing GR and Freight Memo data.
 * Generates: Outstanding Report, Ageing Report, Recovery Report.
 */
class OutstandingController extends Controller
{
    use OfficeScopeTrait;

    public function __construct()
    {
        $this->middleware(['auth', 'role:SuperAdmin']);
    }

    /**
     * Outstanding Dashboard — summary of all receivables and payables.
     */
    public function index(Request $request)
    {
        $branch = $this->isSuperAdmin() ? $request->branch : $this->currentOffice();
        $type = $request->type; // receivable or payable
        $partyType = $request->party_type;
        $status = $request->status;

        $query = Outstanding::query()->orderByDesc('invoice_date');

        if (!$this->isSuperAdmin()) {
            $query->where('branch', $branch);
        } elseif ($branch) {
            $query->where('branch', $branch);
        }

        if ($type) $query->where('type', $type);
        if ($partyType) $query->where('party_type', $partyType);
        if ($status) $query->where('status', $status);

        $items = $query->paginate(30)->withQueryString();

        // Summary totals
        $summaryQuery = Outstanding::pending();
        if (!$this->isSuperAdmin()) $summaryQuery->where('branch', $this->currentOffice());
        elseif ($branch) $summaryQuery->where('branch', $branch);

        $summary = [
            'total_receivable' => (clone $summaryQuery)->receivable()->sum('pending_amount'),
            'total_payable'    => (clone $summaryQuery)->payable()->sum('pending_amount'),
            'overdue_count'    => (clone $summaryQuery)->where('status', 'overdue')->count(),
            'total_overdue'    => (clone $summaryQuery)->where('status', 'overdue')->sum('pending_amount'),
        ];

        $branches = $this->getBranchOptions();

        return view('accounting.outstanding.index', compact('items', 'summary', 'branches', 'branch', 'type', 'partyType', 'status'));
    }

    /**
     * Ageing Report — group outstanding by age buckets.
     */
    public function ageing(Request $request)
    {
        $branch = $this->isSuperAdmin() ? $request->branch : $this->currentOffice();
        $type = $request->type ?? 'receivable';

        $query = Outstanding::pending()->where('type', $type);
        if (!$this->isSuperAdmin()) $query->where('branch', $this->currentOffice());
        elseif ($branch) $query->where('branch', $branch);

        $items = $query->orderBy('invoice_date')->get();

        // Group into aging buckets
        $buckets = [
            '0-7 days' => $items->filter(fn($i) => $i->age_days <= 7),
            '8-15 days' => $items->filter(fn($i) => $i->age_days > 7 && $i->age_days <= 15),
            '16-30 days' => $items->filter(fn($i) => $i->age_days > 15 && $i->age_days <= 30),
            '31-60 days' => $items->filter(fn($i) => $i->age_days > 30 && $i->age_days <= 60),
            '61-90 days' => $items->filter(fn($i) => $i->age_days > 60 && $i->age_days <= 90),
            '90+ days' => $items->filter(fn($i) => $i->age_days > 90),
        ];

        $branches = $this->getBranchOptions();

        return view('accounting.outstanding.ageing', compact('buckets', 'type', 'branches', 'branch'));
    }

    /**
     * Recovery Report — payments received against outstanding.
     */
    public function recovery(Request $request)
    {
        $fromDate = $request->from_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? Carbon::now()->format('Y-m-d');
        $branch = $this->isSuperAdmin() ? $request->branch : $this->currentOffice();

        $query = Outstanding::where('paid_amount', '>', 0)
            ->whereDate('updated_at', '>=', $fromDate)
            ->whereDate('updated_at', '<=', $toDate);

        if (!$this->isSuperAdmin()) $query->where('branch', $this->currentOffice());
        elseif ($branch) $query->where('branch', $branch);

        $recoveries = $query->orderByDesc('updated_at')->get();

        $totalRecovered = $recoveries->sum('paid_amount');
        $branches = $this->getBranchOptions();

        return view('accounting.outstanding.recovery', compact('recoveries', 'totalRecovered', 'fromDate', 'toDate', 'branches', 'branch'));
    }

    /**
     * Record a payment against an outstanding entry.
     */
    public function recordPayment(Request $request, $id)
    {
        $outstanding = Outstanding::findOrFail($id);

        if (!$this->isSuperAdmin() && $outstanding->branch !== $this->currentOffice()) {
            abort(403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $outstanding->pending_amount,
        ]);

        $outstanding->recordPayment((float) $request->amount);

        return redirect()->back()->with('success', "₹{$request->amount} recorded against {$outstanding->invoice_ref}.");
    }

    /**
     * Sync outstanding from existing GR TO-PAY data.
     * This pulls from the operational system into the accounting outstanding table.
     */
    public function syncFromGr()
    {
        if (!$this->isSuperAdmin()) abort(403);

        $synced = 0;

        // Get TO-PAY GRs that don't have an outstanding entry yet
        $grs = Gr::where('to_pay', 1)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('outstanding')
                  ->whereColumn('outstanding.reference_id', 'grs.id')
                  ->where('outstanding.reference_type', 'gr');
            })
            ->get();

        foreach ($grs as $gr) {
            $status = $gr->topay_collected ? 'paid' : 'pending';
            $paid = $gr->topay_collected ? $gr->total_amount : 0;

            Outstanding::create([
                'party_type'     => 'consignee',
                'party_name'     => $gr->consignee ?: 'Unknown',
                'type'           => 'receivable',
                'invoice_ref'    => $gr->gr_no,
                'invoice_date'   => $gr->copy_date,
                'total_amount'   => $gr->total_amount,
                'paid_amount'    => $paid,
                'pending_amount' => $gr->total_amount - $paid,
                'due_date'       => Carbon::parse($gr->copy_date)->addDays(30),
                'status'         => $status,
                'branch'         => $gr->office,
                'reference_type' => 'gr',
                'reference_id'   => $gr->id,
            ]);
            $synced++;
        }

        // Sync Freight Memo payables (truck owner balances)
        $fms = Freight::where('balance_due', '>', 0)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('outstanding')
                  ->whereColumn('outstanding.reference_id', 'frieghts.id')
                  ->where('outstanding.reference_type', 'freight_memo');
            })
            ->get();

        foreach ($fms as $fm) {
            Outstanding::create([
                'party_type'     => 'truck_owner',
                'party_name'     => $fm->consignor ?: ($fm->truck_no ?: 'Truck Owner'),
                'type'           => 'payable',
                'invoice_ref'    => $fm->fm_no ?: $fm->memo_no,
                'invoice_date'   => $fm->fm_date ?? $fm->memo_date ?? now(),
                'total_amount'   => $fm->balance_due,
                'paid_amount'    => 0,
                'pending_amount' => $fm->balance_due,
                'due_date'       => Carbon::parse($fm->fm_date ?? $fm->memo_date ?? now())->addDays(7),
                'status'         => 'pending',
                'branch'         => $fm->office,
                'reference_type' => 'freight_memo',
                'reference_id'   => $fm->id,
            ]);
            $synced++;
        }

        return redirect()->route('accounting.outstanding.index')
            ->with('success', "Synced {$synced} outstanding entries from operational data.");
    }
}
