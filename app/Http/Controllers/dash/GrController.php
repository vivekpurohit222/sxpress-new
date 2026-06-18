<?php

namespace App\Http\Controllers\dash;

use App\Http\Controllers\Controller;
use App\Models\Gr;
use App\Models\Branch;
use App\Models\BranchSerial;
use App\Models\Consignor;
use App\Models\Consignee;
use App\Events\GRCreated;
use App\Events\GRDelivered;
use App\Events\PODUploaded;
use App\Rules\GstNumberRule;
use App\Services\GrWorkflowService;
use App\Traits\OfficeScopeTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GrController extends Controller
{
    use OfficeScopeTrait;

    private GrWorkflowService $workflow;

    public function __construct(GrWorkflowService $workflow)
    {
        $this->middleware('auth');
        $this->workflow = $workflow;
    }

    /**
     * Display a listing of GRs.
     */
    public function index(Request $request)
    {
        $query = Gr::query();

        // Office scope (SuperAdmin sees all)
        $query = $this->officeScope($query);

        // Search
        if ($s = $request->search) {
            $query->where(function ($q) use ($s) {
                $q->where('gr_no', 'like', "%{$s}%")
                  ->orWhere('consignor', 'like', "%{$s}%")
                  ->orWhere('consignee', 'like', "%{$s}%")
                  ->orWhere('from_dest', 'like', "%{$s}%")
                  ->orWhere('to_dest', 'like', "%{$s}%");
            });
        }

        // Status filter
        if ($status = $request->status) {
            $query->where('status', $status);
        }

        // Date range
        if ($from = $request->from_date) {
            $query->whereDate('copy_date', '>=', $from);
        }
        if ($to = $request->to_date) {
            $query->whereDate('copy_date', '<=', $to);
        }

        // Paid/ToPay filter
        if ($request->payment === 'paid') {
            $query->where('paid', 1);
        }
        if ($request->payment === 'to_pay') {
            $query->where('to_pay', 1);
        }

        // SuperAdmin branch filter
        if ($this->isSuperAdmin() && $branch = $request->branch) {
            $query->where('office', $branch);
        }

        $copies = $query->latest()->paginate(25)->withQueryString();
        $branches = $this->getBranchOptions();

        $copies_list_page = 'GR List';

        return view('admin.category.copies_list', compact('copies', 'copies_list_page', 'branches'));
    }

    /**
     * Show the form for creating a new GR.
     */
    public function create()
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Use currentOffice() which respects impersonation
        $office = $this->currentOffice();

        // SuperAdmin WITHOUT impersonation can pick any office
        // SuperAdmin WITH impersonation is locked to impersonated office
        // Other roles always locked to their own office
        $branches = [];
        if ($this->isSuperAdmin() && !$this->isImpersonating()) {
            $branches = Branch::active()->orderBy('branch_name')->get();
        }

        $newGrNo = $this->generateGrNumber($office);
        $date = Carbon::now()->format('d-m-y');

        // Load destinations dynamically from branches table
        $destinations = Branch::active()->orderBy('branch_name')->pluck('branch_name')->all();

        return view('admin.category.copies', compact('newGrNo', 'date', 'user', 'destinations', 'branches', 'office'));
    }

    /**
     * Store a newly created GR.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->grRules(), $this->grValidationMessages());

        // Mutual exclusivity: paid XOR to_pay
        $paidSelected = (bool) ($validated['paid'] ?? false);
        $toPaySelected = (bool) ($validated['to_pay'] ?? false);

        if ($paidSelected === $toPaySelected) {
            return back()->withInput()->withErrors([
                'paid' => 'Select either Paid OR To-Pay, not both. At least one is required.'
            ]);
        }

        // Determine the office for this GR
        // SuperAdmin can create for any office via from_dest selection
        // Other users are locked to their own office
        if ($this->isSuperAdmin()) {
            $office = $validated['from_dest'];
            // Validate the selected office exists
            if (!Branch::where('branch_name', $office)->exists()) {
                return back()->withInput()->withErrors(['from_dest' => 'Invalid office selected.']);
            }
        } else {
            $office = auth()->user()->office;
            // Force from_dest to be the user's own office (prevent tampering)
            $validated['from_dest'] = $office;
        }

        // Server-side total recalculation — NEVER trust client total
        $validated['total_amount'] = $this->computeTotal($validated);
        $validated['office'] = $office;
        $validated['status'] = 'created';
        $validated['created_by_id'] = auth()->id();

        // Ensure nullable string fields default to empty string (DB has NOT NULL)
        $validated['pm'] = $validated['pm'] ?? '';
        $validated['eway_bill_number'] = $validated['eway_bill_number'] ?? '';
        $validated['consignor_gst_no'] = $validated['consignor_gst_no'] ?? '';
        $validated['consignee_gst_no'] = $validated['consignee_gst_no'] ?? '';

        // Atomic GR number generation using the selected office's prefix
        $validated['gr_no'] = $this->generateGrNumberAtomic($office);

        $gr = Gr::create($validated);

        // Fire event (wrapped in try-catch so GR creation isn't blocked)
        try {
            event(new GRCreated($gr));
        } catch (\Throwable $e) {
            \Log::warning('GRCreated event failed: ' . $e->getMessage());
        }

        return redirect()->route('gr.index')
            ->with('success', "GR {$gr->gr_no} created successfully.");
    }

    /**
     * Display the print view for a GR.
     */
    public function show($id)
    {
        $copy = Gr::findOrFail($id);

        // Office check — users can only print GRs from their own office (SuperAdmin exempt)
        if (!$this->isSuperAdmin() && $copy->office !== $this->currentOffice()) {
            abort(403, 'You can only view GRs from your own office.');
        }

        return view('admin.category.copies_print', compact('copy', 'id'));
    }

    /**
     * Show the form for editing a GR.
     */
    public function edit($id)
    {
        $gr = Gr::findOrFail($id);

        // Office check
        if (!$this->isSuperAdmin() && $gr->office !== $this->currentOffice()) {
            abort(403, 'You do not have permission to edit this GR.');
        }

        // After dispatch, only Admin+ can edit
        if (in_array($gr->status, ['dispatched', 'in_transit', 'delivered', 'closed'])
            && !Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) {
            abort(403, 'GR has been dispatched. Only Admin can edit.');
        }

        // Closed/Cancelled = nobody can edit
        if (in_array($gr->status, ['closed', 'cancelled'])) {
            abort(403, "GR is {$gr->status} and cannot be edited.");
        }

        // Staff can only edit GRs they created (when status = created)
        if (Auth::user()->hasRole('Agent') && $gr->status === 'created') {
            if ($gr->created_by_id && $gr->created_by_id !== auth()->id()) {
                abort(403, 'You can only edit GRs you created.');
            }
        }

        // Load destinations dynamically
        $destinations = Branch::active()->orderBy('branch_name')->pluck('branch_name')->all();

        return view('admin.category.copies_edit', compact('gr', 'id', 'destinations'));
    }

    /**
     * Update a GR.
     */
    public function update(Request $request, $id)
    {
        $gr = Gr::findOrFail($id);

        // Office check
        if (!$this->isSuperAdmin() && $gr->office !== $this->currentOffice()) {
            abort(403);
        }

        // After dispatch, only Admin+ can edit
        if (in_array($gr->status, ['dispatched', 'in_transit', 'delivered', 'closed'])
            && !Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) {
            abort(403, 'GR has been dispatched. Only Admin can edit.');
        }

        // Closed/Cancelled = nobody can edit
        if (in_array($gr->status, ['closed', 'cancelled'])) {
            abort(403, "GR is {$gr->status} and cannot be edited.");
        }

        // Staff ownership check
        if (Auth::user()->hasRole('Agent') && $gr->status === 'created') {
            if ($gr->created_by_id && $gr->created_by_id !== auth()->id()) {
                abort(403, 'You can only edit GRs you created.');
            }
        }

        $validated = $request->validate($this->grRules(), $this->grValidationMessages());

        // Mutual exclusivity check
        $paidSelected = (bool) ($validated['paid'] ?? false);
        $toPaySelected = (bool) ($validated['to_pay'] ?? false);
        if ($paidSelected === $toPaySelected) {
            return back()->withInput()->withErrors([
                'paid' => 'Select either Paid OR To-Pay, not both.'
            ]);
        }

        // Server-side total recalculation
        $validated['total_amount'] = $this->computeTotal($validated);

        // Ensure nullable string fields default to empty string
        $validated['pm'] = $validated['pm'] ?? '';
        $validated['eway_bill_number'] = $validated['eway_bill_number'] ?? '';
        $validated['consignor_gst_no'] = $validated['consignor_gst_no'] ?? '';
        $validated['consignee_gst_no'] = $validated['consignee_gst_no'] ?? '';

        $gr->update($validated);

        return redirect()->route('gr.index')
            ->with('success', "GR {$gr->gr_no} updated successfully.");
    }

    /**
     * Delete a GR.
     */
    public function destroy($id)
    {
        $gr = Gr::findOrFail($id);

        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) {
            abort(403);
        }
        if (!$this->isSuperAdmin() && $gr->office !== $this->currentOffice()) {
            abort(403);
        }

        if ($gr->status !== 'created') {
            return back()->withErrors(['Cannot delete a GR that has been dispatched or is in progress.']);
        }

        // Block if linked to a Gatepass or Challan
        if ($gr->gatepasses()->exists() || $gr->challanItems()->exists()) {
            return back()->withErrors(['Cannot delete GR — it is linked to a Gatepass or Challan.']);
        }

        $gr->delete();

        return redirect()->route('gr.index')->with('success', 'GR deleted successfully.');
    }

    /**
     * GR Autocomplete endpoint for Gatepass/Challan forms.
     */
    public function autocomplete(Request $request)
    {
        $q = $request->get('q', '');

        $query = Gr::query();
        $this->officeScope($query);

        // Filter by purpose
        $for = $request->get('for');
        if ($for === 'freight') {
            // For freight memo — return delivered/dispatched/in_transit GRs
            $query->whereIn('status', ['delivered', 'dispatched', 'in_transit']);
        } elseif ($for === 'gatepass') {
            // For gatepass — return only in_transit GRs at destination office
            $query->where('status', 'in_transit')
                  ->where('to_dest', $this->currentOffice());
        } else {
            // Default (for Challan) — only 'created' GRs not yet loaded
            $query->where('status', 'created');
        }

        $grs = $query
            ->where(function ($q2) use ($q) {
                $q2->where('gr_no', 'like', "%{$q}%")
                   ->orWhere('consignor', 'like', "%{$q}%")
                   ->orWhere('consignee', 'like', "%{$q}%");
            })
            ->select('id', 'gr_no', 'consignor', 'consignee', 'from_dest', 'to_dest',
                     'nugs', 'meth', 'description', 'weight', 'frieght_amount',
                     'sur_ch', 'c_r', 'other', 'total_amount')
            ->limit(10)
            ->get();

        return response()->json($grs);
    }

    /**
     * Autocomplete for consignor.
     */
    public function autocompleteConsignor(Request $request)
    {
        $q = $request->get('q', '');
        if (strlen($q) < 2) return response()->json([]);

        $consignors = Consignor::where('consignor_name', 'like', '%' . $q . '%')
            ->select('id', 'consignor_name as name', 'address', 'city', 'gst_no', 'phone')
            ->limit(10)
            ->get();

        $grConsignors = Gr::where('consignor', 'like', '%' . $q . '%')
            ->select('consignor as name', 'consignor_address as address', 'consignor_gst_no as gst_no')
            ->distinct()
            ->limit(10)
            ->get();

        $results = $consignors->merge($grConsignors)->unique('name')->take(10)->values();
        return response()->json($results);
    }

    /**
     * Autocomplete for consignee.
     */
    public function autocompleteConsignee(Request $request)
    {
        $q = $request->get('q', '');
        if (strlen($q) < 2) return response()->json([]);

        $consignees = Consignee::where('consignee_name', 'like', '%' . $q . '%')
            ->select('id', 'consignee_name as name', 'address', 'city', 'gst_no', 'phone')
            ->limit(10)
            ->get();

        $grConsignees = Gr::where('consignee', 'like', '%' . $q . '%')
            ->select('consignee as name', 'consignee_address as address', 'consignee_gst_no as gst_no')
            ->distinct()
            ->limit(10)
            ->get();

        $results = $consignees->merge($grConsignees)->unique('name')->take(10)->values();
        return response()->json($results);
    }

    // ─────────────────────────────────────────────────────────────────
    // POD METHODS
    // ─────────────────────────────────────────────────────────────────

    /**
     * View/Download POD file with access control.
     * GET /gr/{id}/pod
     */
    public function viewPod($id)
    {
        $gr = Gr::findOrFail($id);

        if (!$this->isSuperAdmin() && $gr->office !== $this->currentOffice()) {
            abort(403);
        }

        if (!$gr->pod_file || !\Storage::disk('public')->exists($gr->pod_file)) {
            abort(404, 'POD file not found.');
        }

        return response()->file(\Storage::disk('public')->path($gr->pod_file));
    }

    public function uploadPodForm($id)
    {
        $gr = Gr::findOrFail($id);
        if (!$this->isSuperAdmin() && $gr->office !== $this->currentOffice()) {
            abort(403);
        }
        return view('admin.category.gr_upload_pod', compact('gr'));
    }

    public function uploadPod(Request $request, $id)
    {
        $gr = Gr::findOrFail($id);

        if (!$this->isSuperAdmin() && $gr->office !== $this->currentOffice()) {
            abort(403);
        }

        $request->validate([
            'pod_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'pod_date' => 'required|date|before_or_equal:today',
            'pod_note' => 'nullable|string|max:300',
        ]);

        if (!in_array($gr->status, ['dispatched', 'in_transit'])) {
            return back()->withErrors(['pod_file' => "Cannot upload POD — GR is '{$gr->status}'."]);
        }

        $file = $request->file('pod_file');
        $fileName = 'pod_' . $gr->gr_no . '_' . time() . '.' . $file->getClientOriginalExtension();

        // Delete old POD file if re-uploading
        if ($gr->pod_file && \Storage::disk('public')->exists($gr->pod_file)) {
            \Storage::disk('public')->delete($gr->pod_file);
        }

        $path = $file->storeAs('pods', $fileName, 'public');

        $gr->update([
            'pod_file'        => $path,  // store full relative path
            'pod_date'        => $request->pod_date,
            'pod_note'        => $request->pod_note,
            'pod_uploaded_by' => auth()->id(),
        ]);

        // Auto-transition to delivered
        if ($gr->status === 'in_transit') {
            $this->workflow->transition($gr, 'delivered', auth()->user());
        } elseif ($gr->status === 'dispatched') {
            // Skip in_transit — POD upload goes straight to delivered (per skill §8)
            $gr->update([
                'status' => 'delivered',
                'status_updated_at' => now(),
                'status_updated_by' => auth()->id(),
            ]);
        }

        try {
            event(new PODUploaded($gr));
            event(new GRDelivered($gr));
        } catch (\Throwable $e) {
            \Log::warning('POD event failed: ' . $e->getMessage());
        }

        return redirect()->route('gr.edit', $gr->id)
            ->with('success', 'POD uploaded successfully. GR marked as Delivered.');
    }

    public function markDelivered(Request $request, $id)
    {
        $gr = Gr::findOrFail($id);

        if (!$this->isSuperAdmin() && $gr->office !== $this->currentOffice()) {
            abort(403);
        }

        if (!in_array($gr->status, ['dispatched', 'in_transit'])) {
            return response()->json(['success' => false, 'message' => "GR is '{$gr->status}'."], 422);
        }

        // in_transit → delivered (valid transition)
        if ($gr->status === 'in_transit') {
            $this->workflow->transition($gr, 'delivered', auth()->user());
        } else {
            // dispatched → delivered (skip in_transit for direct delivery confirmation)
            $gr->update([
                'status' => 'delivered',
                'status_updated_at' => now(),
                'status_updated_by' => auth()->id(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'GR marked as delivered.']);
    }

    public function updateDeliveryStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:dispatched,in_transit,delivered',
        ]);

        $gr = Gr::findOrFail($id);

        if (!$this->isSuperAdmin() && $gr->office !== $this->currentOffice()) {
            abort(403);
        }

        if (!$this->workflow->canTransition($gr, $request->status)) {
            return response()->json([
                'success' => false,
                'message' => "Invalid status transition from '{$gr->status}' to '{$request->status}'."
            ], 422);
        }

        $this->workflow->transition($gr, $request->status, auth()->user());

        return response()->json(['success' => true, 'message' => 'Delivery status updated.']);
    }

    // ─────────────────────────────────────────────────────────────────
    // TO-PAY COLLECTION
    // ─────────────────────────────────────────────────────────────────

    public function markTopayCollected($id)
    {
        $gr = Gr::findOrFail($id);

        if (!$this->isSuperAdmin() && $gr->office !== $this->currentOffice()) {
            abort(403);
        }

        if (!$gr->to_pay) {
            return response()->json(['success' => false, 'message' => 'This GR is Paid — not To-Pay.'], 400);
        }

        if ($gr->topay_collected) {
            return response()->json(['success' => false, 'message' => 'Already collected on ' . $gr->topay_collected_date . '.'], 400);
        }

        $gr->update([
            'topay_collected'      => true,
            'topay_collected_date' => now()->toDateString(),
            'topay_collected_by'   => auth()->id(),
        ]);

        // Create accounting entry for TO-PAY collection (Phase 8 integration)
        try {
            \App\Listeners\CreateTopayCollectionEntry::handle($gr);
        } catch (\Throwable $e) {
            \Log::warning('Accounting TO-PAY entry failed: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'TO-PAY marked as collected.']);
    }

    public function undoTopayCollected($id)
    {
        $gr = Gr::findOrFail($id);

        // Only Admin+ can undo a TO-PAY collection
        if (!Auth::user()->hasAnyRole(['SuperAdmin', 'BranchManager'])) {
            abort(403, 'Only Admin or SuperAdmin can undo a TO-PAY collection.');
        }

        if (!$this->isSuperAdmin() && $gr->office !== $this->currentOffice()) {
            abort(403);
        }

        $gr->update([
            'topay_collected'      => false,
            'topay_collected_date' => null,
            'topay_collected_by'   => null,
        ]);

        return response()->json(['success' => true, 'message' => 'TO-PAY collection undone.']);
    }

    // ─────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────

    /**
     * AJAX: Get next GR number for a given office (SuperAdmin office switching).
     * GET /gr/next-number/{office}
     */
    public function nextGrNumber($office)
    {
        if (!$this->isSuperAdmin()) {
            abort(403);
        }

        $branch = Branch::where('branch_name', $office)->first();
        if (!$branch) {
            return response()->json(['error' => 'Invalid office'], 404);
        }

        $grNo = $this->generateGrNumber($office);

        return response()->json([
            'gr_no' => $grNo,
            'office' => $office,
            'prefix' => $branch->gr_prefix ?? '??',
        ]);
    }

    /**
     * Generate GR number with atomic row locking to prevent race conditions.
     */
    private function generateGrNumberAtomic(string $office): string
    {
        return DB::transaction(function () use ($office) {
            // Determine prefix
            $prefix = $this->getGrPrefix($office);

            // Lock and get the last GR with this prefix
            $lastGr = Gr::where('gr_no', 'like', $prefix . '-%')
                ->orderByRaw('CAST(SUBSTRING_INDEX(gr_no, \'-\', -1) AS UNSIGNED) DESC')
                ->lockForUpdate()
                ->first();

            // Check BranchSerial for start_from
            $branchSerial = BranchSerial::where('office', $office)->first();
            $startFrom = $branchSerial ? $branchSerial->start_from : 1;

            $nextNum = $lastGr
                ? ((int) substr($lastGr->gr_no, strlen($prefix) + 1)) + 1
                : $startFrom;

            return $prefix . '-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Non-locking GR number generation (for display on create form only).
     */
    private function generateGrNumber(string $office): string
    {
        $prefix = $this->getGrPrefix($office);

        $lastGr = Gr::where('gr_no', 'like', $prefix . '-%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(gr_no, \'-\', -1) AS UNSIGNED) DESC')
            ->first();

        $branchSerial = BranchSerial::where('office', $office)->first();
        $startFrom = $branchSerial ? $branchSerial->start_from : 1;

        $nextNum = $lastGr
            ? ((int) substr($lastGr->gr_no, strlen($prefix) + 1)) + 1
            : $startFrom;

        return $prefix . '-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get the GR prefix for an office.
     */
    private function getGrPrefix(string $office): string
    {
        // Priority 1: BranchSerial
        $branchSerial = BranchSerial::where('office', $office)->first();
        if ($branchSerial && $branchSerial->gr_prefix) {
            return $branchSerial->gr_prefix;
        }

        // Priority 2: Branch.gr_prefix
        $branch = Branch::where('branch_name', $office)->first();
        if ($branch && $branch->gr_prefix) {
            return $branch->gr_prefix;
        }

        // Priority 3: hardcoded fallback
        $map = [
            'Rajkot' => 'AA', 'Kashmore Gate' => 'CG', 'Navagam' => 'NV',
            'Dayabasti' => 'DB', 'Swarup Nagar' => 'SN',
            'Shapar (1)' => 'S1', 'Shapar (2)' => 'S2',
        ];
        return $map[$office] ?? 'GR';
    }

    /**
     * Server-side total amount calculation.
     */
    private function computeTotal(array $data): float
    {
        return round(
            floatval($data['frieght_amount'] ?? 0) +
            floatval($data['sur_ch'] ?? 0) +
            floatval($data['c_r'] ?? 0) +
            floatval($data['other'] ?? 0) +
            floatval($data['bc_amount'] ?? 0),
            2
        );
    }

    /**
     * GR validation rules.
     */
    private function grRules(): array
    {
        return [
            'copy_date'         => 'required|date|before_or_equal:today',
            'from_dest'         => 'required|string|max:100',
            'to_dest'           => 'required|string|max:100|different:from_dest',
            'consignor'         => 'required|string|max:200',
            'consignor_address' => 'required|string|max:500',
            'consignor_gst_no'  => ['nullable', 'string', 'max:15', new GstNumberRule()],
            'consignee'         => 'required|string|max:200',
            'consignee_address' => 'required|string|max:500',
            'consignee_gst_no'  => ['nullable', 'string', 'max:15', new GstNumberRule()],
            'nugs'              => 'required|integer|min:1',
            'meth'              => 'required|string|in:Bag,Box,Bundle,Drum,Roll,Carton,Loose,Other',
            'weight'            => 'required|numeric|min:0.01',
            'description'       => 'required|string|max:500',
            'pm'                => 'nullable|string|max:50',
            'eway_bill_number'  => 'nullable|string|max:12',
            'bill_amount'       => 'nullable|numeric|min:0',
            'frieght_amount'    => 'required|numeric|min:0',
            'sur_ch'            => 'nullable|numeric|min:0',
            'c_r'               => 'nullable|numeric|min:0',
            'other'             => 'nullable|numeric|min:0',
            'bc_amount'         => 'nullable|numeric|min:0',
            'paid'              => 'boolean',
            'to_pay'            => 'boolean',
        ];
    }

    private function grValidationMessages(): array
    {
        return [
            'meth.required'              => 'Package method is required.',
            'from_dest.required'         => 'From destination is required.',
            'to_dest.required'           => 'To destination is required.',
            'to_dest.different'          => 'From and To destinations must be different.',
            'consignor.required'         => 'Consignor name is required.',
            'consignor_address.required' => 'Consignor address is required.',
            'consignee.required'         => 'Consignee name is required.',
            'consignee_address.required' => 'Consignee address is required.',
            'nugs.required'              => 'Number of packages is required.',
            'nugs.min'                   => 'At least 1 package is required.',
            'weight.min'                 => 'Weight must be greater than 0.',
            'eway_bill_number.max'       => 'E-Way bill number cannot exceed 12 characters.',
        ];
    }
}
