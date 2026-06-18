<?php

namespace App\Http\Controllers\dash;

use App\Http\Controllers\Controller;
use App\Models\Gr;
use App\Models\Branch;
use App\Models\BranchSerial;
use App\Models\Customer;
use App\Models\Consignor;
use App\Models\Consignee;
use App\Events\GRCreated;
use App\Events\GRDelivered;
use App\Events\PODUploaded;
use App\Services\GrWorkflowService;
use App\Services\SerialNumberService;
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

        // Payment type: single radio (to_pay=1 means TO PAY, to_pay=0 means PAID)
        $validated['to_pay'] = $request->to_pay == '1' ? 1 : 0;
        $validated['paid'] = $request->to_pay == '0' ? 1 : 0;

        // Determine the office for this GR
        if ($this->isSuperAdmin()) {
            $office = $validated['from_dest'];
            if (!Branch::where('branch_name', $office)->exists()) {
                return back()->withInput()->withErrors(['from_dest' => 'Invalid office selected.']);
            }
        } else {
            $office = auth()->user()->office;
            $validated['from_dest'] = $office;
        }

        // Server-side total recalculation — NEVER trust client total
        $validated['total_amount'] = $this->computeTotal($validated);
        $validated['office'] = $office;
        $validated['status'] = 'created';
        $validated['created_by_id'] = auth()->id();

        // If method is "Other", use the meth_other text value
        if ($validated['meth'] === 'Other' && $request->filled('meth_other')) {
            $validated['meth'] = $request->meth_other;
        }

        // Ensure nullable string fields default to empty string (DB has NOT NULL)
        $validated['pm'] = $validated['pm'] ?? '';
        $validated['eway_bill_number'] = $validated['eway_bill_number'] ?? '';
        $validated['consignor_gst_no'] = $validated['consignor_gst_no'] ?? '';
        $validated['consignee_gst_no'] = $validated['consignee_gst_no'] ?? '';
        $validated['consignor_address'] = $validated['consignor_address'] ?? '';
        $validated['consignee_address'] = $validated['consignee_address'] ?? '';

        // Numeric defaults
        $validated['sur_ch'] = $validated['sur_ch'] ?? 0;
        $validated['labour'] = $validated['labour'] ?? 0;
        $validated['dd'] = $validated['dd'] ?? 0;
        $validated['c_r'] = $validated['c_r'] ?? 0;
        $validated['bc_amount'] = $validated['bc_amount'] ?? 0;
        $validated['other'] = $validated['other'] ?? 0;
        $validated['bill_amount'] = $validated['bill_amount'] ?? 0;
        $validated['rate'] = $validated['rate'] ?? 0;
        $validated['weight'] = $validated['weight'] ?? 0;

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

        // Payment type: single radio (to_pay=1 means TO PAY, to_pay=0 means PAID)
        $validated['to_pay'] = $request->to_pay == '1' ? 1 : 0;
        $validated['paid'] = $request->to_pay == '0' ? 1 : 0;

        // If method is "Other", use the meth_other text value
        if ($validated['meth'] === 'Other' && $request->filled('meth_other')) {
            $validated['meth'] = $request->meth_other;
        }

        // Server-side total recalculation
        $validated['total_amount'] = $this->computeTotal($validated);

        // Ensure nullable string fields default to empty string
        $validated['pm'] = $validated['pm'] ?? '';
        $validated['eway_bill_number'] = $validated['eway_bill_number'] ?? '';
        $validated['consignor_gst_no'] = $validated['consignor_gst_no'] ?? '';
        $validated['consignee_gst_no'] = $validated['consignee_gst_no'] ?? '';
        $validated['consignor_address'] = $validated['consignor_address'] ?? '';
        $validated['consignee_address'] = $validated['consignee_address'] ?? '';

        // Numeric defaults
        $validated['sur_ch'] = $validated['sur_ch'] ?? 0;
        $validated['labour'] = $validated['labour'] ?? 0;
        $validated['dd'] = $validated['dd'] ?? 0;
        $validated['c_r'] = $validated['c_r'] ?? 0;
        $validated['bc_amount'] = $validated['bc_amount'] ?? 0;
        $validated['other'] = $validated['other'] ?? 0;
        $validated['bill_amount'] = $validated['bill_amount'] ?? 0;
        $validated['rate'] = $validated['rate'] ?? 0;
        $validated['weight'] = $validated['weight'] ?? 0;

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
     * Fetch customer (consignor/consignee) by GST number for AJAX auto-fill.
     */
    public function fetchByGst(Request $request)
    {
        $gst = $request->get('gst_no');

        // Search in consignors first, then consignees
        $consignor = Consignor::where('gst_no', $gst)->first();
        if ($consignor) {
            return response()->json([
                'found' => true,
                'id' => $consignor->id,
                'name' => $consignor->consignor_name,
                'gst_no' => $consignor->gst_no,
                'rate_per_nug' => $consignor->rate_per_nug ?? 0,
                'rate_per_kg' => $consignor->rate_per_kg ?? 0,
            ]);
        }

        $consignee = Consignee::where('gst_no', $gst)->first();
        if ($consignee) {
            return response()->json([
                'found' => true,
                'id' => $consignee->id,
                'name' => $consignee->consignee_name,
                'gst_no' => $consignee->gst_no,
                'rate_per_nug' => $consignee->rate_per_nug ?? 0,
                'rate_per_kg' => $consignee->rate_per_kg ?? 0,
            ]);
        }

        // Also check Customer table
        $customer = Customer::where('gst_no', $gst)->first();
        if ($customer) {
            return response()->json([
                'found' => true,
                'id' => $customer->id,
                'name' => $customer->customer_name,
                'gst_no' => $customer->gst_no,
                'rate_per_nug' => $customer->rate_per_nug ?? 0,
                'rate_per_kg' => $customer->rate_per_kg ?? 0,
            ]);
        }

        return response()->json(['found' => false]);
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
        $branch = Branch::where('branch_name', $office)->first();
        return SerialNumberService::generateNext($branch->id, 'gr');
    }

    /**
     * Non-locking GR number generation (for display on create form only).
     */
    private function generateGrNumber(string $office): string
    {
        $branch = Branch::where('branch_name', $office)->first();
        return SerialNumberService::previewNext($branch->id, 'gr');
    }

    /**
     * Server-side total amount calculation.
     */
    private function computeTotal(array $data): float
    {
        return round(
            floatval($data['frieght_amount'] ?? 0) +
            floatval($data['sur_ch'] ?? 0) +
            floatval($data['labour'] ?? 0) +
            floatval($data['dd'] ?? 0) +
            floatval($data['c_r'] ?? 0) +
            floatval($data['bc_amount'] ?? 0) +
            floatval($data['other'] ?? 0),
            2
        );
    }

    /**
     * GR validation rules.
     */
    private function grRules(): array
    {
        return [
            'copy_date'         => 'required|date',
            'from_dest'         => 'required|string|max:100',
            'to_dest'           => 'required|string|max:100|different:from_dest',
            'consignor'         => 'required|string|max:200',
            'consignor_address' => 'nullable|string|max:500',
            'consignor_gst_no'  => 'nullable|string|max:15',
            'consignee'         => 'required|string|max:200',
            'consignee_address' => 'nullable|string|max:500',
            'consignee_gst_no'  => 'nullable|string|max:15',
            'consignor_id'      => 'nullable|integer',
            'consignee_id'      => 'nullable|integer',
            'nugs'              => 'required|integer|min:1',
            'meth'              => 'required|string',
            'weight'            => 'nullable|numeric|min:0',
            'description'       => 'required|string|max:500',
            'pm'                => 'required|string|max:50',
            'eway_bill_number'  => 'nullable|string|max:20',
            'bill_amount'       => 'required|numeric|min:0',
            'rate_type'         => 'nullable|in:by_nugs,by_weight',
            'rate'              => 'nullable|numeric|min:0',
            'frieght_amount'    => 'required|numeric|min:0',
            'sur_ch'            => 'nullable|numeric|min:0',
            'labour'            => 'nullable|numeric|min:0',
            'dd'                => 'nullable|numeric|min:0',
            'c_r'               => 'nullable|numeric|min:0',
            'bc_amount'         => 'nullable|numeric|min:0',
            'other'             => 'nullable|numeric|min:0',
            'to_pay'            => 'required|in:0,1',
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
            'consignee.required'         => 'Consignee name is required.',
            'nugs.required'              => 'Number of packages is required.',
            'nugs.min'                   => 'At least 1 package is required.',
            'weight.min'                 => 'Weight must be greater than 0.',
            'eway_bill_number.max'       => 'E-Way bill number cannot exceed 20 characters.',
        ];
    }
}
