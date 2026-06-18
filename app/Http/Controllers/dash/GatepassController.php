<?php

namespace App\Http\Controllers\dash;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\gatepass;
use App\Models\Gr;
use App\Models\Vehicle;
use App\Models\truckdriver;
use App\Events\GRDispatched;
use App\Services\GrWorkflowService;
use App\Services\SerialNumberService;
use App\Traits\OfficeScopeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GatepassController extends Controller
{
    use OfficeScopeTrait;

    private GrWorkflowService $workflow;

    public function __construct(GrWorkflowService $workflow)
    {
        $this->middleware('auth');
        $this->workflow = $workflow;
    }

    /**
     * Display gatepass list with optional search and branch filter.
     * Per SXPRESS_LOGIC_SKILL section 17.
     */
    public function index(Request $request)
    {
        $query = gatepass::with(['grs', 'vehicle']);

        $this->officeScope($query);

        // Search
        if ($s = $request->search) {
            $query->where(function ($q) use ($s) {
                $q->where('gp_no', 'like', "%{$s}%")
                  ->orWhere('from_dest', 'like', "%{$s}%")
                  ->orWhere('to_dest', 'like', "%{$s}%");
            });
        }

        // Date range
        if ($from = $request->from_date) {
            $query->whereDate('gp_date', '>=', $from);
        }
        if ($to = $request->to_date) {
            $query->whereDate('gp_date', '<=', $to);
        }

        // SuperAdmin branch filter
        if ($this->isSuperAdmin() && $branch = $request->branch) {
            $query->where('office', $branch);
        }

        $items = $query->latest()->paginate(25)->withQueryString();
        $branches = $this->getBranchOptions();

        $gatepass_list_page = 'Gate Pass List';

        return view('admin.category.Gatepass.gate_pass_list', compact('items', 'gatepass_list_page', 'branches'));
    }

    /**
     * Show the form for creating a new gatepass.
     * Per SXPRESS_LOGIC_SKILL section 5.
     */
    public function create(Request $request)
    {
        $office = $this->currentOffice();
        $gpNo = $this->generateGatepassNo($office);
        $date = Carbon::now()->format('d-m-y');

        // Pre-select GR if passed (from GR list action)
        $preSelectedGr = null;
        if ($grNo = $request->get('gr_no')) {
            $preSelectedGr = Gr::where('gr_no', $grNo)
                ->where('to_dest', $office)
                ->where('status', 'in_transit')
                ->first();
        }

        // Get active vehicles and drivers for dropdowns
        $vehicles = Vehicle::where('status', 'active')->orderBy('vehicle_number')->get();
        $drivers = truckdriver::where('status', 1)->orderBy('driver_name')->get();

        return view('admin.category.Gatepass.gate_pass', compact('gpNo', 'date', 'office', 'preSelectedGr', 'vehicles', 'drivers'));
    }

    /**
     * Store a newly created gatepass.
     * Per SXPRESS_LOGIC_SKILL section 5 - Gatepass store() Logic.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'gp_date'    => 'required|date|before_or_equal:today',
            'from_dest'  => 'required|string|max:100',
            'to_dest'    => 'required|string|max:100',
            'gr_ids'     => 'required|array|min:1',
            'gr_ids.*'   => 'exists:grs,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id'  => 'required|exists:truckdrivers,id',
            'remarks'    => 'nullable|string|max:500',
        ], [
            'gr_ids.required' => 'At least one GR must be selected.',
            'gr_ids.*.exists' => 'One or more selected GRs are invalid.',
            'vehicle_id.required' => 'Please select a vehicle.',
            'driver_id.required' => 'Please select a driver.',
        ]);

        // Validate all selected GRs are in_transit and destined for current office
        $grs = Gr::whereIn('id', $validated['gr_ids'])
                  ->where('to_dest', $this->currentOffice())
                  ->where('status', 'in_transit')
                  ->get();

        if ($grs->count() !== count($validated['gr_ids'])) {
            return back()->withInput()->withErrors([
                'gr_ids' => 'One or more selected GRs are invalid, not destined for this office, or are not in transit.'
            ]);
        }

        // Create gatepass
        $firstGr = $grs->first();
        $gatepass = gatepass::create([
            'gp_no'          => $this->generateGatepassNoAtomic($this->currentOffice()),
            'gp_date'        => $validated['gp_date'],
            'from_dest'      => $validated['from_dest'],
            'to_dest'        => $validated['to_dest'],
            'vehicle_id'     => $validated['vehicle_id'],
            'driver_id'      => $validated['driver_id'],
            'note'           => $validated['remarks'] ?? '',
            'office'         => $this->currentOffice(),
            'created_by_id'  => auth()->id(),
            // Legacy columns (populated from linked GRs for backward compat)
            'gr_no'          => $grs->pluck('gr_no')->implode(','),
            'consignor'      => $firstGr->consignor ?? '',
            'weight'         => $grs->sum('weight'),
            'nugs'           => $grs->sum('nugs'),
            'pm'             => $firstGr->pm ?? '',
            'frieght_amount' => $grs->sum('frieght_amount'),
            'total_amount'   => $grs->sum('total_amount'),
        ]);

        // Link GRs via pivot table and mark each as 'delivered'
        foreach ($grs as $gr) {
            $gatepass->grs()->attach($gr->id, ['gr_no' => $gr->gr_no]);
        }

        foreach ($grs as $gr) {
            $gr->update([
                'status'            => 'delivered',
                'status_updated_at' => now(),
                'status_updated_by' => auth()->id(),
                'delivered_at'      => now(),
            ]);
        }

        return redirect()->route('gatepass.index')
            ->with('success', "Gatepass {$gatepass->gp_no} created for {$grs->count()} GR(s). GR(s) marked as Delivered.");
    }

    /**
     * Display a gatepass (view/print).
     */
    public function show($id)
    {
        $gp = gatepass::with(['vehicle', 'driver', 'grs'])->findOrFail($id);

        if (!$this->isSuperAdmin() && $gp->office !== $this->currentOffice()) {
            abort(403);
        }

        return view('admin.category.Gatepass.gate_pass_view', compact('gp', 'id'));
    }

    /**
     * Show the form for editing a gatepass.
     */
    public function edit($id)
    {
        $gp = gatepass::with(['vehicle', 'driver', 'grs'])->findOrFail($id);

        if (!$this->isSuperAdmin() && $gp->office !== $this->currentOffice()) {
            abort(403);
        }

        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) {
            abort(403, 'Only Manager or higher can edit a gatepass.');
        }

        $vehicles = Vehicle::where('status', 'active')->orderBy('vehicle_number')->get();
        $drivers = truckdriver::where('status', 1)->orderBy('driver_name')->get();

        return view('admin.category.Gatepass.gate_pass_edit', compact('gp', 'id', 'vehicles', 'drivers'));
    }

    /**
     * Update a gatepass.
     */
    public function update(Request $request, $id)
    {
        $gp = gatepass::findOrFail($id);

        if (!$this->isSuperAdmin() && $gp->office !== $this->currentOffice()) {
            abort(403);
        }

        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) {
            abort(403);
        }

        $validated = $request->validate([
            'gp_date'    => 'required|date',
            'from_dest'  => 'required|string|max:100',
            'to_dest'    => 'required|string|max:100',
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id'  => 'required|exists:truckdrivers,id',
            'remarks'    => 'nullable|string|max:500',
        ]);

        $gp->update([
            'gp_date'    => $validated['gp_date'],
            'from_dest'  => $validated['from_dest'],
            'to_dest'    => $validated['to_dest'],
            'vehicle_id' => $validated['vehicle_id'],
            'driver_id'  => $validated['driver_id'],
            'note'       => $validated['remarks'] ?? '',
        ]);

        return redirect()->route('gatepass.index')
            ->with('success', 'Gatepass updated successfully.');
    }

    /**
     * Delete a gatepass.
     * Per SXPRESS_LOGIC_SKILL section 5 - reverses GR status to 'created'.
     */
    public function destroy($id)
    {
        $gp = gatepass::findOrFail($id);

        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) {
            abort(403);
        }
        if (!$this->isSuperAdmin() && $gp->office !== $this->currentOffice()) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($gp) {
                // Reverse GR status back to 'in_transit' (gate pass delivered → revert to in_transit)
                foreach ($gp->grs as $gr) {
                    if ($gr->status === 'delivered') {
                        $gr->update([
                            'status' => 'in_transit',
                            'delivered_at' => null,
                        ]);
                    }
                }

                $gp->grs()->detach();
                $gp->delete();
            });
        } catch (\Exception $e) {
            return back()->withErrors([$e->getMessage()]);
        }

        return redirect()->route('gatepass.index')
            ->with('success', 'Gatepass deleted. GR(s) reverted to In Transit status.');
    }

    /**
     * Print gatepass.
     * GET /dash/gatepass/{id}/print
     */
    public function print($id)
    {
        $gp = gatepass::with(['vehicle', 'driver', 'grs'])->findOrFail($id);

        if (!$this->isSuperAdmin() && $gp->office !== $this->currentOffice()) {
            abort(403);
        }

        return view('admin.category.Gatepass.gate_pass_print', compact('gp'));
    }

    // ─────────────────────────────────────────────────────────────────
    // PRIVATE HELPER METHODS
    // ─────────────────────────────────────────────────────────────────

    /**
     * Generate gatepass number (preview, non-locking).
     */
    private function generateGatepassNo(string $office): string
    {
        $branch = Branch::where('branch_name', $office)->first();
        return SerialNumberService::previewNext($branch->id, 'gate_pass');
    }

    /**
     * Atomic gatepass number generation with row locking.
     */
    private function generateGatepassNoAtomic(string $office): string
    {
        $branch = Branch::where('branch_name', $office)->first();
        return SerialNumberService::generateNext($branch->id, 'gate_pass');
    }
}
