<?php

namespace App\Http\Controllers\dash;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Freight;
use App\Models\challan;
use App\Models\ChallanItem;
use App\Models\Gr;
use App\Services\SerialNumberService;
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
        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) {
            abort(403);
        }

        $office = $this->currentOffice();
        $fmNo = $this->generateFmNo($office);

        // Get challans ready for freight memo (status 'created' or 'loaded', not yet in_transit)
        $challansQuery = challan::where('office', $office)
            ->whereIn('status', ['created', 'loaded'])
            ->orderByDesc('id');
        $challans = $challansQuery->get();

        // Pre-select challan if passed
        $selectedChallan = null;
        if ($request->challan_id) {
            $selectedChallan = challan::with('items')->find($request->challan_id);
        }

        return view('admin.category.FrieghtMemo.Frieght_memo', compact('fmNo', 'office', 'challans', 'selectedChallan'));
    }

    /**
     * Store — Challan-linked Freight Memo (Indian lorry-hire settlement).
     *
     * Indian truck transport settlement:
     *   Total Lorry Hire (agreed freight for the trip)
     *   − Advance Paid (at loading point, for diesel/expenses)
     *   − Broker Commission / Brokerage
     *   − Hamali (loading + unloading labour)
     *   − Detention / Halting charges
     *   − TDS (1% if applicable)
     *   − Other deductions
     *   = Balance Payable to Owner (at delivery, after POD)
     */
    public function store(Request $request)
    {
        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) {
            abort(403);
        }

        $validated = $request->validate([
            'fm_date'        => 'required|date|before_or_equal:today',
            'challan_id'     => 'nullable|exists:challans,id',
            'from_dest'      => 'required|string|max:100',
            'to_dest'        => 'required|string|max:100',
            'truck_no'       => 'required|string|max:50',
            'owner_name'     => 'nullable|string|max:150',
            'truck_freight'  => 'required|numeric|min:0',
            'advance'        => 'nullable|numeric|min:0',
            'commission'     => 'nullable|numeric|min:0',
            'hamali'         => 'nullable|numeric|min:0',
            'detention'      => 'nullable|numeric|min:0',
            'tds'            => 'nullable|numeric|min:0',
            'other_charges'  => 'nullable|numeric|min:0',
            'note'           => 'nullable|string|max:500',
        ], [
            'truck_freight.required' => 'Total Lorry Hire is required.',
            'truck_no.required'      => 'Truck number is required (select a challan).',
        ]);

        $truckFreight = floatval($validated['truck_freight']);
        $advance   = floatval($validated['advance'] ?? 0);
        $commission= floatval($validated['commission'] ?? 0);
        $hamali    = floatval($validated['hamali'] ?? 0);
        $detention = floatval($validated['detention'] ?? 0);
        $tds       = floatval($validated['tds'] ?? 0);
        $other     = floatval($validated['other_charges'] ?? 0);

        // Balance payable to owner at delivery
        $totalDeductions = $advance + $commission + $hamali + $detention + $tds + $other;
        $balanceDue = round($truckFreight - $totalDeductions, 2);

        $fm = Freight::create([
            'fm_no'          => $this->generateFmNoAtomic($this->currentOffice()),
            'fm_date'        => $validated['fm_date'],
            'from_dest'      => $validated['from_dest'],
            'to_dest'        => $validated['to_dest'],
            'truck_no'       => $validated['truck_no'],
            'truck_freight'  => $truckFreight,
            'commission'     => $commission,
            // Map Indian transport deductions onto flexible entry columns
            'entry_1'        => 'Advance Paid',     'entry_1_amount' => $advance,
            'entry_2'        => 'Hamali (Load/Unload)', 'entry_2_amount' => $hamali,
            'entry_3'        => 'Detention',        'entry_3_amount' => $detention,
            'entry_4'        => 'TDS',              'entry_4_amount' => $tds,
            'total_amount'   => $totalDeductions,
            'other_charges'  => $other,
            'extra'          => 0,
            'balance_due'    => $balanceDue,
            'note'           => $validated['note'] ?? '',
            'office'         => $this->currentOffice(),
            'created_by_id'  => auth()->id(),
            // Compat columns
            'memo_no'        => '',
            'memo_date'      => $validated['fm_date'],
            'consignor'      => $validated['owner_name'] ?? '',
            'consignee'      => '',
        ]);

        // Update linked challan status to 'in_transit'
        if (!empty($validated['challan_id'])) {
            $linkedChallan = challan::find($validated['challan_id']);
            if ($linkedChallan && in_array($linkedChallan->status, ['created', 'loaded'])) {
                $linkedChallan->update(['status' => 'in_transit']);
            }
        }

        // Create accounting entries automatically (Phase 8 integration)
        try {
            \App\Listeners\CreateFreightMemoAccountingEntry::handle($fm);
        } catch (\Throwable $e) {
            \Log::warning('Accounting FM entry failed: ' . $e->getMessage());
        }

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
        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) abort(403);
        if (!$this->isSuperAdmin() && $freight->office !== $this->currentOffice()) abort(403);

        $destinations = Branch::active()->orderBy('branch_name')->pluck('branch_name')->all();
        return view('admin.category.FrieghtMemo.Frieght_memo_edit', compact('freight', 'id', 'destinations'));
    }

    public function update(Request $request, $id)
    {
        $freight = Freight::findOrFail($id);
        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) abort(403);
        if (!$this->isSuperAdmin() && $freight->office !== $this->currentOffice()) abort(403);

        $validated = $request->validate([
            'fm_date'        => 'required|date',
            'from_dest'      => 'required|string|max:100',
            'to_dest'        => 'required|string|max:100',
            'truck_freight'  => 'required|numeric|min:0',
            'advance'        => 'nullable|numeric|min:0',
            'commission'     => 'nullable|numeric|min:0',
            'hamali'         => 'nullable|numeric|min:0',
            'detention'      => 'nullable|numeric|min:0',
            'tds'            => 'nullable|numeric|min:0',
            'other_charges'  => 'nullable|numeric|min:0',
            'note'           => 'nullable|string|max:500',
        ]);

        $truckFreight = floatval($validated['truck_freight']);
        $advance   = floatval($validated['advance'] ?? 0);
        $commission= floatval($validated['commission'] ?? 0);
        $hamali    = floatval($validated['hamali'] ?? 0);
        $detention = floatval($validated['detention'] ?? 0);
        $tds       = floatval($validated['tds'] ?? 0);
        $other     = floatval($validated['other_charges'] ?? 0);

        $totalDeductions = $advance + $commission + $hamali + $detention + $tds + $other;
        $balanceDue = round($truckFreight - $totalDeductions, 2);

        $freight->update([
            'fm_date'        => $validated['fm_date'],
            'from_dest'      => $validated['from_dest'],
            'to_dest'        => $validated['to_dest'],
            'truck_freight'  => $truckFreight,
            'commission'     => $commission,
            'entry_1'        => 'Advance Paid',     'entry_1_amount' => $advance,
            'entry_2'        => 'Hamali (Load/Unload)', 'entry_2_amount' => $hamali,
            'entry_3'        => 'Detention',        'entry_3_amount' => $detention,
            'entry_4'        => 'TDS',              'entry_4_amount' => $tds,
            'total_amount'   => $totalDeductions,
            'other_charges'  => $other,
            'extra'          => 0,
            'balance_due'    => $balanceDue,
            'note'           => $validated['note'] ?? '',
        ]);

        return redirect()->route('frieghtmemo.index')->with('success', 'Freight Memo updated.');
    }

    public function destroy($id)
    {
        $freight = Freight::findOrFail($id);
        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) abort(403);
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
        $branch = Branch::where('branch_name', $office)->first();
        return SerialNumberService::previewNext($branch->id, 'freight_memo');
    }

    private function generateFmNoAtomic(string $office): string
    {
        $branch = Branch::where('branch_name', $office)->first();
        return SerialNumberService::generateNext($branch->id, 'freight_memo');
    }
}
