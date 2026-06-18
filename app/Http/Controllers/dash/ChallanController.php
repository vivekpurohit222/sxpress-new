<?php

namespace App\Http\Controllers\dash;

use App\Http\Controllers\Controller;
use App\Models\challan;
use App\Models\ChallanItem;
use App\Models\Gr;
use App\Models\Vehicle;
use App\Models\truckdriver;
use App\Traits\OfficeScopeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChallanController extends Controller
{
    use OfficeScopeTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display challan list with optional filters.
     * Per SXPRESS_LOGIC_SKILL section 17.
     */
    public function index(Request $request)
    {
        $query = challan::with(['vehicle', 'driver'])->withCount('items');

        $this->officeScope($query);
        $query->whereNotNull('office');

        // Search
        if ($s = $request->search) {
            $query->where(function ($q) use ($s) {
                $q->where('challan_no', 'like', "%{$s}%")
                  ->orWhere('from_dest', 'like', "%{$s}%")
                  ->orWhere('to_dest', 'like', "%{$s}%");
            });
        }

        // Date range
        if ($from = $request->from_date) {
            $query->whereDate('challan_date', '>=', $from);
        }
        if ($to = $request->to_date) {
            $query->whereDate('challan_date', '<=', $to);
        }

        // SuperAdmin branch filter
        if ($this->isSuperAdmin() && $branch = $request->branch) {
            $query->where('office', $branch);
        }

        $items = $query->latest()->paginate(25)->withQueryString();
        $branches = $this->getBranchOptions();

        $challan_list_page = 'Challan List';

        return view('admin.category.challan.challan_list', compact('items', 'challan_list_page', 'branches'));
    }

    /**
     * Show the form for creating a new challan.
     * Per SXPRESS_LOGIC_SKILL section 6.
     */
    public function create(Request $request)
    {
        $date = Carbon::now()->format('Y-m-d');
        $office = $this->currentOffice();

        // Generate challan number
        $challanNo = $this->generateChallanNo($office);

        // Get active vehicles and drivers (correct column names)
        $vehicles = Vehicle::where('status', 'active')->orderBy('vehicle_number')->get();
        $drivers = truckdriver::where('status', 1)->orderBy('driver_name')->get();

        // Dynamic destinations from branches
        $destinations = \App\Models\Branch::active()->orderBy('branch_name')->pluck('branch_name')->all();

        return view('admin.category.challan.challan', compact('challanNo', 'date', 'office', 'vehicles', 'drivers', 'destinations'));
    }

    /**
     * Store a newly created challan with items.
     * Per SXPRESS_LOGIC_SKILL section 6 - Challan store() with header + items.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'challan_date'  => 'required|date|before_or_equal:today',
            'from_dest'     => 'required|string|max:100',
            'to_dest'       => 'required|string|max:100',
            'vehicle_id'    => 'required|exists:vehicles,id',
            'driver_id'     => 'required|exists:truckdrivers,id',
            'items'         => 'required|array|min:1',
            'items.*.gr_no' => 'required|string|exists:grs,gr_no',
            'items.*.description' => 'required|string|max:300',
            'items.*.nugs'        => 'required|integer|min:1',
            'items.*.weight'      => 'required|numeric|min:0.01',
            'items.*.remarks'     => 'nullable|string|max:200',
        ], [
            'items.required' => 'At least one item is required.',
            'items.*.gr_no.exists' => 'One or more GR numbers are invalid.',
            'vehicle_id.required' => 'Please select a vehicle.',
            'driver_id.required' => 'Please select a driver.',
        ]);

        // Validate all GRs belong to current office AND are in 'created' status (not already loaded)
        $grNos = collect($validated['items'])->pluck('gr_no');
        $grs = Gr::whereIn('gr_no', $grNos)
                 ->where('office', $this->currentOffice())
                 ->where('status', 'created')
                 ->get();

        if ($grs->count() !== $grNos->unique()->count()) {
            return back()->withInput()->withErrors([
                'items' => 'One or more GR numbers are invalid, don\'t belong to your office, or have already been loaded onto another challan.'
            ]);
        }

        DB::transaction(function () use ($validated, $grNos) {
            $totalWeight = collect($validated['items'])->sum('weight');

            // Get vehicle/driver info for legacy columns
            $vehicle = Vehicle::find($validated['vehicle_id']);
            $driver = truckdriver::find($validated['driver_id']);

            $challan = challan::create([
                'challan_no'   => $this->generateChallanNoAtomic($this->currentOffice()),
                'challan_date' => $validated['challan_date'],
                'from_dest'    => $validated['from_dest'],
                'to_dest'      => $validated['to_dest'],
                'vehicle_id'   => $validated['vehicle_id'],
                'driver_id'    => $validated['driver_id'],
                'total_weight' => $totalWeight,
                'status'       => 'created',
                'office'       => $this->currentOffice(),
                // Legacy columns
                'truck_no'     => $vehicle->vehicle_number ?? '',
                'driver_name'  => $driver->driver_name ?? '',
                'license'      => $driver->license ?? '',
                'owner_name'   => $vehicle->owner_name ?? '',
            ]);

            foreach ($validated['items'] as $item) {
                // Fetch full GR data to populate challan item correctly
                $gr = Gr::where('gr_no', $item['gr_no'])->first();

                ChallanItem::create([
                    'challan_id'  => $challan->id,
                    'challan_no'  => $challan->challan_no,
                    'gr_no'       => $item['gr_no'],
                    'description' => $item['description'],
                    'nugs'        => $item['nugs'],
                    'weight'      => $item['weight'],
                    'meth'        => $item['meth'] ?? ($gr->meth ?? ''),
                    'paid'        => $gr->paid ? $gr->total_amount : 0,
                    'to_pay'      => $gr->to_pay ? $gr->total_amount : 0,
                    'sur_ch'      => $gr->sur_ch ?? 0,
                    'c_r'         => $gr->c_r ?? 0,
                    'other'       => $gr->other ?? 0,
                ]);
            }

            // Update GR statuses to 'loaded'
            Gr::whereIn('gr_no', $grNos)->update(['status' => 'loaded']);
        });

        return redirect()->route('challan.index')
            ->with('success', 'Challan created successfully.');
    }

    /**
     * Fetch challan by ID for AJAX.
     * GET /dash/challan/{id}/getData
     */
    public function getData($id)
    {
        $challan = challan::with(['vehicle', 'driver', 'items'])->findOrFail($id);

        if (!$this->isSuperAdmin() && $challan->office !== $this->currentOffice()) {
            abort(403);
        }

        return response()->json(['data' => $challan]);
    }

    /**
     * Fetch challan items by challan number (for AJAX).
     * GET /dash/challan/{challan_no}/challanfetchdata
     */
    public function challanfetchdata($challan_no)
    {
        $challan = challan::where('challan_no', $challan_no)->firstOrFail();

        if (!$this->isSuperAdmin() && $challan->office !== $this->currentOffice()) {
            abort(403);
        }

        $items = ChallanItem::where('challan_id', $challan->id)->get();

        return response()->json(['data' => $items]);
    }

    /**
     * Store challan items via AJAX (separate endpoint).
     * POST /dash/challan/challanIteams
     */
    public function challanIteamStore(Request $request)
    {
        $request->validate([
            'challan_id'    => 'required|exists:challans,id',
            'gr_no'         => 'required|string|exists:grs,gr_no',
            'description'   => 'required|string|max:300',
            'nugs'          => 'required|integer|min:1',
            'weight'        => 'required|numeric|min:0.01',
            'remarks'       => 'nullable|string|max:200',
        ]);

        $challan = challan::findOrFail($request->challan_id);

        if (!$this->isSuperAdmin() && $challan->office !== $this->currentOffice()) {
            abort(403);
        }

        $item = ChallanItem::create([
            'challan_id'  => $challan->id,
            'challan_no'  => $challan->challan_no,
            'gr_no'       => $request->gr_no,
            'description' => $request->description,
            'nugs'        => $request->nugs,
            'weight'      => $request->weight,
        ]);

        // Recalculate totals
        $challan->update([
            'total_weight' => $challan->items()->sum('weight'),
        ]);

        return response()->json(['success' => 'Challan item added.', 'status' => true, 'item' => $item]);
    }

    /**
     * Display a challan (view).
     */
    public function show($id)
    {
        $challan = challan::with(['vehicle', 'driver', 'items'])->findOrFail($id);

        if (!$this->isSuperAdmin() && $challan->office !== $this->currentOffice()) {
            abort(403);
        }

        return view('admin.category.challan.challan_view', compact('challan'));
    }

    /**
     * Show the form for editing a challan.
     * Per SXPRESS_LOGIC_SKILL section 6 - Challan Edit.
     */
    public function edit($id)
    {
        $challan = challan::with(['vehicle', 'driver', 'items'])->findOrFail($id);

        if (!$this->isSuperAdmin() && $challan->office !== $this->currentOffice()) {
            abort(403);
        }

        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) {
            abort(403, 'Only Manager or higher can edit a challan.');
        }

        $vehicles = Vehicle::where('status', 'active')->orderBy('vehicle_number')->get();
        $drivers = truckdriver::where('status', 1)->orderBy('driver_name')->get();

        return view('admin.category.challan.challan_edit', compact('challan', 'vehicles', 'drivers'));
    }

    /**
     * Delete a single challan item (AJAX).
     * DELETE /dash/challan-item/{id}
     * Per SXPRESS_LOGIC_SKILL section 6 - Delete Single Challan Item.
     */
    public function challandelete($id)
    {
        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) {
            abort(403);
        }

        $item = ChallanItem::findOrFail($id);
        $challan = $item->challan;

        if (!$this->isSuperAdmin() && $challan->office !== $this->currentOffice()) {
            abort(403);
        }

        // Cannot remove last item
        if ($challan->items()->count() <= 1) {
            return response()->json(['error' => 'Challan must have at least one item.'], 422);
        }

        $item->delete();

        // Recalculate totals
        $challan->update([
            'total_weight' => $challan->items()->sum('weight'),
        ]);

        return response()->json(['success' => 'Challan item removed.']);
    }

    /**
     * Update a challan with items.
     * Per SXPRESS_LOGIC_SKILL section 6 - Challan Update with add/remove items.
     */
    public function update(Request $request, $id)
    {
        $challan = challan::with('items')->findOrFail($id);

        if (!$this->isSuperAdmin() && $challan->office !== $this->currentOffice()) {
            abort(403);
        }

        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) {
            abort(403);
        }

        $validated = $request->validate([
            'challan_date'  => 'required|date',
            'from_dest'     => 'required|string|max:100',
            'to_dest'       => 'required|string|max:100',
            'vehicle_id'    => 'required|exists:vehicles,id',
            'driver_id'     => 'required|exists:truckdrivers,id',
            'items'         => 'required|array|min:1',
            'items.*.id'           => 'nullable|exists:challan_items,id',
            'items.*.gr_no'        => 'required|string',
            'items.*.description'  => 'required|string',
            'items.*.nugs'         => 'required|integer|min:1',
            'items.*.weight'       => 'required|numeric|min:0.01',
            'items.*.remarks'      => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $challan) {
            // Update header
            $vehicle = Vehicle::find($validated['vehicle_id']);
            $driver = truckdriver::find($validated['driver_id']);

            $challan->update([
                'challan_date' => $validated['challan_date'],
                'from_dest'    => $validated['from_dest'],
                'to_dest'      => $validated['to_dest'],
                'vehicle_id'   => $validated['vehicle_id'],
                'driver_id'    => $validated['driver_id'],
                'truck_no'     => $vehicle->vehicle_number ?? $challan->truck_no,
                'driver_name'  => $driver->driver_name ?? $challan->driver_name,
                'license'      => $driver->license ?? $challan->license,
            ]);

            // Collect submitted item IDs
            $submittedIds = collect($validated['items'])
                ->pluck('id')
                ->filter()
                ->toArray();

            // Delete removed items
            $challan->items()->whereNotIn('id', $submittedIds)->delete();

            // Update existing / create new items
            foreach ($validated['items'] as $itemData) {
                if (!empty($itemData['id'])) {
                    ChallanItem::where('id', $itemData['id'])
                               ->where('challan_id', $challan->id)
                               ->update([
                                   'gr_no'       => $itemData['gr_no'],
                                   'description' => $itemData['description'],
                                   'nugs'        => $itemData['nugs'],
                                   'weight'      => $itemData['weight'],
                               ]);
                } else {
                    ChallanItem::create([
                        'challan_id'  => $challan->id,
                        'challan_no'  => $challan->challan_no,
                        'gr_no'       => $itemData['gr_no'],
                        'description' => $itemData['description'],
                        'nugs'        => $itemData['nugs'],
                        'weight'      => $itemData['weight'],
                    ]);
                }
            }

            // Recalculate totals
            $challan->update([
                'total_weight' => $challan->items()->sum('weight'),
            ]);
        });

        return redirect()->route('challan.index')
            ->with('success', 'Challan updated.');
    }

    /**
     * Delete a whole challan.
     * Per SXPRESS_LOGIC_SKILL section 6 - Delete Whole Challan.
     */
    public function destroy($id)
    {
        $challan = challan::findOrFail($id);

        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) {
            abort(403);
        }
        if (!$this->isSuperAdmin() && $challan->office !== $this->currentOffice()) {
            abort(403);
        }

        DB::transaction(function () use ($challan) {
            $challan->items()->delete();
            $challan->delete();
        });

        return redirect()->route('challan.index')
            ->with('success', 'Challan deleted.');
    }

    /**
     * Print challan.
     * GET /dash/challan/{id}/print
     */
    public function print($id)
    {
        $challan = challan::with(['vehicle', 'driver', 'items'])->findOrFail($id);

        if (!$this->isSuperAdmin() && $challan->office !== $this->currentOffice()) {
            abort(403);
        }

        return view('admin.category.challan.challan_print', compact('challan'));
    }

    // ─────────────────────────────────────────────────────────────────
    // PRIVATE HELPER METHODS
    // ─────────────────────────────────────────────────────────────────

    /**
     * Generate challan number.
     * Format: CH-00001 (sequential per branch, 5-digit zero-padded).
     * Per SXPRESS_LOGIC_SKILL section 6.
     */
    private function generateChallanNo(string $office): string
    {
        $last = challan::withTrashed()
            ->orderByRaw("CAST(SUBSTRING(challan_no, 4) AS UNSIGNED) DESC")
            ->first();
        $next = $last ? ((int) substr($last->challan_no, 3)) + 1 : 1;
        return 'CH-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Atomic challan number generation with row locking.
     */
    private function generateChallanNoAtomic(string $office): string
    {
        return DB::transaction(function () use ($office) {
            $last = challan::withTrashed()
                ->lockForUpdate()
                ->orderByRaw("CAST(SUBSTRING(challan_no, 4) AS UNSIGNED) DESC")
                ->first();

            $next = $last ? ((int) substr($last->challan_no, 3)) + 1 : 1;
            return 'CH-' . str_pad($next, 5, '0', STR_PAD_LEFT);
        });
    }
}