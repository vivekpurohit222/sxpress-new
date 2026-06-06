# Performance Report — Saurashtra Express

> Static analysis of database queries, controller workloads, and rendering costs.
> Companion: `refactoring-plan.md`, `database-reconstruction-report.md`.

---

## 1. Headline findings

| # | Finding | Severity | Affected |
|---|---|---|---|
| 1 | `Model::all()` is used in 4 listing pages | **High** | GrController, GatepassController, ChallanController, FreightController, TruckdriverController, UserController, RoleController, PermissionController |
| 2 | `User::all()->count()` in `AdminMiddleware` on every request | **High** | AdminMiddleware |
| 3 | `User::officeall()` issues an extra query per request | Medium | User model |
| 4 | No pagination on any listing | **High** | all listing views |
| 5 | No indexes on the most-queried columns | **High** | `grs.from_dest`, `gatepasses.gr_no`, `challan_iteams.challan_no`, etc. |
| 6 | `gatepasses.gr_no` UNIQUE prevents one-to-many and forces an extra query | Medium | GatepassController |
| 7 | `challan_iteams.gr_no` UNIQUE same problem | Medium | ChallanController |
| 8 | Inefficient per-office `gr_no` generation: `latest()->first()` then string manipulation in PHP | Medium | GrController::create (4 copies) |
| 9 | View-level `count` of related items (potential N+1) | Medium | challan_list, users/index, roles/index |
| 10 | No caching anywhere | Low | entire app |
| 11 | No queue — all "background" work runs inline | Low | entire app |
| 12 | `package.json` not minified; `vite.config.js` not optimized for prod | Low | assets |

---

## 2. N+1 query risk

| File | Line | Pattern | N+1? | Notes |
|---|---|---|---|---|
| `GrController::index` | 25 | `DB::table('users')->leftjoin('grs', ...)->where('from_dest', $ci)->get()` | Safe | single query |
| `GrController::create` | 46 | `DB::table('grs')->join('users', ...)->latest(...)->first()->gr_no` (×4) | Safe but **4 separate queries** | should be 1 cached query |
| `GatepassController::index` | 23 | `gatepass::all()` | Safe (1 query) | but no pagination |
| `GatepassController::create` | 35 | `gatepass::latest()->first()->gp_no` | Safe (1 query) | fragile counter |
| `GatepassController::create` | 44 | `DB::table('grs')->where('gr_no', $gr_no)->first()` | Safe (1 query) | per-request |
| `UserController::index` | (assumed) | `User::all()` then view shows `roles` per user | **Likely N+1** | the view likely calls `$user->roles` per row |
| `RoleController::index` | (assumed) | `Role::all()` then view shows `permissions` per role | **Likely N+1** | the view likely calls `$role->permissions` per row |
| `PermissionController::index` | (assumed) | `Permission::all()` | Safe | no related in view |
| `ChallanController::index` | 25 | `challan::all()` then view shows `count` of items per challan | **Likely N+1** | if view does `$challan->items()->count()` per row, that's N queries |
| `TruckdriverController::index` | 19 | `truckdriver::all()` | Safe | |
| `User::officeall` | 53 | `DB::table('users')->where('id', $id)->pluck('office')` | 1 extra query per call (but it's never called!) | dead method |

**To verify** the N+1 in `UserController::index` and `RoleController::index` and `ChallanController::index`, enable query log and visit the listing page:
```bash
php artisan tinker
> DB::enableQueryLog();
> // trigger a request
> DB::getQueryLog();
```

---

## 3. Missing indexes (the big one)

### 3.1 Application tables
| Table.column | Index needed? | Currently indexed? | Used in |
|---|---|---|---|
| `grs.from_dest` | **Yes** | ❌ (only `gr_no` is unique) | `GrController::index` filter, every GR list |
| `grs.to_dest` | **Yes** | ❌ | reports |
| `grs.copy_date` | **Yes** | ❌ | date range reports |
| `grs.consignor` | **Yes** | ❌ | search |
| `grs.consignee` | **Yes** | ❌ | search |
| `grs.eway_bill_number` | **Yes** | ❌ | e-way lookup |
| COMPOSITE `(grs.from_dest, grs.copy_date)` | **Critical** | ❌ | the single most common query |
| `gatepasses.gr_no` | Yes (drop UNIQUE, add INDEX) | UNIQUE | lookup from GR |
| `gatepasses.gp_date` | Yes | ❌ | date range reports |
| `gatepasses.from_dest` | Yes | ❌ | filter |
| `gatepasses.to_dest` | Yes | ❌ | filter |
| `challan_iteams.challan_no` | Yes (drop UNIQUE on gr_no, add INDEX) | ❌ | `challanfetchdata` |
| `challan_iteams.gr_no` | Yes (drop UNIQUE) | UNIQUE | `getData` |
| `challans.challan_date` | Yes | ❌ | date range |
| `challans.truck_no` | Yes | ❌ | filter |
| `frieghts.fm_no` | Yes (UNIQUE) | ❌ | business key |
| `frieghts.fm_date` | Yes | ❌ | date range |
| `frieghts.truck_no` | Yes | ❌ | filter |
| `truckdrivers.driver_name` | Yes | ❌ | search |
| `truckdrivers.mobile_no2` (drop UNIQUE) | Yes (no UNIQUE) | UNIQUE | per ghost-field-audit §3 |

### 3.2 Estimated impact
For a 1M-row `grs` table:
- `WHERE from_dest = 'Rajkot'` — currently full table scan (~1M row reads). With index, ~50K row reads (assuming even distribution). **20× speedup**.
- `WHERE from_dest = 'Rajkot' AND copy_date BETWEEN '2025-01-01' AND '2025-01-31'` — full scan × 2 conditions. With composite index, ~1.5K row reads. **600× speedup**.

---

## 4. Pagination

None. Every list page does `Model::all()`. With even 10K rows, this:
- Loads the entire table into PHP memory.
- Renders the entire HTML.
- Crashes the browser for >50K rows.

**Fix:** use `->paginate(50)` and add pagination links in the view.

---

## 5. Caching opportunities

| What | How | TTL |
|---|---|---|
| Office list (the 7 offices) | `Cache::remember('offices', 3600, fn() => Branch::orderBy('name')->get())` | 1h |
| Truck dropdown | same pattern | 1h |
| Permission list | `Cache::remember('permissions', 3600, fn() => Permission::all())` | 1h |
| Role list | same | 1h |
| Dashboard counts (GRs today, this week, etc.) | `Cache::remember('dash.counts', 300, fn() => [...])` | 5m |
| `NumberSequenceService` current value | `Cache::lock('gr_seq', 5)->block(5, fn() => ...)` | atomic |
| Settings (company name, GST no.) | `Cache::rememberForever('settings', fn() => Setting::pluck('value', 'key'))` | forever (invalidate on update) |

---

## 6. Database-level optimizations

| Optimization | Why | Risk |
|---|---|---|
| Use `decimal(12,2)` for all money columns | Currently `decimal` with no precision → MySQL defaults to `decimal(10,0)` which **truncates paise** | None (data migration needed if existing data has paise) |
| Use `decimal(10,3)` for `weight` (kg) | Currently `decimal` defaults to `decimal(10,0)` → truncates grams | None |
| Convert `*_date` columns from `string` to `date` | Faster date range queries; better semantics | **High** (data must be reformatted) |
| Add foreign keys to all implicit relationships | MySQL can use FK indexes for join optimization | Low |
| Add `idx_users_office` on `users.office` | `GrController::index` filter | Low |
| Drop UNIQUE on `gatepasses.gr_no` and `challan_iteams.gr_no` | Allows one-to-many; current constraint is almost certainly a bug | **High** (data may need de-dup) |
| Add `deleted_at` (soft delete) to `grs` and `gatepasses` | Regulatory | Low |

---

## 7. Frontend performance

| Issue | Fix |
|---|---|
| Tables render 10K+ rows | Paginate server-side; lazy-load rows beyond N |
| No CSS/JS minification in production | Run `npm run build` (Vite); commit `public/build/` or use CDN |
| jQuery + AJAX without debouncing | Add `.debounce(300)` to search inputs |
| Inline `<style>` in print template | Move to `public/css/copies_print.css` (already exists) |
| Hard-coded Windows path in print template | Use `{{ asset('images/logo.png') }}` |
| No `<link rel="preload">` for fonts | Add preload hints |

---

## 8. Query-by-query analysis (the worst offenders)

### 8.1 `GrController::index`
```php
$copies = DB::table('users')
    ->leftjoin('grs','grs.from_dest','=','office')
    ->select('grs.*','users.office')
    ->where('grs.from_dest', '=',$ci)
    ->get();
```
- **Cost:** full scan of `grs` (1M rows). The `LEFT JOIN users` is meaningless because we filter on `grs.from_dest = $ci` and we know `users.office = $ci` (we got `$ci` from `Auth::user()->office`).
- **Fix:** `Gr::where('from_branch_id', $branchId)->paginate(50);` (after the schema upgrade) or `Gr::where('from_dest', $office)->paginate(50);` (current schema).

### 8.2 `GrController::create` (4 copies of the same query)
```php
$gr_no = DB::table('grs')
    ->join('users','users.office','=','from_dest')
    ->select('grs.gr_no')
    ->latest('grs.created_at')->first()->gr_no;
```
- **Cost:** full scan of `grs` (latest ordered, so MySQL has to read all rows to find the max `created_at`). Plus the join.
- **Fix:** add an index on `(from_dest, created_at)`. Or use a `number_sequences` table with `MAX(id) + 1` (O(1) with the PK index).

### 8.3 `GatepassController::create`
```php
$gp_no = gatepass::latest()->first()->gp_no;
$gp_no++;
if($gp_no == 1000) { $gp_no = 0; }
```
- **Cost:** full scan of `gatepasses` (O(N)).
- **Fragility:** resets at 1000.
- **Fix:** `number_sequences` table or `DB::table('gatepasses')->max('gp_no') + 1` (with an index on `gp_no`).

### 8.4 `GatepassController::index`
```php
$gr_no = gr::latest()->first()->gr_no;  // unused
$gatepass = gatepass::all();
```
- **Cost:** two full scans (one of `grs`, one of `gatepasses`).
- **The `$gr_no` is unused in the view** — dead code. Remove.

### 8.5 `User::officeall`
- **Cost:** 1 extra query per call.
- **Status:** dead method (no callers). Remove.

### 8.6 `AdminMiddleware`
```php
User::all()->count() == 1
```
- **Cost:** full table scan + PHP collection instantiation on every request.
- **Fix:** `User::doesntHave('roles')->count() == 0` (still a full scan). Better: `Cache::remember('first_user_bootstrap', 3600, fn() => User::count() == 1)`. Or: check a config flag.

---

## 9. Caching strategy (recommended)

| Cache key | Driver | TTL | Invalidated by |
|---|---|---|---|
| `offices` | file/redis | 3600s | `Branch` model save/delete |
| `trucks.dropdown` | file/redis | 3600s | `Truck` model save/delete |
| `permissions.all` | file/redis | 3600s | `Permission` save/delete |
| `roles.all` | file/redis | 3600s | `Role` save/delete |
| `dash.counts` | file/redis | 300s | Any GR/GP/Challan create |
| `gr_seq.{office}` | redis (with lock) | n/a | atomic `lockForUpdate` |
| `gp_seq` | redis (with lock) | n/a | atomic |
| `challan_seq` | redis (with lock) | n/a | atomic |
| `fm_seq` | redis (with lock) | n/a | atomic |
| `settings.*` | file/redis | 86400s | `Setting` save |

Use `Cache::tags(['offices'])->flush()` pattern for invalidation.

---

## 10. Queue strategy

Switch `QUEUE_CONNECTION` from `sync` to `database` (or `redis` in production).

Jobs to create:
- `GenerateGrPdfJob` — generates a PDF copy of a GR for emailing.
- `SendDailyReportJob` — sends the daily GR register to admins at 8am.
- `ReconcileBranchBalancesJob` — nightly job to compute outstanding per branch.
- `ProcessPodUploadJob` — image resize + virus scan for POD uploads.

Use `php artisan queue:work` (supervisord) in production.

---

## 11. Database connection pooling

For high-concurrency, use `proxy_read_timeout` and `max_connections` tuning in MySQL. The Laravel side: use `read` / `write` connections in `config/database.php`:

```php
'mysql' => [
    'driver' => 'mysql',
    'read' => ['host' => ['192.168.1.2']],   // read replica
    'write' => ['host' => ['192.168.1.1']],  // primary
    ...
]
```

---

## 12. Performance budget

| Metric | Current (estimate) | Target (after refactor) |
|---|---|---|
| GR list page TTFB (10K rows) | 800ms | 80ms |
| GR list page TTFB (1M rows) | 60s (timeout) | 200ms (with pagination + index) |
| GR create page TTFB | 500ms (full scan) | 50ms (cache) |
| Dashboard TTFB | n/a (empty) | 100ms (cached) |
| AJAX `getData` (GR lookup) | 200ms (full scan) | 5ms (index) |
| Memory per request | 50MB (loads `Model::all()`) | 8MB (paginates 50) |

---

## 13. Recommended order of performance fixes

1. **Add missing indexes** (`database/migrations_new/2025_01_01_000001_add_indexes.php`) — biggest single win.
2. **Add pagination to all listing pages** — second biggest win, also fixes a stability issue.
3. **Replace `Model::all()` with `paginate()`** everywhere.
4. **Add `NumberSequenceService`** — replaces 4 full scans with 1 atomic lookup.
5. **Cache office/truck/permission/role lists** — quick win.
6. **Cache dashboard counts** — quick win.
7. **Replace `AdminMiddleware` `count`** with a config flag.
8. **Convert `string` date columns to `date`** — allows index usage on date ranges.
9. **Add FK constraints** — enables MySQL join optimization.
10. **Add soft deletes + audit logs** — operational, not perf.
11. **Switch to queues for emails / PDFs**.
12. **Add read replica** for heavy reports.

---

## 14. Tools for ongoing measurement

- **`barryvdh/laravel-debugbar`** — shows query count + duration per request in dev.
- **`spatie/laravel-query-monitor`** — slow query log.
- **Laravel Telescope** — full request/response/query log in dev.
- **MySQL slow query log** — set `long_query_time=1` in production.
- **EXPLAIN** — `EXPLAIN SELECT * FROM grs WHERE from_dest = 'Rajkot' ORDER BY copy_date DESC LIMIT 50;` to verify index usage.
