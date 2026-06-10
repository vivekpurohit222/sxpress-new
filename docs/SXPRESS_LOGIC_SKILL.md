---
name: sxpress-transport-logic
description: >
  Complete business logic, role-based access control, validation rules, and workflow
  for the SXpress Freight/Logistics Management System (Laravel 10 + PHP 8 + MySQL 8).
  MUST be consulted before writing ANY controller method, form, query, permission check,
  document workflow, or data mutation across ALL modules: GR, Gatepass, Challan,
  Challan Items, Freight Memo, POD, Consignor, Consignee, Vehicle, Driver, Branch,
  Route, Station, Users, and Serial Number assignment. Never guess business rules —
  always follow this skill. Use for every feature, bug fix, or new module in SXpress.
---

# SXpress Transport System — Complete Logic Skill

## QUICK NAVIGATION

1. [Role Hierarchy & Full Permission Matrix](#1-role-hierarchy--full-permission-matrix)
2. [Data Isolation by Office](#2-data-isolation-by-office)
3. [Document Workflow — State Machine](#3-document-workflow--state-machine)
4. [GR (Goods Receipt) Logic](#4-gr-goods-receipt-logic)
5. [Gatepass Logic](#5-gatepass-logic)
6. [Challan + Challan Items Logic](#6-challan--challan-items-logic)
7. [Freight Memo Logic](#7-freight-memo-logic)
8. [POD (Proof of Delivery) Logic](#8-pod-proof-of-delivery-logic)
9. [Consignor & Consignee Logic](#9-consignor--consignee-logic)
10. [Vehicle & Driver Logic](#10-vehicle--driver-logic)
11. [Route & Station Logic](#11-route--station-logic)
12. [SuperAdmin Exclusive Actions](#12-superadmin-exclusive-actions)
13. [Serial Number Assignment](#13-serial-number-assignment-by-superadmin)
14. [User Management Logic](#14-user-management-logic)
15. [Financial Logic](#15-financial-logic)
16. [Validation Rules Reference](#16-validation-rules-reference)
17. [Controller Patterns](#17-controller-patterns)
18. [Common Mistakes to Avoid](#18-common-mistakes-to-avoid)
19. [Appendix A — All Required Migrations](#appendix-a--all-required-migrations)
20. [Appendix B — Spatie Roles & Permissions Seeder](#appendix-b--spatie-roles--permissions-seeder)

---

## 1. ROLE HIERARCHY & FULL PERMISSION MATRIX

### Role Levels (highest → lowest)
```
SuperAdmin  ← Full company access. All branches. All actions. God mode.
   │
Admin       ← Branch head. Full control within own branch only.
   │
Manager     ← Can approve/finalize documents. Cannot delete anything.
   │
Staff       ← Day-to-day entry. Create & view own branch only.
   │
Viewer      ← Read-only. Cannot create or modify anything.
```

### Complete Permission Matrix

| Action | SuperAdmin | Admin | Manager | Staff | Viewer |
|--------|:---:|:---:|:---:|:---:|:---:|
| **OFFICE / BRANCH** |
| Create Office | ✅ only | ❌ | ❌ | ❌ | ❌ |
| Edit Office | ✅ only | ❌ | ❌ | ❌ | ❌ |
| Delete Office | ✅ only | ❌ | ❌ | ❌ | ❌ |
| View all offices | ✅ | ❌ | ❌ | ❌ | ❌ |
| Assign GR Serial to Branch | ✅ only | ❌ | ❌ | ❌ | ❌ |
| **USERS** |
| Create user (any branch) | ✅ | ❌ | ❌ | ❌ | ❌ |
| Create user (own branch) | ✅ | ✅ | ❌ | ❌ | ❌ |
| Edit user (any branch) | ✅ | ❌ | ❌ | ❌ | ❌ |
| Edit user (own branch) | ✅ | ✅ | ❌ | ❌ | ❌ |
| Assign SuperAdmin/Admin role | ✅ only | ❌ | ❌ | ❌ | ❌ |
| Assign Manager/Staff/Viewer role | ✅ | ✅ | ❌ | ❌ | ❌ |
| Deactivate/reactivate user | ✅ | ✅ (own branch) | ❌ | ❌ | ❌ |
| Change user's branch | ✅ only | ❌ | ❌ | ❌ | ❌ |
| **GR (GOODS RECEIPT)** |
| Create GR | ✅ | ✅ | ✅ | ✅ | ❌ |
| Edit GR (status: created) | ✅ | ✅ | ✅ | ✅ own | ❌ |
| Edit GR (status: dispatched+) | ✅ | ✅ | ❌ | ❌ | ❌ |
| Delete GR (status: created only) | ✅ | ✅ | ❌ | ❌ | ❌ |
| Print GR | ✅ | ✅ | ✅ | ✅ | ✅ |
| Cancel GR | ✅ | ✅ | ❌ | ❌ | ❌ |
| Close GR (final billing) | ✅ only | ❌ | ❌ | ❌ | ❌ |
| **GATEPASS** |
| Create Gatepass | ✅ | ✅ | ✅ | ✅ | ❌ |
| Edit Gatepass | ✅ | ✅ | ✅ | ❌ | ❌ |
| Delete Gatepass | ✅ | ✅ | ❌ | ❌ | ❌ |
| Print Gatepass | ✅ | ✅ | ✅ | ✅ | ✅ |
| **CHALLAN** |
| Create Challan | ✅ | ✅ | ✅ | ✅ | ❌ |
| Add/Edit Challan Items | ✅ | ✅ | ✅ | ✅ | ❌ |
| Delete Challan Item | ✅ | ✅ | ✅ | ❌ | ❌ |
| Delete Challan (whole) | ✅ | ✅ | ❌ | ❌ | ❌ |
| Print Challan | ✅ | ✅ | ✅ | ✅ | ✅ |
| **FREIGHT MEMO** |
| Create Freight Memo | ✅ | ✅ | ✅ | ❌ | ❌ |
| Edit Freight Memo | ✅ | ✅ | ❌ | ❌ | ❌ |
| Delete Freight Memo | ✅ | ✅ | ❌ | ❌ | ❌ |
| Print Freight Memo | ✅ | ✅ | ✅ | ✅ | ✅ |
| **TO-PAY & POD** |
| Mark TO-PAY Collected | ✅ | ✅ | ✅ | ✅ | ❌ |
| Upload POD | ✅ | ✅ | ✅ | ✅ | ❌ |
| View POD document | ✅ | ✅ | ✅ | ✅ | ✅ |
| **MASTERS** |
| Manage Consignors | ✅ | ✅ | ✅ | ✅ | ❌ |
| Manage Consignees | ✅ | ✅ | ✅ | ✅ | ❌ |
| Manage Customers | ✅ | ✅ | ✅ | ✅ | ❌ |
| Manage Vehicles | ✅ | ✅ | ❌ | ❌ | ❌ |
| Manage Drivers | ✅ | ✅ | ❌ | ❌ | ❌ |
| Manage Routes | ✅ | ✅ | ❌ | ❌ | ❌ |
| Manage Stations | ✅ | ✅ | ❌ | ❌ | ❌ |
| **REPORTS** |
| View reports (own branch) | ✅ | ✅ | ✅ | ❌ | ❌ |
| View reports (all branches) | ✅ only | ❌ | ❌ | ❌ | ❌ |
| Export reports | ✅ | ✅ | ✅ | ❌ | ❌ |

### Applying Permission Checks

**Middleware on route groups (routes/web.php):**
```php
// SuperAdmin ONLY routes
Route::middleware(['auth', 'role:SuperAdmin'])->group(function () {
    Route::resource('dash/branch', BranchController::class);
    Route::get('dash/serial-assign', [SerialController::class, 'index'])->name('serial.index');
    Route::post('dash/serial-assign', [SerialController::class, 'assign'])->name('serial.assign');
    Route::get('dash/reports/all', [ReportController::class, 'allBranches'])->name('reports.all');
});

// Admin + SuperAdmin
Route::middleware(['auth', 'role:SuperAdmin|Admin'])->group(function () {
    Route::resource('dash/users', UserController::class);
    Route::resource('dash/vehicle', VehicleController::class);
    Route::resource('dash/truckdriver', TruckDriverController::class);
    Route::resource('dash/route', RouteController::class);
    Route::resource('dash/station', StationController::class);
});

// Manager+ (create/edit operations)
Route::middleware(['auth', 'role:SuperAdmin|Admin|Manager'])->group(function () {
    Route::resource('dash/frieghtmemo', FreightController::class);
    Route::get('dash/reports', [ReportController::class, 'index'])->name('reports.index');
});

// Staff+ (day-to-day operations)
Route::middleware(['auth', 'role:SuperAdmin|Admin|Manager|Staff'])->group(function () {
    Route::resource('dash/gr', GrController::class);
    Route::resource('dash/gatepass', GatepassController::class);
    Route::resource('dash/challan', ChallanController::class);
    Route::delete('dash/challan-item/{id}', [ChallanController::class, 'destroyItem'])->name('challan.item.destroy');
    Route::resource('dash/consignor', ConsignorController::class);
    Route::resource('dash/consignee', ConsigneeController::class);
    Route::resource('dash/customer', CustomerController::class);
    Route::post('dash/gr/{gr}/pod', [GrController::class, 'uploadPod'])->name('gr.pod.upload');
    Route::post('dash/gr/{gr}/topay-collect', [GrController::class, 'markTopayCollected'])->name('gr.topay.collect');
});

// All authenticated users (read + print)
Route::middleware(['auth'])->group(function () {
    Route::get('dash/gr/{id}/print', [GrController::class, 'print'])->name('gr.print');
    Route::get('dash/gatepass/{id}/print', [GatepassController::class, 'print'])->name('gatepass.print');
    Route::get('dash/challan/{id}/print', [ChallanController::class, 'print'])->name('challan.print');
    Route::get('dash/frieghtmemo/{id}/print', [FreightController::class, 'print'])->name('freight.print');
    // Autocomplete endpoints
    Route::get('dash/autocomplete/consignor', [ConsignorController::class, 'autocomplete'])->name('autocomplete.consignor');
    Route::get('dash/autocomplete/consignee', [ConsigneeController::class, 'autocomplete'])->name('autocomplete.consignee');
    Route::get('dash/autocomplete/gr', [GrController::class, 'autocomplete'])->name('autocomplete.gr');
});
```

**In Blade views:**
```blade
@role('SuperAdmin')
    <a href="{{ route('branch.create') }}" class="btn btn-primary">+ New Office</a>
@endrole

@hasanyrole('SuperAdmin|Admin')
    <button class="btn btn-danger btn-delete">Delete</button>
@endhasanyrole

@hasanyrole('SuperAdmin|Admin|Manager')
    <a href="{{ route('frieghtmemo.create') }}">Create Freight Memo</a>
@endhasanyrole

@can('delete', $challanItem)
    <button class="btn-remove-item">Remove Item</button>
@endcan
```

---

## 2. DATA ISOLATION BY OFFICE

**Rule: Every query on operational data MUST be scoped by office. SuperAdmin bypasses this.**

### Office Scope Helper (add to a Trait or BaseController)
```php
// app/Traits/OfficeScopeTrait.php
trait OfficeScopeTrait
{
    protected function officeScope($query)
    {
        if (auth()->user()->hasRole('SuperAdmin')) {
            return $query; // sees all branches
        }
        return $query->where('office', auth()->user()->office);
    }

    protected function currentOffice(): string
    {
        return auth()->user()->office;
    }

    protected function isSuperAdmin(): bool
    {
        return auth()->user()->hasRole('SuperAdmin');
    }
}
```

### Use in every controller:
```php
// GrController, GatepassController, ChallanController, FreightController
use OfficeScopeTrait;

public function index()
{
    $items = $this->officeScope(GR::query())->latest()->paginate(25);
    return view('admin.category.gr_list', compact('items'));
}
```

### Stamping office on create (ALL document create methods):
```php
$data['office'] = auth()->user()->office;
```

### SuperAdmin branch filter dropdown (on all list pages):
```php
// Only show branch filter to SuperAdmin
if ($this->isSuperAdmin() && $branch = request('branch')) {
    $query->where('office', $branch);
}
$branches = $this->isSuperAdmin() ? Branch::pluck('name') : collect();
```

---

## 3. DOCUMENT WORKFLOW — STATE MACHINE

Every GR passes through these states. All other documents (Gatepass, Challan, Freight Memo) trigger automatic GR state transitions.

### GR Status Flow
```
[created] ──→ [dispatched] ──→ [in_transit] ──→ [delivered] ──→ [closed]
     ↓               ↓
[cancelled]      [cancelled]    (only Admin+)
```

### What triggers each transition
| Trigger | Old Status | New Status |
|---------|-----------|-----------|
| Gatepass created & linked to GR | created | dispatched |
| Admin manually marks in-transit | dispatched | in_transit |
| POD uploaded | in_transit | delivered |
| Freight Memo created for GR | delivered | closed |
| Admin/SuperAdmin cancels | created / dispatched | cancelled |

### State Transition Enforcer
```php
// app/Services/GrWorkflowService.php
class GrWorkflowService
{
    private array $transitions = [
        'created'    => ['dispatched', 'cancelled'],
        'dispatched' => ['in_transit', 'cancelled'],
        'in_transit' => ['delivered'],
        'delivered'  => ['closed'],
        'closed'     => [],
        'cancelled'  => [],
    ];

    public function transition(GR $gr, string $newStatus, User $user): void
    {
        if (!in_array($newStatus, $this->transitions[$gr->status] ?? [])) {
            throw new \Exception("Invalid transition: {$gr->status} → {$newStatus}");
        }

        if ($newStatus === 'closed' && !$user->hasRole('SuperAdmin')) {
            throw new \Exception("Only SuperAdmin can close a GR.");
        }

        if (in_array($newStatus, ['cancelled']) && !$user->hasAnyRole(['SuperAdmin', 'Admin'])) {
            throw new \Exception("Only Admin or SuperAdmin can cancel a GR.");
        }

        $gr->update([
            'status'            => $newStatus,
            'status_updated_at' => now(),
            'status_updated_by' => $user->id,
        ]);
    }
}
```

### Status Badge Colors (Blade)
```php
// Helper: get badge class for status
$badgeClass = match($gr->status) {
    'created'    => 'badge bg-secondary',
    'dispatched' => 'badge bg-primary',
    'in_transit' => 'badge bg-warning text-dark',
    'delivered'  => 'badge bg-info',
    'closed'     => 'badge bg-success',
    'cancelled'  => 'badge bg-danger',
    default      => 'badge bg-light',
};
```

---

## 4. GR (GOODS RECEIPT) LOGIC

### GR Number Generation
```php
// Prefix comes from branches.gr_prefix column (NOT hardcoded)
public function generateGrNumber(string $officeName): string
{
    $branch = Branch::where('name', $officeName)->firstOrFail();
    $prefix = $branch->gr_prefix;

    // Respect SuperAdmin-assigned start number
    $serial    = BranchSerial::where('office', $officeName)->first();
    $startFrom = $serial ? $serial->start_from : 1;

    // Find last GR for this prefix (cast to integer for correct ordering)
    $lastGR = GR::where('gr_no', 'like', $prefix . '-%')
                 ->orderByRaw('CAST(SUBSTRING_INDEX(gr_no, \'-\', -1) AS UNSIGNED) DESC')
                 ->first();

    $nextNum = $lastGR
        ? ((int) explode('-', $lastGR->gr_no)[1]) + 1
        : $startFrom;

    return $prefix . '-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
}
```

### GR Create — Full store() Method
```php
public function store(Request $request)
{
    $this->authorize('create', GR::class);

    $validated = $request->validate($this->grRules());

    // Mutual exclusivity: paid XOR to_pay
    if ((bool)$validated['paid'] === (bool)$validated['to_pay']) {
        return back()->withInput()->withErrors(['paid' => 'Select either Paid OR To-Pay, not both.']);
    }

    // Server-side total recalculation — NEVER trust client total
    $validated['total_amount'] = $this->computeTotal($validated);
    $validated['office']       = auth()->user()->office;
    $validated['gr_no']        = $this->generateGrNumber($validated['office']);
    $validated['status']       = 'created';

    $gr = GR::create($validated);

    return redirect()->route('gr.index')
        ->with('success', "GR {$gr->gr_no} created successfully.");
}
```

### GR Edit Rules
```php
public function edit(GR $gr)
{
    // Office check
    if (!$this->isSuperAdmin() && $gr->office !== $this->currentOffice()) {
        abort(403);
    }

    // After dispatch, only Admin+
    if (in_array($gr->status, ['dispatched','in_transit','delivered','closed'])
        && !auth()->user()->hasAnyRole(['SuperAdmin','Admin'])) {
        abort(403, 'GR has been dispatched. Only Admin can edit.');
    }

    // Closed/Cancelled = nobody can edit
    if (in_array($gr->status, ['closed','cancelled'])) {
        abort(403, "GR is {$gr->status} and cannot be edited.");
    }

    return view('admin.category.gr_edit', compact('gr'));
}
```

### GR Delete Rules
```php
public function destroy(GR $gr)
{
    if (!auth()->user()->hasAnyRole(['SuperAdmin','Admin'])) abort(403);
    if (!$this->isSuperAdmin() && $gr->office !== $this->currentOffice()) abort(403);

    if ($gr->status !== 'created') {
        return back()->withErrors(['Cannot delete a GR that has been dispatched or is in progress.']);
    }

    // Block if linked to a Gatepass or Challan
    if ($gr->gatepasses()->exists() || $gr->challans()->exists()) {
        return back()->withErrors(['Cannot delete GR — it is linked to a Gatepass or Challan.']);
    }

    $gr->delete();
    return redirect()->route('gr.index')->with('success', 'GR deleted.');
}
```

### GR Autocomplete Endpoint (for Gatepass/Challan forms)
```php
// GET /dash/autocomplete/gr?q=AA-001
public function autocomplete(Request $request)
{
    $q = $request->get('q', '');
    $grs = $this->officeScope(GR::query())
        ->where('gr_no', 'like', "%{$q}%")
        ->where('status', 'created') // only un-dispatched GRs
        ->select('id','gr_no','consignor','consignee','from_dest','to_dest','total_amount')
        ->limit(10)
        ->get();

    return response()->json($grs);
}
```

---

## 5. GATEPASS LOGIC

### Purpose
Authorizes goods to exit the origin branch. A Gatepass **links to one or more GRs** and automatically sets those GRs to `dispatched`.

### Gatepass Number Generation
```php
// Format: GP-YYYYMMDD-001 (resets daily per branch)
public function generateGatepassNo(string $office): string
{
    $today  = now()->format('Ymd');
    $prefix = "GP-{$today}";

    $last = Gatepass::where('office', $office)
                    ->where('gatepass_no', 'like', "{$prefix}-%")
                    ->orderByDesc('id')->first();

    $nextNum = $last ? ((int) substr($last->gatepass_no, -3)) + 1 : 1;
    return $prefix . '-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
}
```

### Gatepass store() Logic
```php
public function store(Request $request)
{
    $validated = $request->validate([
        'gatepass_date' => 'required|date',
        'vehicle_id'    => 'required|exists:vehicles,id',
        'driver_id'     => 'required|exists:truckdrivers,id',
        'from_station'  => 'required|string',
        'to_station'    => 'required|string',
        'gr_ids'        => 'required|array|min:1',
        'gr_ids.*'      => 'exists:grs,id',
        'remarks'       => 'nullable|string|max:500',
    ]);

    // Validate all selected GRs belong to current office and are in 'created' status
    $grs = GR::whereIn('id', $validated['gr_ids'])
              ->where('office', $this->currentOffice())
              ->where('status', 'created')
              ->get();

    if ($grs->count() !== count($validated['gr_ids'])) {
        return back()->withErrors(['gr_ids' => 'One or more selected GRs are invalid or already dispatched.']);
    }

    // Create gatepass
    $gatepass = Gatepass::create([
        'gatepass_no'   => $this->generateGatepassNo($this->currentOffice()),
        'gatepass_date' => $validated['gatepass_date'],
        'vehicle_id'    => $validated['vehicle_id'],
        'driver_id'     => $validated['driver_id'],
        'from_station'  => $validated['from_station'],
        'to_station'    => $validated['to_station'],
        'remarks'       => $validated['remarks'],
        'office'        => $this->currentOffice(),
    ]);

    // Link GRs via pivot table and auto-transition each to 'dispatched'
    $gatepass->grs()->attach($validated['gr_ids']);
    $workflow = app(GrWorkflowService::class);
    foreach ($grs as $gr) {
        $workflow->transition($gr, 'dispatched', auth()->user());
    }

    return redirect()->route('gatepass.index')
        ->with('success', "Gatepass {$gatepass->gatepass_no} created for " . $grs->count() . " GR(s).");
}
```

### Gatepass Delete Rules
```php
public function destroy(Gatepass $gatepass)
{
    if (!auth()->user()->hasAnyRole(['SuperAdmin','Admin'])) abort(403);

    // Reverse GR status back to 'created' when gatepass is deleted
    foreach ($gatepass->grs as $gr) {
        if ($gr->status === 'dispatched') {
            $gr->update(['status' => 'created']);
        }
    }

    $gatepass->grs()->detach();
    $gatepass->delete();

    return redirect()->route('gatepass.index')->with('success', 'Gatepass deleted. GR(s) reverted to created.');
}
```

### Gatepass Database Tables
```sql
-- gatepasses table
id, gatepass_no (unique), gatepass_date, vehicle_id (FK), driver_id (FK),
from_station, to_station, remarks, office, created_at, updated_at

-- gatepass_gr pivot table
id, gatepass_id (FK), gr_id (FK)
UNIQUE KEY (gatepass_id, gr_id)
```

---

## 6. CHALLAN + CHALLAN ITEMS LOGIC

### Purpose
The Delivery Challan travels with the goods in the truck. It lists all GR items in a single dispatch batch. A Challan can reference multiple GRs. It's different from a Gatepass — a Gatepass authorizes departure; a Challan is the goods manifest.

### Challan Number Generation
```php
// Format: CH-00001 (sequential per branch, 5-digit zero-padded)
public function generateChallanNo(string $office): string
{
    $last = Challan::where('office', $office)->orderByDesc('id')->first();
    $next = $last ? ((int) substr($last->challan_no, 3)) + 1 : 1;
    return 'CH-' . str_pad($next, 5, '0', STR_PAD_LEFT);
}
```

### Challan store() — Header + Items in one transaction
```php
public function store(Request $request)
{
    $request->validate([
        'challan_date'  => 'required|date',
        'vehicle_id'    => 'required|exists:vehicles,id',
        'driver_id'     => 'required|exists:truckdrivers,id',
        'from_station'  => 'required|string|max:100',
        'to_station'    => 'required|string|max:100',
        // Items array
        'items'                 => 'required|array|min:1',
        'items.*.gr_no'         => 'required|string|exists:grs,gr_no',
        'items.*.description'   => 'required|string|max:300',
        'items.*.nuggets'       => 'required|integer|min:1',
        'items.*.weight'        => 'required|numeric|min:0.01',
        'items.*.remarks'       => 'nullable|string|max:200',
    ]);

    DB::transaction(function () use ($request) {
        $challan = Challan::create([
            'challan_no'   => $this->generateChallanNo($this->currentOffice()),
            'challan_date' => $request->challan_date,
            'vehicle_id'   => $request->vehicle_id,
            'driver_id'    => $request->driver_id,
            'from_station' => $request->from_station,
            'to_station'   => $request->to_station,
            'total_items'  => count($request->items),
            'total_weight' => collect($request->items)->sum('weight'),
            'office'       => $this->currentOffice(),
        ]);

        foreach ($request->items as $item) {
            $challan->items()->create([
                'gr_no'       => $item['gr_no'],
                'description' => $item['description'],
                'nuggets'     => $item['nuggets'],
                'weight'      => $item['weight'],
                'remarks'     => $item['remarks'] ?? null,
            ]);
        }
    });

    return redirect()->route('challan.index')->with('success', 'Challan created successfully.');
}
```

### Challan Edit — Add/Remove Items
```php
public function update(Request $request, Challan $challan)
{
    // Validate same as store but also validate ownership
    if (!$this->isSuperAdmin() && $challan->office !== $this->currentOffice()) abort(403);

    $request->validate([
        'challan_date' => 'required|date',
        'vehicle_id'   => 'required|exists:vehicles,id',
        'driver_id'    => 'required|exists:truckdrivers,id',
        'from_station' => 'required|string',
        'to_station'   => 'required|string',
        'items'        => 'required|array|min:1',
        'items.*.id'          => 'nullable|exists:challan_items,id',
        'items.*.gr_no'       => 'required|string',
        'items.*.description' => 'required|string',
        'items.*.nuggets'     => 'required|integer|min:1',
        'items.*.weight'      => 'required|numeric|min:0.01',
        'items.*.remarks'     => 'nullable|string',
    ]);

    DB::transaction(function () use ($request, $challan) {
        // Update header
        $challan->update([
            'challan_date' => $request->challan_date,
            'vehicle_id'   => $request->vehicle_id,
            'driver_id'    => $request->driver_id,
            'from_station' => $request->from_station,
            'to_station'   => $request->to_station,
        ]);

        // Collect submitted item IDs (existing ones)
        $submittedIds = collect($request->items)
            ->pluck('id')
            ->filter()
            ->toArray();

        // Delete removed items (not in submitted list)
        $challan->items()->whereNotIn('id', $submittedIds)->delete();

        // Update existing / create new items
        foreach ($request->items as $itemData) {
            if (!empty($itemData['id'])) {
                ChallanItem::where('id', $itemData['id'])
                           ->where('challan_id', $challan->id)
                           ->update($itemData);
            } else {
                $challan->items()->create($itemData);
            }
        }

        // Recalculate totals
        $challan->update([
            'total_items'  => $challan->items()->count(),
            'total_weight' => $challan->items()->sum('weight'),
        ]);
    });

    return redirect()->route('challan.index')->with('success', 'Challan updated.');
}
```

### Delete Single Challan Item
```php
// DELETE /dash/challan-item/{id}
public function destroyItem(int $id)
{
    // Manager+ can delete individual items
    if (!auth()->user()->hasAnyRole(['SuperAdmin','Admin','Manager'])) abort(403);

    $item    = ChallanItem::findOrFail($id);
    $challan = $item->challan;

    // Office check
    if (!$this->isSuperAdmin() && $challan->office !== $this->currentOffice()) abort(403);

    // Cannot remove last item
    if ($challan->items()->count() <= 1) {
        return response()->json(['error' => 'Challan must have at least one item.'], 422);
    }

    $item->delete();

    // Recalculate totals
    $challan->update([
        'total_items'  => $challan->items()->count(),
        'total_weight' => $challan->items()->sum('weight'),
    ]);

    return response()->json(['success' => true, 'message' => 'Item removed.']);
}
```

### Delete Whole Challan
```php
public function destroy(Challan $challan)
{
    if (!auth()->user()->hasAnyRole(['SuperAdmin','Admin'])) abort(403);
    if (!$this->isSuperAdmin() && $challan->office !== $this->currentOffice()) abort(403);

    DB::transaction(function () use ($challan) {
        $challan->items()->delete(); // delete all items first
        $challan->delete();
    });

    return redirect()->route('challan.index')->with('success', 'Challan deleted.');
}
```

### Challan Database Tables
```sql
-- challans table
id, challan_no (unique), challan_date, vehicle_id (FK), driver_id (FK),
from_station, to_station, total_items (int), total_weight (decimal 10,2),
office, created_at, updated_at

-- challan_items table
id, challan_id (FK → challans.id ON DELETE CASCADE),
gr_no (varchar 20), description (text),
nuggets (int), weight (decimal 10,2),
remarks (text nullable),
created_at, updated_at
```

### Challan Print Layout
```
SXPRESS LOGISTICS — DELIVERY CHALLAN
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Challan No : CH-00001        Date: 01/06/2026
Vehicle    : GJ-03-AB-1234   Driver: Ramesh Patel
From       : Rajkot          To: Surat
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Sr | GR No    | Description    | Pkgs | Weight  | Remarks
 1 | AA-00001 | Electronics    |    5 | 50.00kg |
 2 | AA-00002 | Garments       |   10 | 80.00kg |
 3 | CG-00015 | Auto Parts     |    3 | 120.00kg|
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Total Packages: 18    Total Weight: 250.00 kg
Authorized Signature: _______________
```

---

## 7. FREIGHT MEMO LOGIC

### Purpose
The final billing document. Created after POD is received. Consolidates freight charges. Creation auto-closes the linked GR (status → `closed`). Only Manager+ can create.

### Freight Memo Number Generation
```php
// Format: FM-00001 (sequential per branch)
public function generateMemoNo(string $office): string
{
    $last = Freight::where('office', $office)->orderByDesc('id')->first();
    $next = $last ? ((int) substr($last->memo_no, 3)) + 1 : 1;
    return 'FM-' . str_pad($next, 5, '0', STR_PAD_LEFT);
}
```

### Freight Memo store() Logic
```php
public function store(Request $request)
{
    // Only Manager+ can create
    if (!auth()->user()->hasAnyRole(['SuperAdmin','Admin','Manager'])) abort(403);

    $validated = $request->validate([
        'memo_date'      => 'required|date',
        'gr_no'          => 'required|string|exists:grs,gr_no',
        'consignor'      => 'required|string|max:200',
        'consignee'      => 'required|string|max:200',
        'freight_amount' => 'required|numeric|min:0',
        'other_charges'  => 'nullable|numeric|min:0',
        'payment_type'   => 'required|in:paid,to_pay',
        'payment_status' => 'required|in:pending,collected',
        'remarks'        => 'nullable|string|max:500',
    ]);

    // Fetch the linked GR
    $gr = GR::where('gr_no', $validated['gr_no'])->firstOrFail();

    // GR must belong to same office (unless SuperAdmin)
    if (!$this->isSuperAdmin() && $gr->office !== $this->currentOffice()) {
        abort(403, 'This GR belongs to a different office.');
    }

    // GR must be in 'delivered' status to create a freight memo
    if (!in_array($gr->status, ['delivered', 'in_transit', 'dispatched'])) {
        return back()->withErrors(['gr_no' => "GR is in '{$gr->status}' status. Freight memo can only be created for delivered/in-transit GRs."]);
    }

    // Cannot create duplicate memo for same GR
    if (Freight::where('gr_no', $validated['gr_no'])->exists()) {
        return back()->withErrors(['gr_no' => 'A Freight Memo already exists for this GR.']);
    }

    // Recalculate total
    $validated['total']   = floatval($validated['freight_amount']) + floatval($validated['other_charges'] ?? 0);
    $validated['memo_no'] = $this->generateMemoNo($this->currentOffice());
    $validated['office']  = $this->currentOffice();

    DB::transaction(function () use ($validated, $gr) {
        $memo = Freight::create($validated);

        // Auto-transition GR to 'closed' (only SuperAdmin can do this)
        // If current user is SuperAdmin, close immediately
        // If not, mark as 'delivered' and let SuperAdmin close
        if ($this->isSuperAdmin()) {
            app(GrWorkflowService::class)->transition($gr, 'closed', auth()->user());
        }
    });

    return redirect()->route('frieghtmemo.index')
        ->with('success', "Freight Memo {$validated['memo_no']} created.");
}
```

### Freight Memo Edit Rules
```php
public function edit(Freight $freight)
{
    // Only Admin+ can edit
    if (!auth()->user()->hasAnyRole(['SuperAdmin','Admin'])) abort(403);
    if (!$this->isSuperAdmin() && $freight->office !== $this->currentOffice()) abort(403);

    return view('admin.category.frieghtmemo_edit', compact('freight'));
}
```

### Freight Memo Print Layout
```
SXPRESS LOGISTICS — FREIGHT MEMO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Memo No    : FM-00001         Date: 05/06/2026
GR No      : AA-00001
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Consignor  : Tata Motors Ltd
Consignee  : Auto Parts Depot, Surat
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Freight Amount : ₹ 1,200.00
Other Charges  : ₹    150.00
               ─────────────
TOTAL          : ₹ 1,350.00
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Payment Type   : TO-PAY
Payment Status : COLLECTED
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Remarks: Delivered on time. POD received.
```

### frieghts Table (NOTE: table name has a typo — NEVER rename it)
```sql
-- Model: Freight.php → protected $table = 'frieghts';
id, memo_no (unique), memo_date, gr_no (varchar),
consignor (varchar 200), consignee (varchar 200),
freight_amount (decimal 10,2), other_charges (decimal 10,2),
total (decimal 10,2),
payment_type ENUM('paid','to_pay'),
payment_status ENUM('pending','collected') DEFAULT 'pending',
remarks (text nullable),
office (varchar 100),
created_at, updated_at
```

---

## 8. POD (PROOF OF DELIVERY) LOGIC

### Purpose
POD is a signed receipt from the consignee confirming goods were delivered. Uploading a POD automatically sets GR status to `delivered`.

### Upload Logic
```php
// POST /dash/gr/{gr}/pod
public function uploadPod(Request $request, GR $gr)
{
    // Office check
    if (!$this->isSuperAdmin() && $gr->office !== $this->currentOffice()) abort(403);

    $request->validate([
        'pod_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        'pod_date' => 'required|date|before_or_equal:today',
        'pod_note' => 'nullable|string|max:300',
    ]);

    // GR must be dispatched or in_transit to upload POD
    if (!in_array($gr->status, ['dispatched','in_transit'])) {
        return back()->withErrors(['pod_file' => "Cannot upload POD — GR is '{$gr->status}'."]);
    }

    // Store file
    $path = $request->file('pod_file')->store('pods', 'public');
    // Full public URL: Storage::url($path)

    $gr->update([
        'pod_file'        => $path,
        'pod_date'        => $request->pod_date,
        'pod_note'        => $request->pod_note,
        'pod_uploaded_by' => auth()->id(),
    ]);

    // Auto-transition: in_transit → delivered
    if ($gr->status === 'in_transit') {
        app(GrWorkflowService::class)->transition($gr, 'delivered', auth()->user());
    } elseif ($gr->status === 'dispatched') {
        // Skip in_transit, go straight to delivered
        $gr->update(['status' => 'delivered', 'status_updated_at' => now(), 'status_updated_by' => auth()->id()]);
    }

    return back()->with('success', 'POD uploaded. GR marked as Delivered.');
}
```

### POD Fields on grs Table
```sql
pod_file        VARCHAR(500) NULLABLE  -- storage path
pod_date        DATE NULLABLE
pod_note        TEXT NULLABLE
pod_uploaded_by BIGINT NULLABLE (FK → users.id)
```

---

## 9. CONSIGNOR & CONSIGNEE LOGIC

### Purpose
Consignors and Consignees are master records. On the GR form, typing a name auto-fills address and GSTIN via AJAX. They can also be created on-the-fly from the GR form.

### Autocomplete Endpoint
```php
// GET /dash/autocomplete/consignor?q=tata
public function autocomplete(Request $request)
{
    $q = $request->get('q', '');
    if (strlen($q) < 2) return response()->json([]);

    $results = Consignor::where('name', 'like', "%{$q}%")
        ->select('id','name','address','city','gst_no','phone')
        ->orderBy('name')
        ->limit(10)
        ->get();

    return response()->json($results);
}

// Same pattern for ConsigneeController
```

### Frontend AJAX Wiring (in gr.blade.php)
```javascript
// Auto-fill consignor address + GST when name selected
$('#consignor').on('input', debounce(function() {
    const q = $(this).val();
    if (q.length < 2) return;

    $.get('/dash/autocomplete/consignor', { q }, function(data) {
        const list = $('#consignor-suggestions').empty();
        data.forEach(item => {
            list.append(`<li class="list-group-item list-group-item-action"
                data-id="${item.id}"
                data-address="${item.address}"
                data-city="${item.city}"
                data-gst="${item.gst_no}"
            >${item.name} — ${item.city}</li>`);
        });
    });
}, 300));

$(document).on('click', '#consignor-suggestions li', function() {
    const $el = $(this);
    $('#consignor').val($el.text().split('—')[0].trim());
    $('#consignor_address').val($el.data('address') + ', ' + $el.data('city'));
    $('#consignor_gst_no').val($el.data('gst'));
    $('#consignor-suggestions').empty();
});

// Same for consignee fields
```

### Consignor/Consignee Validation
```php
// ConsignorController & ConsigneeController
public function store(Request $request)
{
    $validated = $request->validate([
        'name'    => 'required|string|max:200',
        'address' => 'required|string|max:500',
        'city'    => 'required|string|max:100',
        'state'   => 'required|string|max:100',
        'gst_no'  => ['nullable','string','size:15', new GstNumberRule()],
        'phone'   => 'required|string|max:15|regex:/^[6-9]\d{9}$/', // Indian mobile
        'email'   => 'nullable|email|max:100',
    ]);

    Consignor::create($validated);
    return redirect()->route('consignor.index')->with('success', 'Consignor added.');
}
```

---

## 10. VEHICLE & DRIVER LOGIC

### Access: Admin + SuperAdmin only

### Vehicle Validation
```php
public function store(Request $request)
{
    if (!auth()->user()->hasAnyRole(['SuperAdmin','Admin'])) abort(403);

    $request->validate([
        'vehicle_no'   => 'required|string|max:20|unique:vehicles,vehicle_no|regex:/^[A-Z]{2}[\-\s]?\d{2}[\-\s]?[A-Z]{1,2}[\-\s]?\d{4}$/',
        'vehicle_type' => 'required|in:Truck,Tempo,Container,Mini Truck,Trailer,Other',
        'capacity'     => 'required|numeric|min:0.5|max:100', // in tons
        'owner_name'   => 'required|string|max:100',
        'status'       => 'required|in:active,inactive',
    ]);
    // vehicle_no format: GJ-03-AB-1234
}
```

### Driver Validation
```php
$request->validate([
    'name'       => 'required|string|max:100',
    'license_no' => 'required|string|max:20|unique:truckdrivers,license_no',
    'phone'      => 'required|string|regex:/^[6-9]\d{9}$/',
    'address'    => 'required|string|max:300',
    'status'     => 'required|in:active,inactive',
]);
```

### Business Rules
- Only `active` vehicles/drivers should appear in Gatepass and Challan dropdowns
- When a vehicle/driver is set to `inactive`, validate no pending dispatch exists

```php
// In VehicleController@update — check before deactivating
if ($request->status === 'inactive') {
    $activeGatepasses = Gatepass::where('vehicle_id', $vehicle->id)
        ->whereHas('grs', fn($q) => $q->whereIn('status', ['dispatched','in_transit']))
        ->exists();
    if ($activeGatepasses) {
        return back()->withErrors(['status' => 'Cannot deactivate — vehicle has active dispatches.']);
    }
}
```

---

## 11. ROUTE & STATION LOGIC

### Access: Admin + SuperAdmin only

### Route Validation
```php
$request->validate([
    'from_station' => 'required|string|exists:stations,name',
    'to_station'   => 'required|string|exists:stations,name|different:from_station',
    'distance_km'  => 'required|integer|min:1',
    'rate_per_kg'  => 'required|numeric|min:0',
]);
// Prevent duplicate routes
$exists = Route::where('from_station', $request->from_station)
               ->where('to_station', $request->to_station)->exists();
if ($exists) return back()->withErrors(['from_station' => 'This route already exists.']);
```

### Station Validation
```php
$request->validate([
    'name'    => 'required|string|max:100|unique:stations,name',
    'state'   => 'required|string|max:100',
    'pincode' => 'required|string|digits:6',
]);
```

### Auto-fill Freight Rate on GR Form
When user selects `from_dest` and `to_dest` on GR form, auto-fetch the standard rate:
```javascript
// GR form: on to_dest change
$('#from_dest, #to_dest').on('change', function() {
    const from = $('#from_dest').val();
    const to   = $('#to_dest').val();
    if (!from || !to) return;

    $.get('/dash/route-rate', { from, to }, function(data) {
        if (data.rate_per_kg) {
            // Suggest rate based on weight
            const weight = parseFloat($('#weight').val()) || 0;
            const suggestedFreight = (weight * data.rate_per_kg).toFixed(2);
            $('#freight_amount').val(suggestedFreight);
            recalcTotal();
        }
    });
});
```

---

## 12. SUPERADMIN EXCLUSIVE ACTIONS

### Branch / Office Management (SuperAdmin ONLY)

```php
// BranchController — constructor locks entire controller
class BranchController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:SuperAdmin']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:100|unique:branches,name',
            'gr_prefix'    => 'required|string|size:2|regex:/^[A-Z]{2}$/|unique:branches,gr_prefix',
            'address'      => 'required|string|max:300',
            'city'         => 'required|string|max:100',
            'phone'        => 'required|string|max:20',
            'manager_name' => 'nullable|string|max:100',
        ]);

        Branch::create($request->validated());
        return redirect()->route('branch.index')->with('success', 'Office created.');
    }

    public function destroy(Branch $branch)
    {
        // Block deletion if branch has GR records
        if (GR::where('office', $branch->name)->exists()) {
            return back()->withErrors(['Cannot delete — this office has existing GR records.']);
        }
        // Block if users are assigned
        if (User::where('office', $branch->name)->exists()) {
            return back()->withErrors(['Cannot delete — users are assigned to this office. Reassign them first.']);
        }
        $branch->delete();
        return redirect()->route('branch.index')->with('success', 'Office deleted.');
    }
}
```

### SuperAdmin Dashboard — Cross-Branch Overview
```php
// DashboardController — SuperAdmin view
if (auth()->user()->hasRole('SuperAdmin')) {
    $summary = Branch::withCount([
        'grs',
        'grs as grs_today_count' => fn($q) => $q->whereDate('copy_date', today()),
        'grs as pending_topay_count' => fn($q) => $q->where('to_pay',1)->where('topay_collected',0),
    ])->get();

    return view('admin.superadmin.dashboard', compact('summary'));
}
```

---

## 13. SERIAL NUMBER ASSIGNMENT BY SUPERADMIN

### What It Does
Before a branch starts creating GRs, SuperAdmin can set the starting sequence number. E.g., Navagam starts from 5000 → first GR = NV-05000.

### Table: `branch_serials`
```sql
id, office (varchar unique), gr_prefix (varchar 5),
start_from (int unsigned default 1),
assigned_by (FK → users.id), assigned_at (timestamp),
notes (text nullable), created_at, updated_at
```

### SerialController
```php
class SerialController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:SuperAdmin']);
    }

    public function index()
    {
        // Show all branches with their serial config and GR count
        $branches = Branch::with('serial')
            ->withCount('grs')
            ->get()
            ->map(function($branch) {
                $branch->is_locked = $branch->grs_count > 0;
                return $branch;
            });

        return view('admin.superadmin.serial_assign', compact('branches'));
    }

    public function assign(Request $request)
    {
        $validated = $request->validate([
            'office'     => 'required|string|exists:branches,name',
            'start_from' => 'required|integer|min:1|max:99999',
            'notes'      => 'nullable|string|max:500',
        ]);

        // Hard lock — cannot change once GRs exist
        if (GR::where('office', $validated['office'])->exists()) {
            return back()->withErrors([
                'start_from' => 'Serial is locked — this office already has GR records.'
            ]);
        }

        $branch = Branch::where('name', $validated['office'])->first();

        BranchSerial::updateOrCreate(
            ['office' => $validated['office']],
            [
                'gr_prefix'   => $branch->gr_prefix,
                'start_from'  => $validated['start_from'],
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
                'notes'       => $validated['notes'],
            ]
        );

        return redirect()->route('serial.index')
            ->with('success', "Serial for {$validated['office']} set. First GR will be: {$branch->gr_prefix}-" . str_pad($validated['start_from'], 5, '0', STR_PAD_LEFT));
    }
}
```

### Serial Assignment UI Layout
```
┌──────────────────────────────────────────────────────────────────┐
│  GR SERIAL NUMBER ASSIGNMENT  (SuperAdmin Only)                  │
├─────────────────┬────────┬──────────────┬───────────┬───────────┤
│  Office         │ Prefix │  Start From  │ GRs Exist │  Action   │
├─────────────────┼────────┼──────────────┼───────────┼───────────┤
│  Rajkot         │  AA    │  00001       │ YES ✅    │  LOCKED 🔒│
│  Kashmore Gate  │  CG    │  00001       │ NO        │  [Edit ✏️]│
│  Navagam        │  NV    │  05000       │ NO        │  [Edit ✏️]│
│  Dayabasti      │  DB    │  not set     │ NO        │  [Set  ✏️]│
├─────────────────┴────────┴──────────────┴───────────┴───────────┤
│  ⚠️ Once the first GR is created for a branch, serial is locked. │
└──────────────────────────────────────────────────────────────────┘
```

---

## 14. USER MANAGEMENT LOGIC

### Who can create users and what roles they can assign

```php
public function store(Request $request)
{
    $creator = auth()->user();

    // Only Admin+ can create users
    if (!$creator->hasAnyRole(['SuperAdmin','Admin'])) abort(403);

    $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
        'office'   => 'required|string|exists:branches,name',
        'role'     => 'required|string|exists:roles,name',
        'is_active'=> 'boolean',
    ]);

    // Admin can only create for own branch
    if ($creator->hasRole('Admin') && $request->office !== $creator->office) {
        abort(403, 'You can only create users for your own office.');
    }

    // Admin cannot assign SuperAdmin or Admin role
    $restrictedRoles = ['SuperAdmin', 'Admin'];
    if ($creator->hasRole('Admin') && in_array($request->role, $restrictedRoles)) {
        abort(403, 'You cannot assign this role. Contact SuperAdmin.');
    }

    $user = User::create([
        'name'      => $request->name,
        'email'     => $request->email,
        'password'  => bcrypt($request->password),
        'office'    => $request->office,
        'is_active' => $request->is_active ?? true,
    ]);

    $user->assignRole($request->role);

    return redirect()->route('users.index')->with('success', 'User created.');
}
```

### Deactivate/Reactivate User
```php
public function toggleActive(User $user)
{
    if (!auth()->user()->hasAnyRole(['SuperAdmin','Admin'])) abort(403);

    // Admin can only deactivate users of own branch
    if (auth()->user()->hasRole('Admin') && $user->office !== auth()->user()->office) abort(403);

    // Cannot deactivate yourself
    if ($user->id === auth()->id()) {
        return back()->withErrors(['Cannot deactivate your own account.']);
    }

    $user->update(['is_active' => !$user->is_active]);
    $status = $user->is_active ? 'activated' : 'deactivated';

    return back()->with('success', "User {$user->name} has been {$status}.");
}
```

### Login Guard — block inactive users
```php
// In AuthServiceProvider or LoginController
protected function authenticated(Request $request, $user)
{
    if (!$user->is_active) {
        auth()->logout();
        return redirect('/login')->withErrors(['Your account has been deactivated. Contact your admin.']);
    }
}
```

---

## 15. FINANCIAL LOGIC

### Total Amount = Always Recalculated Server-Side

```php
// NEVER trust $request->total_amount — always recompute
private function computeTotal(array $data): float
{
    return round(
        floatval($data['freight_amount'] ?? 0) +
        floatval($data['sur_ch']         ?? 0) +
        floatval($data['c_r']            ?? 0) +
        floatval($data['other']          ?? 0) +
        floatval($data['bc_amount']      ?? 0),
        2
    );
}
```

### Frontend Live Calculation (UX helper — not trusted by server)
```javascript
function recalcTotal() {
    const fields = ['freight_amount','sur_ch','c_r','other','bc_amount'];
    const total  = fields.reduce((sum, id) => sum + (parseFloat(document.getElementById(id)?.value) || 0), 0);
    const el = document.getElementById('total_amount');
    if (el) el.value = total.toFixed(2);
}
document.querySelectorAll('#freight_amount,#sur_ch,#c_r,#other,#bc_amount')
        .forEach(el => el.addEventListener('input', recalcTotal));
```

### TO-PAY Collection
```php
public function markTopayCollected(GR $gr)
{
    if (!$this->isSuperAdmin() && $gr->office !== $this->currentOffice()) abort(403);

    if (!$gr->to_pay) {
        return back()->withErrors(['This GR is Paid — not To-Pay.']);
    }
    if ($gr->topay_collected) {
        return back()->withErrors(['Freight already marked as collected on ' . $gr->topay_collected_date . '.']);
    }

    $gr->update([
        'topay_collected'      => true,
        'topay_collected_date' => now()->toDateString(),
        'topay_collected_by'   => auth()->id(),
    ]);

    return back()->with('success', 'Freight collection of ₹' . number_format($gr->total_amount, 2) . ' recorded.');
}
```

### Dashboard Financial KPIs
```php
$office = $this->currentOffice();
$scope  = fn($q) => $this->isSuperAdmin() ? $q : $q->where('office', $office);

$kpis = [
    'grs_today'        => $scope(GR::whereDate('copy_date', today()))->count(),
    'grs_this_month'   => $scope(GR::whereMonth('copy_date', now()->month))->count(),
    'pending_topay'    => $scope(GR::where('to_pay', 1)->where('topay_collected', 0))->sum('total_amount'),
    'collected_today'  => $scope(GR::where('topay_collected', 1)->whereDate('topay_collected_date', today()))->sum('total_amount'),
    'active_vehicles'  => Vehicle::where('status', 'active')->count(),
    'in_transit_count' => $scope(GR::where('status', 'in_transit'))->count(),
];
```

---

## 16. VALIDATION RULES REFERENCE

### GR Full Validation
```php
private function grRules(): array
{
    return [
        'copy_date'         => 'required|date|before_or_equal:today',
        'from_dest'         => 'required|string|max:100',
        'to_dest'           => 'required|string|max:100|different:from_dest',
        'consignor'         => 'required|string|max:200',
        'consignor_address' => 'required|string|max:500',
        'consignor_gst_no'  => ['nullable','string','size:15', new GstNumberRule()],
        'consignee'         => 'required|string|max:200',
        'consignee_address' => 'required|string|max:500',
        'consignee_gst_no'  => ['nullable','string','size:15', new GstNumberRule()],
        'nuggets'           => 'required|integer|min:1',
        'meth'              => 'required|string|in:Bag,Box,Bundle,Drum,Roll,Carton,Loose,Other',
        'weight'            => 'required|numeric|min:0.01',
        'description'       => 'required|string|max:500',
        'pm'                => 'nullable|string|max:50',
        'eway_bill_number'  => 'nullable|string|digits:12',
        'bill_amount'       => 'nullable|numeric|min:0',
        'freight_amount'    => 'required|numeric|min:0',
        'sur_ch'            => 'nullable|numeric|min:0',
        'c_r'               => 'nullable|numeric|min:0',
        'other'             => 'nullable|numeric|min:0',
        'bc_amount'         => 'nullable|numeric|min:0',
        'paid'              => 'boolean',
        'to_pay'            => 'boolean',
    ];
}
```

### GST Number Rule
```php
// app/Rules/GstNumberRule.php
class GstNumberRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        return (bool) preg_match(
            '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
            strtoupper($value)
        );
    }
    public function message(): string
    {
        return 'The :attribute must be a valid 15-character Indian GSTIN (e.g. 24AABCU9603R1ZX).';
    }
}
```

---

## 17. CONTROLLER PATTERNS

### Standard Index with All Filters
```php
public function index(Request $request)
{
    $query = GR::query(); // replace GR:: with relevant model

    // Office scope (SuperAdmin sees all)
    $this->officeScope($query);

    // Search
    if ($s = $request->search) {
        $query->where(fn($q) => $q
            ->where('gr_no','like',"%{$s}%")
            ->orWhere('consignor','like',"%{$s}%")
            ->orWhere('consignee','like',"%{$s}%")
        );
    }

    // Status filter
    if ($status = $request->status) $query->where('status', $status);

    // Date range
    if ($from = $request->from_date) $query->whereDate('copy_date', '>=', $from);
    if ($to   = $request->to_date)   $query->whereDate('copy_date', '<=', $to);

    // Paid/ToPay filter
    if ($request->payment === 'paid')   $query->where('paid', 1);
    if ($request->payment === 'to_pay') $query->where('to_pay', 1);

    // Branch filter (SuperAdmin only)
    if ($this->isSuperAdmin() && $branch = $request->branch) {
        $query->where('office', $branch);
    }

    $items    = $query->latest()->paginate(25)->withQueryString();
    $branches = $this->isSuperAdmin() ? Branch::pluck('name') : collect();

    return view('admin.category.gr_list', compact('items','branches'));
}
```

---

## 18. COMMON MISTAKES TO AVOID

| ❌ Wrong | ✅ Correct |
|----------|-----------|
| `GR::all()` | `$this->officeScope(GR::query())->get()` |
| Trust `$request->total_amount` | Recalculate: `$this->computeTotal($data)` |
| Hardcode GR prefixes in controller | Load from `branches.gr_prefix` |
| Any role creates branches | `middleware('role:SuperAdmin')` on BranchController |
| Admin assigns Admin/SuperAdmin role | Check in UserController before `assignRole()` |
| `Freight` model without `$table` | Add `protected $table = 'frieghts';` |
| Delete Challan without deleting items | Use `DB::transaction` + `$challan->items()->delete()` |
| Create Freight Memo for non-delivered GR | Check `$gr->status` before creating memo |
| Staff editing dispatched GR | Check `$gr->status !== 'created'` in edit() |
| POD upload without status check | Validate `$gr->status` is dispatched/in_transit |
| Challan item delete leaving 0 items | Check `count > 1` before deleting last item |
| Active vehicle shows inactive drivers | Filter `status = active` in dropdowns |
| Duplicate route creation | Check unique `from_station + to_station` combo |
| SuperAdmin sees only own branch | Skip office filter when `hasRole('SuperAdmin')` |
| GR deletion when linked to Gatepass | Check `$gr->gatepasses()->exists()` first |

---

## APPENDIX A — ALL REQUIRED MIGRATIONS

```bash
# Run all these in order:
php artisan make:migration add_gr_prefix_and_is_active_to_branches_table
php artisan make:migration create_branch_serials_table
php artisan make:migration add_status_fields_to_grs_table
php artisan make:migration add_topay_tracking_to_grs_table
php artisan make:migration add_pod_fields_to_grs_table
php artisan make:migration add_is_active_to_users_table
php artisan make:migration create_gatepass_gr_pivot_table
php artisan make:migration add_vehicle_driver_fk_to_gatepasses_table
php artisan make:migration add_vehicle_driver_fk_to_challans_table
php artisan make:migration add_total_weight_to_challans_table
```

### Migration Content Quick Reference

```php
// branches: add gr_prefix + is_active
$table->string('gr_prefix', 5)->unique()->nullable()->after('name');
$table->boolean('is_active')->default(true)->after('gr_prefix');

// branch_serials (new table)
$table->id();
$table->string('office', 100)->unique();
$table->string('gr_prefix', 5);
$table->unsignedInteger('start_from')->default(1);
$table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('assigned_at')->nullable();
$table->text('notes')->nullable();
$table->timestamps();

// grs: status + audit fields
$table->enum('status', ['created','dispatched','in_transit','delivered','closed','cancelled'])->default('created')->after('office');
$table->timestamp('status_updated_at')->nullable()->after('status');
$table->foreignId('status_updated_by')->nullable()->constrained('users')->nullOnDelete();

// grs: topay tracking
$table->boolean('topay_collected')->default(false)->after('to_pay');
$table->date('topay_collected_date')->nullable();
$table->foreignId('topay_collected_by')->nullable()->constrained('users')->nullOnDelete();

// grs: pod fields
$table->string('pod_file', 500)->nullable();
$table->date('pod_date')->nullable();
$table->text('pod_note')->nullable();
$table->foreignId('pod_uploaded_by')->nullable()->constrained('users')->nullOnDelete();

// users: is_active
$table->boolean('is_active')->default(true)->after('office');

// gatepass_gr pivot table (new)
$table->id();
$table->foreignId('gatepass_id')->constrained('gatepasses')->cascadeOnDelete();
$table->foreignId('gr_id')->constrained('grs')->cascadeOnDelete();
$table->unique(['gatepass_id','gr_id']);
$table->timestamps();

// gatepasses: vehicle/driver FK
$table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
$table->foreignId('driver_id')->nullable()->constrained('truckdrivers')->nullOnDelete();

// challans: weight total + FK
$table->decimal('total_weight', 10, 2)->default(0)->after('total_items');
$table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
$table->foreignId('driver_id')->nullable()->constrained('truckdrivers')->nullOnDelete();
```

---

## APPENDIX B — SPATIE ROLES & PERMISSIONS SEEDER

```php
// database/seeders/RolePermissionSeeder.php
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['SuperAdmin','Admin','Manager','Staff','Viewer'];
        foreach ($roles as $r) Role::firstOrCreate(['name' => $r]);

        $permissions = [
            // Branch
            'create-branch','edit-branch','delete-branch','assign-gr-serial',
            // GR
            'create-gr','edit-gr','edit-gr-dispatched','delete-gr','print-gr','cancel-gr','close-gr',
            // Gatepass
            'create-gatepass','edit-gatepass','delete-gatepass','print-gatepass',
            // Challan
            'create-challan','edit-challan','delete-challan','delete-challan-item','print-challan',
            // Freight Memo
            'create-freight-memo','edit-freight-memo','delete-freight-memo','print-freight-memo',
            // POD & Collection
            'upload-pod','mark-topay-collected',
            // Masters
            'manage-consignors','manage-consignees','manage-customers',
            'manage-vehicles','manage-drivers','manage-routes','manage-stations',
            // Users
            'create-user','edit-user','deactivate-user',
            // Reports
            'view-reports','view-all-branch-reports','export-reports',
        ];
        foreach ($permissions as $p) Permission::firstOrCreate(['name' => $p]);

        Role::findByName('SuperAdmin')->syncPermissions(Permission::all());

        Role::findByName('Admin')->syncPermissions([
            'create-gr','edit-gr','edit-gr-dispatched','delete-gr','print-gr','cancel-gr',
            'create-gatepass','edit-gatepass','delete-gatepass','print-gatepass',
            'create-challan','edit-challan','delete-challan','delete-challan-item','print-challan',
            'create-freight-memo','edit-freight-memo','delete-freight-memo','print-freight-memo',
            'upload-pod','mark-topay-collected',
            'manage-consignors','manage-consignees','manage-customers',
            'manage-vehicles','manage-drivers','manage-routes','manage-stations',
            'create-user','edit-user','deactivate-user',
            'view-reports','export-reports',
        ]);

        Role::findByName('Manager')->syncPermissions([
            'create-gr','edit-gr','print-gr',
            'create-gatepass','edit-gatepass','print-gatepass',
            'create-challan','edit-challan','delete-challan-item','print-challan',
            'create-freight-memo','print-freight-memo',
            'upload-pod','mark-topay-collected',
            'manage-consignors','manage-consignees','manage-customers',
            'view-reports','export-reports',
        ]);

        Role::findByName('Staff')->syncPermissions([
            'create-gr','edit-gr','print-gr',
            'create-gatepass','print-gatepass',
            'create-challan','edit-challan','print-challan',
            'upload-pod','mark-topay-collected',
            'manage-consignors','manage-consignees','manage-customers',
        ]);

        Role::findByName('Viewer')->syncPermissions([
            'print-gr','print-gatepass','print-challan','print-freight-memo',
        ]);
    }
}
```

```bash
php artisan db:seed --class=RolePermissionSeeder
php artisan storage:link   # for POD file uploads
```

---

*This skill is the complete single source of truth for all SXpress business logic. Every feature must follow these rules exactly.*
