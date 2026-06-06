# Refactoring Plan — Saurashtra Express

> Target: extract services, repositories, action classes, DTOs, and events. Preserve behavior 1:1.
> Companion: `laravel-upgrade-plan.md`, `performance-report.md`, `security-audit.md`.

---

## 1. Current state (diagnostic)

| Layer | Present? | What it does today |
|---|---|---|
| Controllers | ✅ | All logic here (validates, generates IDs, inserts, redirects) |
| Models | ✅ (8) | Thin Eloquent Active Record, no relationships |
| Services | ❌ | none |
| Repositories | ❌ | none |
| Action classes | ❌ | none |
| DTOs | ❌ | none |
| Form Requests | ❌ | inline `$request->validate(...)` |
| Events | ❌ | none fired |
| Listeners | ❌ | none |
| Jobs / Queues | ❌ | `QUEUE_CONNECTION=sync` |
| Policies | ❌ | none registered |
| Observers | ❌ | none |
| Notifications | ❌ | none |
| Caching | ❌ | none observed |
| Resource classes (API) | ❌ | no API |

**Verdict:** Flat MVC. ~580 LOC in the biggest controller (`GrController`). All business rules in controllers. No separation of concerns.

---

## 2. Naming conventions (modernization)

### 2.1 Class naming (PSR-1)
| Old | New |
|---|---|
| `App\Models\gr` | `App\Models\GoodsReceipt` (table stays `grs`) |
| `App\Models\challan` | `App\Models\Challan` |
| `App\Models\gatepass` | `App\Models\Gatepass` |
| `App\Models\truckdriver` | `App\Models\TruckDriver` |
| `App\Models\Freight` | `App\Models\FreightMemo` (table renamed to `freight_memos` + view for `frieghts`) |
| `App\Models\ChallanItem` | `App\Models\ChallanLine` (table renamed to `challan_lines`) |
| `App\Models\Post` | (delete) |

### 2.2 File naming
Files should match the class (PSR-4). `app/Models/gatepass.php` → `app/Models/Gatepass.php`, etc.

### 2.3 Table naming (already correct)
`grs, gatepasses, challans, truckdrivers, frieghts → freight_memos, challan_iteams → challan_lines, users, posts (drop)`.

### 2.4 Route segment aliasing (backward compat)
Keep the legacy `frieghtmemo` URL working; add a redirect from `/dash/freight-memo` to `/dash/frieghtmemo` (or vice versa — pick one canonical).

---

## 3. Service layer (recommended extraction)

The following services should be created. Each is a single, testable class that does one thing.

### 3.1 `NumberSequenceService`
**Purpose:** Generate the next sequential business number (GR, Gatepass, Challan, Freight Memo) safely and atomically.

**Why:** The current `GrController::create()` has 4 copies of the same logic (one per office), `GatepassController::create()` resets at 1000, `ChallanController::create()` rolls over at 1001. All fragile.

**API:**
```php
class NumberSequenceService
{
    public function nextGrNumber(string $office): string;        // 'AA-00007'
    public function nextGatepassNumber(): int;                  // 42
    public function nextChallanNumber(): string;                // 'AA-0008'
    public function nextFreightMemoNumber(): string;            // 'FM-00007'
}
```

**Implementation:** backed by a `number_sequences` table with row locking (`SELECT ... FOR UPDATE` or `DB::transaction` + `lockForUpdate`). Or a Redis `INCR`.

**Where used:** every controller that currently does `Model::latest()->first()->xxx_no` and increments.

### 3.2 `GrCreationService` (Action class)
**Purpose:** encapsulate the "create a GR" workflow.

**Why:** `GrController::store` is 50 lines of mass-assignment with hand-tweaked `ucwords()` and `Str::ucfirst()`. The business rule "consignor's name is title-cased" should not be in the controller.

**API:**
```php
class CreateGrAction
{
    public function execute(CreateGrRequest $request): GoodsReceipt;
}
```

**Side effects:** fires `GrCreated` event; creates a `NumberSequence` reservation; logs an `audit_log` entry.

### 3.3 `GatepassCreationService`
**Purpose:** encapsulate the gatepass creation, pre-filling from the GR.

**API:**
```php
class CreateGatepassAction
{
    public function execute(string $grNo, CreateGatepassRequest $request): Gatepass;
}
```

### 3.4 `ChallanCreationService`
**Purpose:** encapsulate the AJAX-driven challan creation.

**API:**
```php
class CreateChallanAction
{
    public function execute(CreateChallanRequest $request): Challan;
}

class AddChallanLineAction
{
    public function execute(AddChallanLineRequest $request): ChallanLine;
}
```

### 3.5 `FreightMemoService` (entire module rebuild)
**Purpose:** Rebuild the freight memo module as a real workflow.

**API:**
```php
class CreateFreightMemoAction
{
    public function execute(CreateFreightMemoRequest $request): FreightMemo;
}

class RecordFreightPaymentAction
{
    public function execute(RecordFreightPaymentRequest $request): FreightPayment;
}
```

### 3.6 `ReportService`
**Purpose:** generate the missing reports (see `report-inventory.md §3`).

**API:**
```php
class GrRegisterReport
{
    public function forDateRange(\DateTime $from, \DateTime $to, ?int $branchId = null): Collection;
}

class BranchRevenueReport { ... }
class TruckUtilizationReport { ... }
class OutstandingFreightReport { ... }
```

### 3.7 `AuditService`
**Purpose:** central place to write `audit_logs` rows.

**API:**
```php
class AuditService
{
    public function log(string $action, Model $model, ?array $old = null, ?array $new = null): void;
}
```

Used by observers (see §6) or by services directly.

### 3.8 `ImportService` (future)
For bulk-importing old data via Excel. Out of scope for first pass.

---

## 4. Repository layer (recommended but optional)

> **Trade-off:** Eloquent is already a Repository pattern. Adding a custom Repository on top adds indirection without much benefit. **Recommend:** use Eloquent directly in services, and reserve Repository pattern for the few complex queries (reports, joins across 3+ tables).

### 4.1 Where to use a custom repository
- `BranchRepository` — list with `with('users')`, etc.
- `GrRepository` — the report queries (§3.6).
- `FreightMemoRepository` — the settlement queries.

### 4.2 Sample signature
```php
interface GrRepository
{
    public function paginateForOffice(int $branchId, int $perPage = 50): LengthAwarePaginator;
    public function findByGrNumber(string $grNo): ?GoodsReceipt;
    public function forDateRange(\DateTime $from, \DateTime $to, ?int $fromBranch = null, ?int $toBranch = null): Collection;
    public function pendingDeliveries(int $branchId): Collection;  // GRs without Gatepass
}
```

---

## 5. Form Request classes (validation)

Extract inline validation into dedicated Form Request classes. This:
- Removes 50+ lines of duplicated rules from controllers
- Centralizes the validation contract
- Auto-generates the 422 response with field errors

### 5.1 List
- `CreateGrRequest` — for `GrController@store`
- `UpdateGrRequest` — for `GrController@update`
- `CreateGatepassRequest` — for `GatepassController@store`
- `UpdateGatepassRequest` — for `GatepassController@update`
- `CreateChallanRequest` — for `ChallanController@store`
- `AddChallanLineRequest` — for `ChallanController@challanIteamStore`
- `CreateFreightMemoRequest`
- `RecordFreightPaymentRequest`
- `CreateTruckDriverRequest`
- `UpdateTruckDriverRequest`
- `CreateUserRequest` / `UpdateUserRequest`
- `CreateRoleRequest` / `UpdateRoleRequest`
- `CreatePermissionRequest` / `UpdatePermissionRequest`

### 5.2 Example
```php
class CreateGrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create gr');
    }

    public function rules(): array
    {
        return [
            'gr_no' => ['required', 'string', 'max:8', 'unique:grs,gr_no'],
            'from_dest' => ['required', 'string', 'max:50'],
            'to_dest' => ['required', 'string', 'max:50'],
            'copy_date' => ['required', 'date_format:Y-m-d'],
            'consignor' => ['required', 'string', 'max:150'],
            'nor_adress' => ['required', 'string', 'max:255'],
            'nor_gst_no' => ['required', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'consignee' => ['required', 'string', 'max:150'],
            'nee_adress' => ['required', 'string', 'max:255'],
            'nee_gst_no' => ['required', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'nugs' => ['required', 'integer', 'min:1'],
            'meth' => ['required', 'in:C_R,C_B,Bags'],
            'description' => ['required', 'string', 'max:1000'],
            'pm' => ['required', 'string', 'max:50'],
            'eway_bill_number' => ['required', 'string', 'max:20'],
            'bill_amount' => ['required', 'numeric', 'min:0'],
            'weight' => ['required', 'numeric', 'min:0'],
            'paid' => ['boolean'],
            'to_pay' => ['boolean'],
            'frieght_amount' => ['required', 'numeric', 'min:0'],
            'sur_ch' => ['required', 'numeric', 'min:0'],
            'c_r' => ['required', 'numeric', 'min:0'],
            'other' => ['required', 'numeric', 'min:0'],
            'bc_amount' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
```

---

## 6. Events & Observers

### 6.1 Events
- `GrCreated(GoodsReceipt $gr)`
- `GrUpdated(GoodsReceipt $gr, array $oldValues)`
- `GrDeleted(GoodsReceipt $gr)`
- `GatepassCreated`
- `ChallanCreated`
- `ChallanLineAdded`
- `FreightMemoCreated`
- `FreightPaymentRecorded`

### 6.2 Observers
- `GoodsReceiptObserver` — auto-fills `created_by_id` from `Auth::id()`, audit-logs.
- `GatepassObserver` — same.
- `ChallanObserver` — same.
- `FreightMemoObserver` — same; recomputes `balance_due`.

### 6.3 Listeners
- `SendGrCreationNotification` — sends an email / in-app notification.
- `InvalidateDashboardCache` — clears the dashboard count cache.
- `WriteAuditLog` — persists to `audit_logs` table.

---

## 7. Jobs & Queues

Switch `QUEUE_CONNECTION` from `sync` to `database` or `redis`. Then:

- `GenerateGrPdfJob` — generates the PDF for the print template.
- `SendDailyReportEmailJob` — sends the daily GR register to admins.
- `SyncExternalErpJob` — future integration.
- `ProcessBulkImportJob` — future bulk import.

---

## 8. Caching

- **Dashboard counts** — `Cache::remember('dash.counts', 300, fn() => [...]);`
- **Office list** — `Cache::remember('offices', 3600, fn() => Branch::orderBy('name')->get());`
- **Truck dropdown** — same pattern.
- **GR number series** — see `NumberSequenceService` (cache the next number with `Cache::lock()`).

---

## 9. API layer (out of scope for first pass, but plan)

- Use `Laravel Sanctum` for token auth.
- Create `app/Http/Controllers/Api/` and `routes/api.php`.
- Return JSON resources (`app/Http/Resources/`).
- Version: `/api/v1/gr`, `/api/v1/gatepass`, etc.

---

## 10. Frontend modernization (preserving design)

> Goal: improve responsiveness, accessibility, and form UX. **Do not redesign.**

### 10.1 Responsiveness
- The current admin layout is desktop-first. Add `@media` queries for tablet/mobile.
- Tables: switch to horizontal scroll on small screens.
- Forms: stack fields vertically on mobile.

### 10.2 Accessibility
- Add `aria-label` to all `<input>` and `<button>`.
- Use `<label for="...">` properly.
- Color contrast: verify against WCAG 2.1 AA.

### 10.3 Form UX
- Add inline error display (`@error('field')`).
- Add `autocomplete` hints (`autocomplete="off"` for OTPs, `autocomplete="gstin"` for GST fields).
- Add `inputmode="numeric"` for numeric fields.
- Date pickers: replace `<input type="text">` with `<input type="date">`.

### 10.4 Data tables
- Add pagination (currently `Model::all()` is loaded — see `performance-report.md §2`).
- Add column sorting.
- Add column filters.
- Add "search" box.
- Add per-page selector (10/25/50/100).

### 10.5 Loading states
- Add a global "Loading…" overlay during AJAX calls.
- Disable submit buttons on form submit.

### 10.6 Error handling
- Wrap AJAX in try/catch with user-friendly error messages.
- Add toast notification component.

### 10.7 Preserve
- Colors (saffron / orange / navy — the "SAURASHTRA EXPRESS" branding).
- Logo placement.
- Print layout (after fixing the `G:\revan\img1.jpg` path).
- All existing fields, in the same order.

---

## 11. Refactoring priority order

| # | Refactor | Effort | Risk | Impact |
|---|---|---|---|---|
| 1 | Rename files to PSR-4 | 1h | Low | Hygiene |
| 2 | Rename models to PSR-1 | 1h | Low | Hygiene |
| 3 | Fix `PostController` (create or remove route) | 1h | Low | Bug fix |
| 4 | Fix `challan_iteam` use statement | 15min | Low | Bug fix |
| 5 | Fix `gatepasses.gst_amount` ghost | 1h | Medium | Bug fix |
| 6 | Fix `copies_print.blade.php` `packeges` typo + logo path | 30min | Low | Cosmetic |
| 7 | Extract `NumberSequenceService` | 8h | Medium | Critical (replaces fragile per-controller logic) |
| 8 | Extract Form Request classes | 8h | Low | Hygiene |
| 9 | Extract Action classes (CreateGrAction, etc.) | 16h | Medium | Maintainability |
| 10 | Add Events + Observers + Listeners | 8h | Low | Decoupling |
| 11 | Add Repository for reports | 16h | Medium | Performance |
| 12 | Add Caching | 4h | Low | Performance |
| 13 | Add Jobs/Queues | 4h | Low | Future-proofing |
| 14 | Implement Freight Memo module | 24h | Medium | Major feature |
| 15 | Add Policies (RBAC) | 8h | Low | Security |
| 16 | Frontend modernization | 40h | Medium | UX |
| 17 | API layer (Sanctum + resources) | 24h | Medium | Future-proofing |
| 18 | Reports (PDF/Excel) | 24h | Medium | Operational |

---

## 12. Migration path (backward compatible)

### 12.1 Phase A: Fix the broken things (no behavior change for working code)
- All renames: keep `class_alias()` or route alias redirects.
- Add new files; don't delete old ones until cutover.
- Use the `Str::contains()` test in `routes/web.php` to map legacy URLs to new ones.

### 12.2 Phase B: Introduce services
- New `NumberSequenceService` is used by `GrController::create()` first.
- Old logic stays in a private method; new logic is preferred.
- Once all controllers use the service, remove the old method.

### 12.3 Phase C: Cutover
- Replace old routes with new routes.
- Update views to use new models.
- Delete old files in a single PR.

---

## 13. Specific bug fixes bundled with refactoring

| Bug | Source | Fix |
|---|---|---|
| `$copy->packeges` (typo) | `copies_print.blade.php:131` | rename to `$copy->nugs` (or add `grs.packages` column) |
| `gst_amount` ghost | `gate_pass_edit.blade.php` | remove input; remove `$gp->gst_amount = ...` |
| `PostController` missing | `routes/web.php:101` | remove the route; remove `Post` model; remove `posts` migration (in a follow-up migration) |
| `challan_iteam` use statement | `dash/ChallanController.php:8` | change to `ChallanItem` (or `ChallanLine` after rename) |
| `User::officeall()` returns Collection | `User.php:51` | change to `->value('office')`; remove the method (it's unused anyway) |
| `gp_no` resets at 1000 | `GatepassController::create` | use `NumberSequenceService` |
| `truckdriver` redirect URL typo | `TruckdriverController` | fix to `dash/truckdriver` |
| `GatepassController::create` `gr::latest()->first()->gr_no` no null check | line 20 | add `optional()` |
| `pm.numberic` typo in validation messages | multiple controllers | fix to `pm.numeric` |
| `DashboardController` empty | `admin/dashboard.blade.php` | build real dashboard |
| `ChallanItemController` empty stub | file is dead | delete file |
| `posts` table orphaned | no controller | drop in migration |
| `package.json` declares Vite but `webpack.mix.js` also exists | dual config | remove `webpack.mix.js` |
| `package.json` `bcrypt` etc. typo in messages | various | search & replace |
| `AdminMiddleware` `count` on every request | `AdminMiddleware` | replace with `roles()->doesntExist()` |

---

## 14. Sample refactored controller (`GrController`)

```php
class GrController extends Controller
{
    public function __construct(
        private NumberSequenceService $sequence,
        private CreateGrAction $createAction,
    ) {}

    public function index(GrRepository $repo)
    {
        $grs = $repo->paginateForOffice(Auth::user()->branch_id, 50);
        return view('admin.category.copies_list', compact('grs'));
    }

    public function create(NumberSequenceService $sequence)
    {
        $grNo = $sequence->nextGrNumber(Auth::user()->office);
        return view('admin.category.copies', [
            'new_id' => $grNo,
            'date' => today()->format('Y-m-d'),
            'offices' => Branch::orderBy('name')->get(),
            'user' => Auth::user(),
        ]);
    }

    public function store(CreateGrRequest $request)
    {
        $gr = $this->createAction->execute($request);
        return redirect()->route('dash.gr.index')
            ->with('success', 'Copy Added successfully');
    }

    // ... edit, update, destroy, show similar
}
```

Compare to the current 580-LOC controller. The new version is ~30 LOC per method, and the action is testable in isolation.

---

## 15. Definition of done

- [ ] All files renamed to PSR-4
- [ ] All class names are PSR-1
- [ ] All ghost fields fixed
- [ ] `NumberSequenceService` in use by every controller that creates a numbered document
- [ ] Form Request classes used for all `store/update` methods
- [ ] All actions are Action classes (`CreateXAction`, `UpdateXAction`)
- [ ] All business events fired; observers attached
- [ ] RBAC enforced on every `/dash/*` route via middleware
- [ ] All lists paginated, filtered, sortable
- [ ] Cache: dashboard, office list, dropdowns
- [ ] Queue: `QUEUE_CONNECTION=database`; at least one Job class exists
- [ ] Audit: every CUD on business tables writes to `audit_logs`
- [ ] Tests: at least one Feature test per business module
- [ ] Documentation: every public method has a PHPDoc summary
