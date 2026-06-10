<?php

namespace App\Http\Controllers\dash;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Freight;
use App\Models\challan;
use App\Models\ChallanItem;
use App\Models\Gr;
use App\Traits\OfficeScopeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Freight Memo — Truck Owner Settlement linked to a Challan.
 *
 * Indian transport flow:
 * 1. Challan created (truck + driver carries GRs from A → B)
 * 2. Trip completes
 * 3. Freight Memo settles the truck owner:
 *    Total freight from GRs → minus company commission → minus charges → balance to owner
 */
class FreightController extends Controller
{
    use OfficeScopeTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Freight::query();
        $this->officeScope($query);

        if ($s = $request->search) {
            $query->where(function ($q) use ($s) {
                $q->where('fm_no', 'like', "%{$s}%")
                  ->orWhere('truck_no', 'like', "%{$s}%");
            });
        }

        if ($from = $request->from_date) $query->whereDate('fm_date', '>=', $from);
        if ($to = $request->to_date) $query->whereDate('fm_date', '<=', $to);

        if ($this->isSuperAdmin() && $branch = $request->branch) {
            $query->where('office', $branch);
        }

        $items = $query->latest()->paginate(25)->withQueryString();
        $branches = $this->getBranchOptions();

        return view('admin.category.FrieghtMemo.Frieght_memo_list', compact('items', 'branches'));
    }

    public function create(Request $request)
    {
        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'Admin', 'Manager'])) {
            abort(403);
        }

        $office = $this->currentOffice();
        $fmNo = $this->generateFmNo($office);

        // Get challans that don't have a freight memo yet (for this office)
        $challansQuery = challan::where('office', $office)->orderByDesc('id');
        if (!$this->isSuperAdmin()) {
            $challansQuery->where('office', $office);
        }
        $challans = $challansQuery->get();

        // Pre-select challan if passed
        $selectedChallan = null;
        if ($request->challan_id) {
            $selectedChallan = challan::with('items')->find($request->challan_id);
        }

        return view('admin.category.FrieghtMemo.Frieght_memo', compact('fmNo', 'office', 'challans', 'selectedChallan'));
    }

    /**
     * Store — Challan-linked Freight Memo.
     *
     * Calculation:
     * - Total GR Freight = sum of all freight from GRs on the challan
     * - Truck Hire = agreed rate for the trip
     * - Deductions = Commission + Loading + Unloading + Advance + Other
     * - Balance Due = Truck Hire − All Deductions
     */
    public function store(Request $request)
    {
        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'Admin', 'Manager'])) {
            abort(403);
        }

        $validated = $request->validate([
            'fm_date'        => 'required|date|before_or_equal:today',
            'challan_id'     => 'nullable|exists:challans,id',
            'from_dest'      => 'required|string|max:100',
            'to_dest'        => 'required|string|max:100',
            'truck_no'       => 'required|string|max:50',
            'truck_freight'  => 'required|numeric|min:0',
            'commission'     => 'nullable|numeric|min:0',
            'entry_1'        => 'nullable|string|max:100',
            'entry_1_amount' => 'nullable|numeric|min:0',
            'entry_2'        => 'nullable|string|max:100',
            'entry_2_amount' => 'nullable|numeric|min:0',
            'entry_3'        => 'nullable|string|max:100',
            'entry_3_amount' => 'nullable|numeric|min:0',
            'entry_4'        => 'nullable|string|max:100',
            'entry_4_amount' => 'nullable|numeric|min:0',
            'other_charges'  => 'nullable|numeric|min:0',
            'extra'          => 'nullable|numeric|min:0',
            'note'           => 'nullable|string|max:500',
        ]);

        // Calculate
        $truckFreight = floatval($validated['truck_freight']);
        $commission = floatval($validated['commission'] ?? 0);
        $e1 = floatval($validated['entry_1_amount'] ?? 0);
        $e2 = floatval($validated['entry_2_amount'] ?? 0);
        $e3 = floatval($validated['entry_3_amount'] ?? 0);
        $e4 = floatval($validated['entry_4_amount'] ?? 0);
        $other = floatval($validated['other_charges'] ?? 0);
        $extra = floatval($validated['extra'] ?? 0);

        $totalEntries = $e1 + $e2 + $e3 + $e4;
        $totalDeductions = $commission + $e1 + $e2 + $e3 + $e4 + $other + $extra;
        $balanceDue = round($truckFreight - $totalDeductions, 2);

        Freight::create([
            'fm_no'          => $this->generateFmNoAtomic($this->currentOffice()),
            'fm_date'        => $validated['fm_date'],
            'from_dest'      => $validated['from_dest'],
            'to_dest'        => $validated['to_dest'],
            'truck_no'       => $validated['truck_no'],
            'truck_freight'  => $truckFreight,
            'commission'     => $commission,
            'entry_1'        => $validated['entry_1'] ?? '',
            'entry_1_amount' => $e1,
            'entry_2'        => $validated['entry_2'] ?? '',
            'entry_2_amount' => $e2,
            'entry_3'        => $validated['entry_3'] ?? '',
            'entry_3_amount' => $e3,
            'entry_4'        => $validated['entry_4'] ?? '',
            'entry_4_amount' => $e4,
            'total_amount'   => $totalEntries,
            'other_charges'  => $other,
            'extra'          => $extra,
            'balance_due'    => $balanceDue,
            'note'           => $validated['note'] ?? '',
            'office'         => $this->currentOffice(),
            'created_by_id'  => auth()->id(),
            // Compat columns
            'memo_no'        => '',
            'memo_date'      => $validated['fm_date'],
            'consignor'      => '',
            'consignee'      => '',
        ]);

        return redirect()->route('frieghtmemo.index')
            ->with('success', 'Freight Memo created — Balance ₹' . number_format($balanceDue, 2) . ' payable to truck owner.');
    }

    /**
     * AJAX: Get challan details for auto-fill on create form.
     */
    public function getChallanData($id)
    {
        $challan = challan::with('items')->findOrFail($id);

        if (!$this->isSuperAdmin() && $challan->office !== $this->currentOffice()) {
            abort(403);
        }

        // Sum freight from all GRs on this challan
        $grNos = $challan->items->pluck('gr_no')->filter();
        $totalGrFreight = Gr::whereIn('gr_no', $grNos)->sum('frieght_amount');

        return response()->json([
            'challan_no'  => $challan->challan_no,
            'challan_date' => $challan->challan_date,
            'from_dest'   => $challan->from_dest,
            'to_dest'     => $challan->to_dest,
            'truck_no'    => $challan->truck_no,
            'driver_name' => $challan->driver_name,
            'owner_name'  => $challan->owner_name,
            'total_weight' => $challan->total_weight,
            'items_count' => $challan->items->count(),
            'total_gr_freight' => (float) $totalGrFreight,
            'gr_numbers'  => $grNos->implode(', '),
        ]);
    }

    public function show($id)
    {
        $freight = Freight::findOrFail($id);
        if (!$this->isSuperAdmin() && $freight->office !== $this->currentOffice()) abort(403);
        return view('admin.category.FrieghtMemo.Frieght_memo_view', compact('freight', 'id'));
    }

    public function edit($id)
    {
        $freight = Freight::findOrFail($id);
        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'Admin'])) abort(403);
        if (!$this->isSuperAdmin() && $freight->office !== $this->currentOffice()) abort(403);

        $destinations = Branch::active()->orderBy('branch_name')->pluck('branch_name')->all();
        return view('admin.category.FrieghtMemo.Frieght_memo_edit', compact('freight', 'id', 'destinations'));
    }

    public function update(Request $request, $id)
    {
        $freight = Freight::findOrFail($id);
        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'Admin'])) abort(403);
        if (!$this->isSuperAdmin() && $freight->office !== $this->currentOffice()) abort(403);

        $validated = $request->validate([
            'fm_date'        => 'required|date',
            'from_dest'      => 'required|string|max:100',
            'to_dest'        => 'required|string|max:100',
            'truck_freight'  => 'required|numeric|min:0',
            'commission'     => 'nullable|numeric|min:0',
            'entry_1'        => 'nullable|string|max:100',
            'entry_1_amount' => 'nullable|numeric|min:0',
            'entry_2'        => 'nullable|string|max:100',
            'entry_2_amount' => 'nullable|numeric|min:0',
            'entry_3'        => 'nullable|string|max:100',
            'entry_3_amount' => 'nullable|numeric|min:0',
            'entry_4'        => 'nullable|string|max:100',
            'entry_4_amount' => 'nullable|numeric|min:0',
            'other_charges'  => 'nullable|numeric|min:0',
            'extra'          => 'nullable|numeric|min:0',
            'note'           => 'nullable|string|max:500',
        ]);

        $truckFreight = floatval($validated['truck_freight']);
        $commission = floatval($validated['commission'] ?? 0);
        $e1 = floatval($validated['entry_1_amount'] ?? 0);
        $e2 = floatval($validated['entry_2_amount'] ?? 0);
        $e3 = floatval($validated['entry_3_amount'] ?? 0);
        $e4 = floatval($validated['entry_4_amount'] ?? 0);
        $other = floatval($validated['other_charges'] ?? 0);
        $extra = floatval($validated['extra'] ?? 0);
        $balanceDue = round($truckFreight - $commission - $e1 - $e2 - $e3 - $e4 - $other - $extra, 2);

        $freight->update([
            'fm_date'        => $validated['fm_date'],
            'from_dest'      => $validated['from_dest'],
            'to_dest'        => $validated['to_dest'],
            'truck_freight'  => $truckFreight,
            'commission'     => $commission,
            'entry_1'        => $validated['entry_1'] ?? '',
            'entry_1_amount' => $e1,
            'entry_2'        => $validated['entry_2'] ?? '',
            'entry_2_amount' => $e2,
            'entry_3'        => $validated['entry_3'] ?? '',
            'entry_3_amount' => $e3,
            'entry_4'        => $validated['entry_4'] ?? '',
            'entry_4_amount' => $e4,
            'total_amount'   => $e1 + $e2 + $e3 + $e4,
            'other_charges'  => $other,
            'extra'          => $extra,
            'balance_due'    => $balanceDue,
            'note'           => $validated['note'] ?? '',
        ]);

        return redirect()->route('frieghtmemo.index')->with('success', 'Freight Memo updated.');
    }

    public function destroy($id)
    {
        $freight = Freight::findOrFail($id);
        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'Admin'])) abort(403);
        if (!$this->isSuperAdmin() && $freight->office !== $this->currentOffice()) abort(403);
        $freight->delete();
        return redirect()->route('frieghtmemo.index')->with('success', 'Freight Memo deleted.');
    }

    public function print($id)
    {
        $freight = Freight::findOrFail($id);
        if (!$this->isSuperAdmin() && $freight->office !== $this->currentOffice()) abort(403);
        return view('admin.category.FrieghtMemo.Frieght_memo_print', compact('freight'));
    }

    // ─────────────────────────────────────────────────────────────────

    private function generateFmNo(string $office): string
    {
        $last = Freight::withTrashed()->where('office', $office)->whereNotNull('fm_no')->where('fm_no', '!=', '')->orderByDesc('id')->first();
        if ($last && $last->fm_no) {
            $num = (int) preg_replace('/[^0-9]/', '', $last->fm_no);
            return str_pad($num + 1, 5, '0', STR_PAD_LEFT);
        }
        return '00001';
    }

    private function generateFmNoAtomic(string $office): string
    {
        return DB::transaction(function () use ($office) {
            $last = Freight::withTrashed()->where('office', $office)
                ->whereNotNull('fm_no')->where('fm_no', '!=', '')
                ->lockForUpdate()->orderByDesc('id')->first();
            if ($last && $last->fm_no) {
                $num = (int) preg_replace('/[^0-9]/', '', $last->fm_no);
                return str_pad($num + 1, 5, '0', STR_PAD_LEFT);
            }
            return '00001';
        });
    }
}
