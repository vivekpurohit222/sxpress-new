# Master Execution Roadmap — Saurashtra Express

> Synthesizes the 18 documents in `docs/`. Every issue is **actionable**, **prioritized**, and **cited** to its source document.
> Purpose: a single document the user can approve to start work. No code in this document — only planning.
>
> **Sources read** (all of `docs/`):
> `framework-version-audit.md`, `codebase-inventory.md`, `project-analysis.md`, `database-reconstruction-report.md`, `form-field-map.md`, `ghost-field-audit.md`, `relationship-map.md`, `erp-workflow-map.md`, `report-inventory.md`, `business-workflows.md`, `module-map.md`, `erd.md`, `laravel-upgrade-plan.md`, `refactoring-plan.md`, `performance-report.md`, `security-audit.md`, `deployment-plan.md`, `final-discovery-report.md`.

---

## 0. TL;DR — what blocks the application right now

The application will **not boot on Laravel 11** and **cannot be installed on a fresh database** in its current state. The four hard blockers are:

1. **No `composer.lock`** — `composer install` will resolve to whatever the latest 11.x is today, not what's known-good (`framework-version-audit.md §1.3`).
2. **Half-migrated bootstrap** — `composer.json` says L11 but `app/Http/Kernel.php` (L8) and `bootstrap/app.php` (L11) both exist; L11 ignores `Kernel.php` (`framework-version-audit.md §4`, `laravel-upgrade-plan.md §3`).
3. **`ChallanController` imports `App\Models\challan_iteam` (typo class)** — every `Challan*` route throws a fatal autoload error (`ghost-field-audit.md §1.5`, `project-analysis.md §11.3`, `erp-workflow-map.md §3`).
4. **`PostController` is referenced in routes but the file does not exist** — every `/dash/posts/*` route 500s (`ghost-field-audit.md §4.1`, `project-analysis.md §11.2`).

Until these are fixed, the application cannot be installed, demoed, or deployed.

---

## 1. Critical blockers preventing application startup (P0)

| # | Priority | Description | Root Cause | Risk | Files Affected | Recommended Fix | Effort |
|---|---|---|---|---|---|---|---|
| 1.1 | **P0** | `composer.lock` is missing | Never committed; `composer.json` was edited directly | Cannot do a reproducible install; CI breaks | `composer.lock` (absent) | Run `composer update --no-scripts` on L8 baseline, commit the lock, then upgrade step-by-step (per `laravel-upgrade-plan.md §Step 2`) | 15 min |
| 1.2 | **P0** | `ChallanController` imports `App\Models\challan_iteam` (typo class) | Copy-paste typo at controller line 8 | **Every** `Challan*` route 500s on first call | `app/Http/Controllers/dash/ChallanController.php:8` | Change `use App\Models\challan_iteam;` → `use App\Models\ChallanItem;` (`ghost-field-audit.md §5.1`) | 5 min |
| 1.3 | **P0** | `PostController` missing — routes reference a non-existent class | Sample scaffold route was never cleaned up | `/dash/posts/*` 500s | `routes/web.php:101`; absent `app/Http/Controllers/PostController.php` | Remove the `Route::resource('/posts', 'App\Http\Controllers\PostController');` line; remove `posts` migration; remove `app/Models/Post.php` (`ghost-field-audit.md §4.1`, `project-analysis.md §11.2`) | 30 min |
| 1.4 | **P0** | `GatepassController::update` writes to non-existent property `$gp->gst_amount` | Form input is dropped; the model is not persisted with the new value | Silent data loss on every Gatepass edit | `app/Http/Controllers/dash/GatepassController.php:201`; `resources/views/admin/category/Gatepass/gate_pass_edit.blade.php` | Remove `$gp->gst_amount = ...` line; remove the `gst_amount` `<input>` from the edit form; or — if business wants a GST field — add a `gst_amount decimal(12,2) nullable` column to `gatepasses` and put it in `$fillable` (`ghost-field-audit.md §1.2`) | 30 min |
| 1.5 | **P0** | Duplicate `create_users_table` migration — two files declare the same class | The original 2014 users migration was edited twice | Fresh `migrate` fails or skips; `office` column is added by the second migration only | `database/migrations/2014_10_12_000000_create_users_table.php` and `database/migrations/2020_11_11_082619_create_users_table.php` | Rename the second file to `2020_11_11_082619_add_office_to_users_table.php` and convert it to a column-add migration (`project-analysis.md §11.1`) | 15 min |
| 1.6 | **P0** | Empty `down()` on GR migration | `public function down() { // }` is a no-op | Cannot rollback the GR table | `database/migrations/2020_11_22_053810_cretae_grs_table.php` | Implement `down()` as `Schema::dropIfExists('grs')` (`project-analysis.md §11.7`) | 5 min |
| 1.7 | **P0** | `User::officeall()` returns a Collection, called with `strcmp()` in `GrController::create()` | Method returns `pluck('office')` (Collection); code expects a string | Runtime warning on every GR create page; no actual data corruption because the method is never called from any successful path | `app/Models/User.php:51–56`; `app/Http/Controllers/dash/GrController.php:188` | Change method body to `return $this->where('id', $id)->value('office');` — and since the method is never called, the safer fix is to **delete it entirely** (`ghost-field-audit.md §3.3`, `§3.4`) | 5 min |
| 1.8 | **P0** | Database does not exist | No MySQL dump, no `database.sqlite`, no seeder produces a usable dataset | Application cannot be installed, demoed, or migrated | entire `database/` directory | Generate reconstructed migrations under `database/migrations_new/` (deferred to `database-reconstruction-report.md §14`) and a baseline seeder that inserts the 7 hard-coded offices and the 4 Spatie permissions; **Gate 2 approval required first** (`final-discovery-report.md §13`) | 8h |
| 1.9 | **P0** | No `tests/` files exist | Project was scaffolded but tests never written | Cannot detect regressions during the L11 upgrade | `tests/` (empty) | Add one smoke test (`GET /` returns 200) and one Feature test per business module that asserts the route exists (`laravel-upgrade-plan.md §Step 1`) | 8h |
| 1.10 | **P0** | `APP_DEBUG=true` in `.env` | Default Laravel `.env` shipped unchanged | Stack traces and `.env` variables leak in any production error response | `.env:4` | Set `APP_DEBUG=false` for any non-local `.env`; add `.env.example` to repo (`security-audit.md §15`, `framework-version-audit.md §5.3`) | 5 min |

**Subtotal: 10 P0 items, ~17.5h (most are 5–30 min bug fixes; #1.8 and #1.9 are larger)**

---

## 2. Missing database tables (P0/P1)

> Source: `database-reconstruction-report.md §11`, `erd.md §2`, `final-discovery-report.md §5`.

| # | Priority | Table | Why it is missing | Risk if not added | Files Affected (target) | Recommended Fix | Effort |
|---|---|---|---|---|---|---|---|
| 2.1 | **P0** | `branches` | `users.office` is a free-text string — no `branches` table exists. The 7 hard-coded office names live only in Blade `@php` arrays. | Tenancy cannot be enforced at the DB level; renaming an office is a global find-replace; new offices require a code change | new `database/migrations_new/2025_01_01_000020_create_branches_table.php` | Create `branches (id, code UK, name, city, state, pincode, phone, is_active, created_at, updated_at)`. Add `users.branch_id` (FK, nullable) in a follow-up migration. Backfill from the 7 known offices. | 4h |
| 2.2 | **P0** | `audit_logs` | Not implemented. No model, no migration, no observers. | Regulatory: 6-year GST retention; forensics impossible. | new `database/migrations_new/2025_01_01_000030_create_audit_logs_table.php` | Create `audit_logs (id, user_id FK, auditable_type, auditable_id, event, old_values JSON, new_values JSON, url, ip, created_at)`. Add `AuditService` and observers on GR/Gatepass/Challan/FreightMemo. (`refactoring-plan.md §3.7`, `security-audit.md §12`) | 8h |
| 2.3 | **P0** | `number_sequences` | GR, Gatepass, and Challan numbers are all generated by `Model::latest()->first()->xxx_no; $xxx_no++;` in the controller. There is no sequence table. | Race conditions; counter collisions; GR rolls over arbitrarily; Gatepass resets at 1000 (`erp-workflow-map.md §2`, `erp-workflow-map.md §3`); counts break after `migrate:fresh` | new `database/migrations_new/2025_01_01_000040_create_number_sequences_table.php` | Create `number_sequences (id, scope UK, current_value BIGINT, prefix, pad_length, updated_at)`. Implement `NumberSequenceService` with row locking. (`refactoring-plan.md §3.1`, `performance-report.md §3`) | 8h |
| 2.4 | P1 | `customers` | Consignor and consignee are free-text on `grs`. A real ERP masters them. | Search/merge/analytics impossible; GST data is duplicated per GR. | new `database/migrations_new/2025_01_01_000050_create_customers_table.php` | Create `customers (id, code UK, name, address, gst_no, pan_no, phone, email, is_active, soft-delete)`. Backfill from `SELECT DISTINCT consignor, consignee FROM grs`. Add `grs.consignor_id` / `consignee_id` nullable FKs. (`erd.md §2`, `business-workflows.md §9`) | 16h |
| 2.5 | P1 | `vendors` | Truck owner (`owner_name`) is free-text on challan/freight. | Settlement statements cannot be generated; bank details have nowhere to live. | new `database/migrations_new/2025_01_01_000060_create_vendors_table.php` | Create `vendors (id, code UK, name, address, gst_no, pan_no, bank_account, ifsc, phone, is_active, soft-delete)`. Backfill from `SELECT DISTINCT owner_name FROM challans`. | 16h |
| 2.6 | P2 | `trucks` + `drivers` + `truck_assignments` | The current `truckdrivers` table conflates the truck, the driver, and their assignment. A driver cannot be reassigned to a new truck. | Driver history is lost when truck is sold; the same driver is duplicated per truck. | new `database/migrations_new/2025_01_01_000070_create_trucks_table.php` (and 2 more) | Split the existing `truckdrivers` table into three. Backfill. Add `challans.truck_id` FK. (`erd.md §2`, `business-workflows.md §6`) | 24h |
| 2.7 | P2 | `pod_uploads` | Not implemented. | No proof of delivery; "GR is delivered" status is unknown. | new `database/migrations_new/2025_01_01_000080_create_pod_uploads_table.php` | Create `pod_uploads (id, gr_id FK, file_path, signature_path, received_by_name, delivered_at, created_at)`. Add upload UI. (`business-workflows.md §8`, `erd.md §2`) | 16h |
| 2.8 | P2 | `payments` | Customer payments not tracked. | Outstanding balance, GST summary, and finance reports cannot run. | new `database/migrations_new/2025_01_01_000090_create_payments_table.php` | Create `payments (id, gr_id FK, paid_on DATE, amount DECIMAL(12,2), method, reference, note, created_at, updated_at)`. | 12h |
| 2.9 | P2 | `freight_payments` | Truck owner payments not tracked. | Settlement is a stub (no payments exist). | new `database/migrations_new/2025_01_01_000100_create_freight_payments_table.php` | Create `freight_payments (id, freight_memo_id FK, paid_on DATE, amount DECIMAL(12,2), method, reference, note, created_at, updated_at)`. | 12h |
| 2.10 | P2 | `freight_lines` | `frieghts.entry_1..4` is a 4-column wide table — can't store N charges, can't add a 5th, can't label them dynamically. | The Freight Memo module cannot be rebuilt without normalizing these. | new `database/migrations_new/2025_01_01_000110_create_freight_lines_table.php` | Create `freight_lines (id, freight_memo_id FK, sequence INT, description, amount DECIMAL(12,2))`. Backfill from existing rows. (`erd.md §2`) | 8h |
| 2.11 | P3 | `personal_access_tokens` (Sanctum) | API layer is planned but no tokens table. | Cannot issue API tokens. | new `database/migrations_new/2025_01_01_000120_create_personal_access_tokens_table.php` | Run `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"` after L11 upgrade. | 1h |
| 2.12 | P3 | `settings` (KV) | App config (default office, default freight, GST rates) is hard-coded in Blade. | Every change is a code deploy. | new `database/migrations_new/2025_01_01_000130_create_settings_table.php` | Create `settings (key UK, value, type, updated_at)`. | 4h |
| 2.13 | P3 | `media` (Spatie Media Library) | POD images, GR scans, gatepass images have nowhere to live. | Cannot attach files to GRs. | deferred — install Spatie Media Library in the modernization pass. | `composer require spatie/laravel-medialibrary; php artisan vendor:publish ...` | 4h |

**Subtotal: 13 missing tables, ~133h. Three P0 (branches, audit_logs, number_sequences) block the modernization.**

---

## 3. Missing foreign keys (P1)

> Source: `relationship-map.md §2–§3`, `erd.md §3`, `database-reconstruction-report.md §10.2`.

| # | Priority | Missing FK | Tables | Risk | Recommended Fix | Effort |
|---|---|---|---|---|---|---|
| 3.1 | **P0** | `gatepasses.gr_id` → `grs.id` (drop `gatepasses.gr_no` UNIQUE) | `gatepasses`, `grs` | A single GR cannot be split across multiple gatepasses (partial delivery is impossible). Database enforces a wrong business rule. | new migration: drop UNIQUE on `gatepasses.gr_no`; add `gatepasses.gr_id BIGINT UNSIGNED`; add FK + INDEX. Keep `gr_no` as a denormalized string. (`relationship-map.md §3.1`, `project-analysis.md §11.8`) | 2h |
| 3.2 | **P0** | `challan_iteams.gr_id` → `grs.id` (drop `challan_iteams.gr_no` UNIQUE) | `challan_iteams`, `grs` | A single GR cannot appear on a return / re-dispatch challan. | new migration: drop UNIQUE on `challan_iteams.gr_no`; add `challan_iteams.gr_id BIGINT UNSIGNED`; add FK + INDEX. Keep `gr_no`. (`relationship-map.md §3.2`, `project-analysis.md §11.8`) | 2h |
| 3.3 | P1 | `challan_iteams.challan_id` → `challans.id` (add `challans.id` first — currently `challan_no` is the PK and no `id` exists) | `challans`, `challan_iteams` | `challan_iteams.challan_no` is a string-to-string match with no FK; orphan lines possible. | new migration: add `challans.id BIGINT UNSIGNED AUTO_INCREMENT`; add `challan_iteams.challan_id BIGINT UNSIGNED`; add FK + INDEX. (`relationship-map.md §3.3`, `database-reconstruction-report.md §7.2`) | 3h |
| 3.4 | P1 | `challans.truck_id` → `truckdrivers.id` (via `truck_no` today) | `challans`, `truckdrivers` | Driver name + license are denormalized; if the truck is edited, the challan is not updated; orphan `truck_no` values possible. | new migration: add `challans.truck_id BIGINT UNSIGNED`; add FK + INDEX. Backfill from `truck_no`. | 2h |
| 3.5 | P1 | `frieghts.truck_id` → `truckdrivers.id` | `frieghts`, `truckdrivers` | No FK; cannot `with('truck')`; reports by truck require a string join. | new migration: add `frieghts.truck_id BIGINT UNSIGNED`; add FK + INDEX. | 2h |
| 3.6 | P1 | `grs.from_branch_id` → `branches.id` | `grs`, `branches` | Tenancy cannot be enforced. | Add column + FK after `branches` table is created (#2.1). | 2h |
| 3.7 | P1 | `grs.to_branch_id` → `branches.id` | `grs`, `branches` | Delivery destination cannot be validated. | Same as #3.6. | 2h |
| 3.8 | P1 | `gatepasses.from_branch_id`, `gatepasses.to_branch_id` → `branches.id` | `gatepasses`, `branches` | Same as #3.6/#3.7. | Same as #3.6. | 2h |
| 3.9 | P1 | `challans.from_branch_id`, `challans.to_branch_id` → `branches.id` | `challans`, `branches` | Same. | Same. | 2h |
| 3.10 | P1 | `frieghts.from_branch_id`, `frieghts.to_branch_id` → `branches.id` | `frieghts`, `branches` | Same. | Same. | 2h |
| 3.11 | P1 | `users.branch_id` → `branches.id` | `users`, `branches` | Tenancy at the user level cannot be enforced. | Add column + FK after #2.1. Keep `office` for backward compat; deprecate. | 2h |
| 3.12 | P1 | `grs.consignor_id`, `grs.consignee_id` → `customers.id` (nullable) | `grs`, `customers` | Free-text consignor/consignee; analytics impossible. | Add FKs after #2.4. | 2h |
| 3.13 | P1 | `trucks.owner_vendor_id` → `vendors.id` (in new `trucks` table) | `trucks`, `vendors` | Free-text truck owner. | Add after #2.5 + #2.6. | 2h |
| 3.14 | P2 | `pod_uploads.gr_id` → `grs.id` | `pod_uploads`, `grs` | POD must be linked to a GR. | Add when `pod_uploads` table is created (#2.7). | (bundled) |
| 3.15 | P2 | `payments.gr_id` → `grs.id` | `payments`, `grs` | Payments must be linked to a GR. | Add when `payments` table is created (#2.8). | (bundled) |
| 3.16 | P2 | `freight_payments.freight_memo_id` → `freight_memos.id` | `freight_payments`, `freight_memos` | Payments must be linked to a memo. | Add when `freight_payments` table is created (#2.9). | (bundled) |
| 3.17 | P3 | `audit_logs.user_id` → `users.id` | `audit_logs`, `users` | Optional FK (audit survives user deletion). | Add when `audit_logs` table is created (#2.2). | (bundled) |

**Subtotal: 17 missing FKs, ~26h. The two P0 (#3.1, #3.2) block the modernization because they require dropping wrong UNIQUE constraints.**

---

## 4. Missing indexes (P1)

> Source: `performance-report.md §3`, `erd.md §4`, `database-reconstruction-report.md §4.5/5.4/6.4/7.4/8.4/9.4`.

| # | Priority | Index | Used by | Risk if missing | Recommended Fix | Effort |
|---|---|---|---|---|---|---|
| 4.1 | **P0** | `grs.from_dest` | `GrController@index` filter on every request | Full table scan on every page load | new `database/migrations_new/2025_01_01_000001_add_indexes.php` | 1h |
| 4.2 | **P0** | `grs.to_dest` | Reports by destination | Full scan | same migration | 1h |
| 4.3 | **P0** | `grs.copy_date` | Date-range reports | Full scan (also a type issue — see §7.1) | same migration | 1h |
| 4.4 | **P0** | COMPOSITE `(grs.from_dest, grs.copy_date)` | Single most common query (list per office, date-sorted) | ~600× slowdown on 1M-row table (per `performance-report.md §3.2`) | same migration | (bundled) |
| 4.5 | P1 | `grs.consignor` | Search | Full scan | same migration | 1h |
| 4.6 | P1 | `grs.consignee` | Search | Full scan | same migration | (bundled) |
| 4.7 | P1 | `grs.eway_bill_number` | E-Way lookup | Full scan | same migration | (bundled) |
| 4.8 | P1 | `gatepasses.gr_no` (after UNIQUE dropped — see #3.1) | GR → Gatepass lookup | Full scan | same migration | 1h |
| 4.9 | P1 | `gatepasses.gp_date` | Date range reports | Full scan | same migration | (bundled) |
| 4.10 | P1 | `gatepasses.from_dest`, `gatepasses.to_dest` | Filter by office | Full scan | same migration | (bundled) |
| 4.11 | P1 | `gatepasses (gp_no, gp_date)` UNIQUE | Business key (per office per day) | Allows duplicate GP numbers on same day | same migration | (bundled) |
| 4.12 | P1 | `challan_iteams.challan_no` | `challanfetchdata` AJAX | Full scan | same migration | 1h |
| 4.13 | P1 | `challan_iteams.gr_no` (after UNIQUE dropped — see #3.2) | `getData` AJAX | Full scan | same migration | (bundled) |
| 4.14 | P1 | `challans.challan_date` | Date range reports | Full scan | same migration | (bundled) |
| 4.15 | P1 | `challans.truck_no` | Filter by truck | Full scan | same migration | (bundled) |
| 4.16 | P1 | `frieghts.fm_no` UNIQUE | Business key | Allows duplicate FM numbers | same migration | 1h |
| 4.17 | P1 | `frieghts.fm_date`, `frieghts.truck_no`, `frieghts (from_dest, to_dest)` | Reports | Full scan | same migration | (bundled) |
| 4.18 | P1 | `truckdrivers.driver_name` | Search | Full scan | same migration | 1h |
| 4.19 | P1 | `users.office` | Tenancy filter on `GrController@index` | Full scan | same migration | 1h |
| 4.20 | P1 | `users.email` UNIQUE | Login (already exists) | — | already exists | 0 |

**Subtotal: 20 indexes. The four P0 (4.1–4.4) are the highest-impact changes in the entire modernization pass (per `performance-report.md §3.2` and `§13`). All indexes ship in one migration so the deploy is atomic.**

---

## 5. Missing migrations (P0)

> Source: `database-reconstruction-report.md §14`, `final-discovery-report.md §14`, `laravel-upgrade-plan.md §Step 7`.

The following migrations must be generated under `database/migrations_new/` (a new directory) and applied via `php artisan migrate --path=database/migrations_new` (the legacy migrations are preserved untouched for rollback safety per `laravel-upgrade-plan.md §9`).

| # | Priority | Migration | What it changes | Effort |
|---|---|---|---|---|
| 5.1 | **P0** | `2025_01_01_000001_add_indexes.php` | All 20 indexes from §4. | 4h |
| 5.2 | **P0** | `2025_01_01_000002_drop_wrong_uniques.php` | Drop UNIQUE on `gatepasses.gr_no`, `challan_iteams.gr_no`, `truckdrivers.mobile_no2`. Add `gr_id` / `challan_id` columns + FKs (per §3.1, §3.2, §3.3). | 4h |
| 5.3 | **P0** | `2025_01_01_000003_fix_column_types.php` | `grs.copy_date`, `gatepasses.gp_date`, `frieghts.fm_date`, `challans.challan_date` → `date`. `grs.weight` → `decimal(10,3)`. All money → `decimal(12,2)`. `truckdrivers.driver_address` → `text`. (`database-reconstruction-report.md §13`) | 8h |
| 5.4 | **P0** | `2025_01_01_000010_create_number_sequences_table.php` | New `number_sequences` table + seed the 4 scopes (`gr`, `gp`, `challan`, `fm`). | 2h |
| 5.5 | **P0** | `2025_01_01_000020_create_branches_table.php` | New `branches` + seed 7 offices. | 2h |
| 5.6 | **P0** | `2025_01_01_000030_create_audit_logs_table.php` | New `audit_logs`. | 2h |
| 5.7 | P1 | `2025_01_01_000040_add_branch_fks.php` | `users.branch_id`, `grs.from_branch_id`, `grs.to_branch_id`, `gatepasses.from_branch_id`, `gatepasses.to_branch_id`, `challans.from_branch_id`, `challans.to_branch_id`, `frieghts.from_branch_id`, `frieghts.to_branch_id`. Backfill. | 8h |
| 5.8 | P1 | `2025_01_01_000050_create_customers_table.php` + add `grs.consignor_id` / `consignee_id` | New `customers` + FKs + backfill. | 8h |
| 5.9 | P1 | `2025_01_01_000060_create_vendors_table.php` | New `vendors` + seed. | 4h |
| 5.10 | P2 | `2025_01_01_000070_create_trucks_table.php` (and `drivers`, `truck_assignments`) | Split `truckdrivers`. | 16h |
| 5.11 | P2 | `2025_01_01_000080_create_pod_uploads_table.php` | New `pod_uploads`. | 2h |
| 5.12 | P2 | `2025_01_01_000090_create_payments_table.php` | New `payments`. | 2h |
| 5.13 | P2 | `2025_01_01_000100_create_freight_payments_table.php` | New `freight_payments`. | 2h |
| 5.14 | P2 | `2025_01_01_000110_create_freight_lines_table.php` | New `freight_lines` + backfill from `frieghts.entry_1..4`. | 4h |
| 5.15 | P2 | `2025_01_01_000120_rename_legacy_columns.php` | Rename typos: `grs.nor_adress` → `consignor_address`, `grs.nee_adress` → `consignee_address`, `grs.nor_gst_no` → `consignor_gst_no`, `grs.nee_gst_no` → `consignee_gst_no`, `gatepasses.m_s` → `consignor`. Keep `grs.gr_no` and `grs.pm` as-is (display-name change only). | 4h |
| 5.16 | P2 | `2025_01_01_000130_rename_legacy_tables.php` | `frieghts` → `freight_memos` (with view for backward compat), `challan_iteams` → `challan_lines`. | 4h |
| 5.17 | P2 | `2025_01_01_000140_add_soft_deletes_and_audit_columns.php` | Add `deleted_at` + `created_by_id` + `updated_by_id` to every business table. (`erd.md §5/§6`) | 8h |
| 5.18 | P2 | `2025_01_01_000150_drop_posts_table.php` | Drop `posts` table (after route is removed per #1.3). | 1h |
| 5.19 | P3 | `2025_01_01_000160_create_personal_access_tokens_table.php` | Sanctum tokens (for future API). | 1h |
| 5.20 | P3 | `2025_01_01_000170_create_settings_table.php` | App-level KV. | 2h |
| 5.21 | P3 | `2025_01_01_000180_add_completed_status_columns.php` | `grs.status` enum (booked / in_transit / delivered / closed); `challans.status`; `gatepasses.status`. | 4h |
| 5.22 | P1 | `2025_01_01_000200_seeder_baseline.php` | Seeder: insert the 7 offices, 4 Spatie permissions, an admin user. | 4h |

**Subtotal: 22 migrations, ~96h. The seven P0 migrations ship first; the rest follow per the execution order in §11.**

---

## 6. Broken relationships (P1)

> Source: `relationship-map.md §1–§3`, `codebase-inventory.md §1.2–1.7`, `module-map.md §2`.

| # | Priority | Broken relationship | How it is broken today | Risk | Files Affected | Recommended Fix | Effort |
|---|---|---|---|---|---|---|---|
| 6.1 | **P0** | GR → ChallanItem (1-to-many) | `challan_iteams.gr_no` is UNIQUE — a GR can appear on at most one challan | Cannot re-dispatch a GR; cannot split a GR onto two trucks; orphan lines possible | `database/migrations/2020_12_06_102452_create_challan_iteams_table.php`; `app/Http/Controllers/dash/ChallanController.php` | Drop UNIQUE, add `gr_id` FK (per #3.2). | 2h |
| 6.2 | **P0** | GR → Gatepass (1-to-many) | `gatepasses.gr_no` is UNIQUE — a GR can be released to at most one gatepass | Partial delivery is impossible | `database/migrations/2020_10_29_082717_create_gatepasses_table.php` | Drop UNIQUE, add `gr_id` FK (per #3.1). | 2h |
| 6.3 | P1 | No Eloquent relationships declared in any of the 8 application models | All cross-table work uses `DB::table()->join()` | N+1 risk everywhere, no eager loading, no `with()`, no `route model binding` | all `app/Models/*.php` | Add `hasMany` / `belongsTo` methods per `relationship-map.md §8` sample code. | 4h |
| 6.4 | P1 | `users.office` is a string, not a FK | Tenancy key has no referential integrity | Renaming an office is a global find-replace | `database/migrations/2014_10_12_000000_create_users_table.php` | Add `branches` table + `users.branch_id` (per #2.1, #3.11). | (bundled with #5.5) |
| 6.5 | P1 | Challan → ChallanItem (1-to-many) | `challans` has no `id` column (PK is `challan_no`); `challan_iteams.challan_no` has no FK | Orphan lines possible; `Model::find($id)` semantics broken | `database/migrations/2020_12_09_090031_create_challans_table.php` | Add `id` PK + `challan_id` FK (per #3.3). | (bundled with #5.2) |
| 6.6 | P1 | Challan → Truck (1-to-many) | `challans.truck_no` is a string with no FK; driver_name + license are denormalized | Cannot `with('truck')`; reports by truck require a string join | `database/migrations/2020_12_09_090031_create_challans_table.php` | Add `truck_id` FK (per #3.4). | 2h |
| 6.7 | P1 | Freight → Truck (intended 1-to-many) | `frieghts.truck_no` has no FK; the entire module is a stub | Cannot rebuild Freight Memo without normalizing the truck link | `database/migrations/2020_11_08_120739_create_frieghts_table.php` | Add `truck_id` FK (per #3.5). | 2h |
| 6.8 | P2 | Branch → GR / Gatepass / Challan / Freight (all transactional tables) | `from_dest` / `to_dest` are free-text on every transactional table | Tenancy cannot be enforced; new office = code change | all transactional migrations | Add `from_branch_id` / `to_branch_id` FKs (per #3.6–3.10). | (bundled with #5.7) |
| 6.9 | P2 | Customer → GR (consignor / consignee) | Free-text on every GR | Analytics, dedup, GST reconciliation all blocked | `database/migrations/2020_11_22_053810_cretae_grs_table.php` | Add `consignor_id` / `consignee_id` FKs (per #3.12, after `customers` table). | (bundled with #5.8) |
| 6.10 | P2 | Vendor → Truck (owner) | Free-text `owner_name` on challan/freight | Settlement statement cannot be generated | `database/migrations/2020_11_09_095555_create_truckdrivers_table.php` (future `trucks`) | Add `owner_vendor_id` FK after `vendors` table. | (bundled with #5.9, #5.10) |
| 6.11 | P2 | POD → GR | Not present | No proof of delivery; no status tracking | new `pod_uploads` migration | Add `gr_id` FK (per #3.14). | (bundled with #5.11) |
| 6.12 | P2 | Payment → GR (customer) | Not present | No outstanding balance; no finance reports | new `payments` migration | Add `gr_id` FK (per #3.15). | (bundled with #5.12) |
| 6.13 | P2 | FreightPayment → FreightMemo | Not present | No settlement tracking | new `freight_payments` migration | Add `freight_memo_id` FK (per #3.16). | (bundled with #5.13) |

**Subtotal: 13 broken relationships. Two are P0 (the two UNIQUE constraints).**

---

## 7. Ghost fields (P1)

> Source: `ghost-field-audit.md` (entire document), `form-field-map.md` (summary table at end), `codebase-inventory.md §1`.

| # | Priority | Ghost | Location | Risk | Recommended Fix | Effort |
|---|---|---|---|---|---|---|
| 7.1 | **P0** | `App\Models\challan_iteam` (typo class) in `use` statement | `app/Http/Controllers/dash/ChallanController.php:8` | Every `Challan*` route throws on first call | Change to `App\Models\ChallanItem;` (`ghost-field-audit.md §5.1`) — also covered in #1.2 | 5 min |
| 7.2 | **P0** | `gst_amount` form input + `$gp->gst_amount = ...` | `resources/views/admin/category/Gatepass/gate_pass_edit.blade.php`; `app/Http/Controllers/dash/GatepassController.php:201` | Data loss on every Gatepass edit | Remove input + line (`ghost-field-audit.md §1.2`) — also covered in #1.4 | 30 min |
| 7.3 | **P0** | Hard-coded `G:\revan\img1.jpg` | `resources/views/admin/category/copies_print.blade.php:13` | Logo image is broken on every print | Move to `public/images/logo.png`; use `{{ asset('images/logo.png') }}` (`ghost-field-audit.md §1.3`) | 30 min |
| 7.4 | **P0** | `$copy->packeges` (typo of `nugs`) | `resources/views/admin/category/copies_print.blade.php:131` | The "Packages" column on GR print is always empty | Rename to `$copy->nugs` (`ghost-field-audit.md §1.1`) | 5 min |
| 7.5 | **P0** | `User::officeall()` returns Collection, runtime bug if called | `app/Models/User.php:51` | Runtime warning; method is dead anyway | Delete method entirely (`ghost-field-audit.md §3.3, §3.4`) — also covered in #1.7 | 5 min |
| 7.6 | P1 | `grs.nor_adress` (typo of `consignor_address`) | migration, model, controller, all views | Typo in every screen; cannot rename without migration | Migration #5.15 renames the column. Update all 4 view files. (`ghost-field-audit.md §2.1`) | 2h |
| 7.7 | P1 | `grs.nee_adress` (typo of `consignee_address`) | migration, model, controller, all views | Same | Same migration. (`ghost-field-audit.md §2.2`) | (bundled) |
| 7.8 | P1 | `grs.nor_gst_no` → `consignor_gst_no` | same | Same | Same migration. (`ghost-field-audit.md §2.3`) | (bundled) |
| 7.9 | P1 | `grs.nee_gst_no` → `consignee_gst_no` | same | Same | Same migration. (`ghost-field-audit.md §2.3`) | (bundled) |
| 7.10 | P1 | `gatepasses.m_s` → `consignor` | migration, model, controller, gate_pass*.blade.php | Display shows "MS" not "Consignor" | Same migration. (`ghost-field-audit.md §2.4`) | (bundled) |
| 7.11 | P1 | `frieghts` table / `Freight` model / `frieght` URL → `freight_memos` / `FreightMemo` / `freight-memo` | migration, model, controller, all views, 3 URL spellings | Three different spellings in the same app | Migration #5.16 renames. Add `Route::redirect('/dash/freight-memo', '/dash/frieghtmemo')` for backward compat. (`ghost-field-audit.md §2.5`) | 4h |
| 7.12 | P1 | `challan_iteams` table / `ChallanItem` model → `challan_lines` / `ChallanLine` | migration, model, controller, all views | One word, three misspellings | Migration #5.16 renames. (`ghost-field-audit.md §2.6`) | (bundled) |
| 7.13 | P1 | `frieghts.balance_to_sn` → `balance_due` | migration, model, view | "S.N." is internal shorthand | Migration #5.15 renames. (`ghost-field-audit.md §2.8`) | (bundled) |
| 7.14 | P1 | `gatepasses.dc_amount` → `delivery_charge` | migration, model, view | "DC" is ambiguous | Migration #5.15 renames. (`ghost-field-audit.md §2.9`) | (bundled) |
| 7.15 | P1 | `frieghts.entry_1..4` (4 columns) → `freight_lines` (separate table) | migration, model, view | Opaque column names; cannot add a 5th charge | Migration #5.14 normalizes. (`ghost-field-audit.md §2.7`) | (bundled) |
| 7.16 | P1 | `pm.numberic` (typo of `numeric`) in validation messages | `app/Http/Controllers/dash/GrController.php` (×2) | Validation rule is **never applied** | Fix to `pm.numeric`. (`security-audit.md §5.1`, `project-analysis.md §11.9`, `laravel-upgrade-plan.md §3`) | 5 min |
| 7.17 | P1 | Freight Memo form inputs lack `name` attributes | `resources/views/admin/category/FrieghtMemo/Frieght_memo*.blade.php` | The form posts an empty payload even when fields are filled | Add `name="..."` to every input. (`form-field-map.md §7`, `erp-workflow-map.md §4`) | 2h |
| 7.18 | P2 | `gatepasses.gp_no` resets at 1000 | `app/Http/Controllers/dash/GatepassController.php` | Counter collision at boundary | Use `NumberSequenceService`. (`performance-report.md §8.3`, `refactoring-plan.md §3.1`) | (bundled with #5.4) |
| 7.19 | P2 | `challan_no` rolls at 1001 (AA-1001 → AB-0001) — fragile | `app/Http/Controllers/dash/ChallanController.php` | Counter collision at boundary | Use `NumberSequenceService`. | (bundled) |
| 7.20 | P2 | GR number generation is duplicated 4× in the controller (one per office) | `app/Http/Controllers/dash/GrController.php` | Fragile, untestable | Use `NumberSequenceService`. (`refactoring-plan.md §3.1`) | (bundled) |
| 7.21 | P2 | `truckdrivers.truck_no` + `license` UNIQUE | migration | If a truck is sold and a new driver is assigned, the truck_no is preserved but the license changes — would break | Drop UNIQUE on `license`; keep on `truck_no`. (`ghost-field-audit.md §3.8`, `erp-workflow-map.md §5`) | 1h |
| 7.22 | P2 | `truckdrivers.mobile_no2` UNIQUE | migration | Nullable UNIQUE is fragile | Drop UNIQUE. (`ghost-field-audit.md §3.8`) | 30 min |
| 7.23 | P2 | Duplicate `from_dest` `<select>` (one disabled, one hidden) | `resources/views/admin/category/copies.blade.php` | Brittle workaround | Keep the visible disabled select; use a single hidden input. (`project-analysis.md §11.6`) | 30 min |
| 7.24 | P2 | `TruckdriverController` redirect URL `admin/back/truckdriver` (typo) | `app/Http/Controllers/TruckdriverController.php` | 302 to non-existent route → 404 | Fix to `dash/truckdriver`. (`erp-workflow-map.md §5`, `codebase-inventory.md §2.1`) | 5 min |
| 7.25 | P2 | `dash\DashboardController` (root) is dead (no route) | `app/Http/Controllers/DashboardController.php` | Dead code | Delete file. (`ghost-field-audit.md §3.6`) | 5 min |
| 7.26 | P2 | `HomeController::index()` is dead (root route overrides) | `app/Http/Controllers/HomeController.php` | Dead code | Delete file. (`ghost-field-audit.md §3.5`) | 5 min |
| 7.27 | P2 | `dash\ChallanItemController` class `ChallanItamsController` (PSR-4 mismatch) | `app/Http/Controllers/dash/ChallanItemController.php` | Autoloader fatal error if ever called | Delete file. (`ghost-field-audit.md §3.8`, `§5.2`) | 5 min |
| 7.28 | P2 | `ClearanceMiddleware` gates `posts.*` only | `app/Http/Middleware/ClearanceMiddleware.php` | Dead code (posts has no controller) | Delete middleware. (`ghost-field-audit.md §6.1`) | 15 min |
| 7.29 | P3 | `dashboard` view is empty stub | `resources/views/admin/dashboard.blade.php` | No value to the user | Build a real dashboard with GR/Gatepass/Challan counts. (`module-map.md §4`, `erp-workflow-map.md §8`) | 8h |
| 7.30 | P3 | `dashboard` controller returns no data | `app/Http/Controllers/dash/DashboardController.php` | Same | Same | (bundled) |
| 7.31 | P3 | `routes/console.php` empty — no scheduled tasks | `routes/console.php` | No daily GR register, no backup reminders, etc. | Add scheduled jobs. (`deployment-plan.md §10`) | 4h |
| 7.32 | P3 | `routes/channels.php` empty | `routes/channels.php` | No real-time | Add (or leave empty for now). | 0 |
| 7.33 | P3 | `package.json` declares Vite but `webpack.mix.js` also exists | `webpack.mix.js` | Dead config | Delete file. (`framework-version-audit.md §5.2`, `project-analysis.md §1.4`) | 5 min |
| 7.34 | P3 | `Composer-Setup.exe` in project root | `Composer-Setup.exe` | Should not be in version control | Delete file; add `*.exe` to `.gitignore`. (`project-analysis.md §11.10`, `laravel-upgrade-plan.md §Step 0`) | 5 min |

**Subtotal: 34 ghost fields. 5 are P0 (the runtime-killing ones). 1 is #1.2, 1 is #1.4, 1 is #1.7, 1 is the logo path, 1 is the print typo.**

---

## 8. Broken reports (P1/P2)

> Source: `report-inventory.md` (entire document), `business-workflows.md §10`, `final-discovery-report.md §10.3`.

The application has **no reports at all**. Everything below is "missing". Listed in priority order.

| # | Priority | Report | Why it is missing | Risk | Recommended Fix | Effort |
|---|---|---|---|---|---|---|
| 8.1 | P1 | Daily GR Register | No `date` column on `grs` (string today); no date-range query; no PDF/Excel library | Operations cannot run the daily register; users export the listing via browser PDF (broken) | Migration #5.3 (`copy_date` → `date`); `barryvdh/laravel-dompdf`; `app/Reports/GrRegisterReport.php`. (`report-inventory.md §3.1.1`) | 8h |
| 8.2 | P1 | Pending deliveries (GRs without Gatepass/Challan) | The relationship is broken (UNIQUE on gr_no); no LEFT JOIN used | Operations has no exception list | After #3.1 + #3.2, add `GrRepository::pendingDeliveries(branchId)`. (`report-inventory.md §3.2.1`) | 4h |
| 8.3 | P1 | Branch-wise GR count (date range) | No date column; no `branches` table | Management dashboard gap | Same as #8.1, plus `branches` table. | 4h |
| 8.4 | P1 | In-transit list (Challans without POD) | No `pod_uploads` table | Operations gap | After #2.7, add `ChallanRepository::inTransit()`. | 4h |
| 8.5 | P2 | Branch revenue | No `branches` table; no aggregation service | Finance gap | Add `BranchRevenueReport` service. | 8h |
| 8.6 | P2 | Truck utilization | No report service | Ops gap | Add `TruckUtilizationReport` service. | 8h |
| 8.7 | P2 | Outstanding freight | No `payments` table | Finance gap | After #2.8, add `OutstandingFreightReport` service. | 16h |
| 8.8 | P2 | GST summary | `grs.other` is the GST amount; no aggregation | Tax filing gap | Add `GstSummaryReport` service. | 8h |
| 8.9 | P2 | E-Way bill expiry | `grs.copy_date` is string (uncomparable); no expiry logic | Compliance gap | After #5.3, add `EwayBillExpiryReport` service. | 8h |
| 8.10 | P2 | Customer-wise revenue | No `customers` table | Finance gap | After #2.4, add `CustomerRevenueReport` service. | 16h |
| 8.11 | P3 | User activity (last login, GRs created per user) | No `last_login_at` column; no `created_by_id` | Audit gap | After #5.17, add `UserActivityReport` service. | 8h |
| 8.12 | P3 | Audit log of changes | No `audit_logs` table | Forensics gap | After #2.2, add `AuditLogReport` service. | 8h |
| 8.13 | P3 | Customer copy reprint (searchable by gr_no) | No search; print template is broken (#7.3) | Customer service gap | Fix template (per #7.3); add `gr_no` search. | 4h |
| 8.14 | P3 | E-Way bill lookup | No search; eway_bill_number is a string | Customer service gap | Add search box. | 4h |
| 8.15 | P3 | Truck owner statement (Freight Memo) | Freight Memo is a stub; no vendor master | Settlement gap | Rebuild Freight Memo module (per #7.11, #7.15, #2.9, #2.10). | 24h |
| 8.16 | P3 | Payment register | No `payments` table | Finance gap | After #2.8. | 8h |
| 8.17 | P3 | Branch transfer log | No `branches` table; no audit log | Audit gap | After #2.1, #2.2. | 8h |
| 8.18 | P3 | PDF/Excel export framework | No DomPDF, no Maatwebsite/Excel in `composer.json` | Cannot export anything | Add `barryvdh/laravel-dompdf` + `maatwebsite/excel`. (`report-inventory.md §7`) | 4h |

**Subtotal: 18 reports. The four P1 reports are operational blockers for daily office work.**

---

## 9. Security issues (P0/P1)

> Source: `security-audit.md §1` (risk table) and `§2–§18`.

| # | Priority | Issue | Severity | Files Affected | Recommended Fix | Effort |
|---|---|---|---|---|---|---|
| 9.1 | **P0** | `ChallanController::store` and `challanIteamStore` do `Model::insert($request->all())` — mass-assignment with no validation | **Critical** | `app/Http/Controllers/dash/ChallanController.php:92, :128` | Replace with `Challan::create($request->validated())` after a `CreateChallanRequest` FormRequest. (`security-audit.md §4.2`, `§17`) | 30 min |
| 9.2 | **P0** | `APP_DEBUG=true` in `.env` | High (if shipped) | `.env:4` | Set false in production; add `.env.example`. (`security-audit.md §15.1`, `framework-version-audit.md §5.3`) | 5 min |
| 9.3 | **P0** | `.env` `APP_KEY` is committed to repo (the file is checked in) | High | `.env:3` | Verify `.env` is in `.gitignore`; rotate the key. (`security-audit.md §1.4`, `framework-version-audit.md §5.3`) | 15 min |
| 9.4 | **P0** | `AdminMiddleware` allows any request if `User::all()->count() == 1` (race condition, O(N) query on every request) | High (multi-tenant) | `app/Http/Middleware/AdminMiddleware.php` | Replace with `Cache::remember('bootstrap.first_user', 3600, fn() => User::count() === 1)`. (`security-audit.md §3.2`, `§17`, `performance-report.md §8.6`) | 1h |
| 9.5 | **P0** | No CSRF protection verified on AJAX Challan endpoints | High (if missing) | `routes/web.php` AJAX routes | Verify in browser that `X-CSRF-TOKEN` is sent. Add a CSRF meta tag to the master layout if missing. (`security-audit.md §8.2`) | 1h |
| 9.6 | P1 | No business-level authorization (any logged-in user can hit any `/dash/*` route) | High | all `/dash/*` routes | Add `GoodsReceiptPolicy`, `GatepassPolicy`, `ChallanPolicy`, `FreightMemoPolicy`, `TruckDriverPolicy` and use `$this->authorize(...)` in controllers. (`security-audit.md §3.2`, `§17`) | 8h |
| 9.7 | P1 | No rate limiting on `POST /login` (brute force possible) | High | `app/Http/Controllers/Auth/LoginController.php` | Add `throttle:5,1` middleware. (`security-audit.md §13.2`) | 30 min |
| 9.8 | P1 | No password complexity / rotation requirements | Medium | `app/Models/User.php` | Add `Password::min(8)->mixedCase()->numbers()->symbols()`; add `last_password_change_at` column. (`security-audit.md §2.2`) | 4h |
| 9.9 | P1 | No 2FA | Medium | n/a | Add `pragmarx/google2fa-laravel` as part of L11 upgrade. (`security-audit.md §2.2`) | 8h |
| 9.10 | P1 | No audit log of changes (regulatory, forensics) | High | n/a | See #2.2 (audit_logs table). (`security-audit.md §12.2`, `§17`) | (bundled with #2.2) |
| 9.11 | P2 | Validation rule typo `'pm.numberic'` is never applied (typo of `numeric`) | Medium | `app/Http/Controllers/dash/GrController.php` (×2) | Fix to `pm.numeric`. (`security-audit.md §5.1`, `§17`) | 5 min |
| 9.12 | P2 | GSTIN, mobile, E-Way bill format not enforced (only length check) | Medium | `GrController`, `TruckdriverController` | Add regex. (`security-audit.md §5.3`, `§17`) | 2h |
| 9.13 | P2 | No `SESSION_SECURE_COOKIE=true` for production | Medium | `.env` | Set for production. (`security-audit.md §10.2`) | 5 min |
| 9.14 | P2 | `BCRYPT_ROUNDS` not set (default 10) | Medium | `.env` | Set to 12. (`security-audit.md §11.2`) | 5 min |
| 9.15 | P2 | `bcrypt` typo in error messages / various | Low | `package.json` and others | Search & replace. | 1h |
| 9.16 | P2 | No `Content-Security-Policy` header | Low | n/a | Add `App\Http\Middleware\AddCsp`. (`security-audit.md §7.3`) | 1h |
| 9.17 | P2 | No `X-Frame-Options` / `X-Content-Type-Options` headers | Low | n/a | Add middleware. (`security-audit.md §17`) | 1h |
| 9.18 | P3 | `routes/api.php` exposes `/api/user` behind `auth:api` (unused scaffold) | Low | `routes/api.php` | Remove or guard. (`security-audit.md §1.19`) | 15 min |
| 9.19 | P3 | `laravelcollective/html` ^6.4 is abandoned (last release 2021) | Low | `composer.json` | Remove. (`security-audit.md §1.20`) | 15 min |
| 9.20 | P3 | `fruitcake/laravel-cors` ^3.0 is superseded by L11 built-in | Low | `composer.json`, `config/cors.php` | Remove package, use built-in. (`security-audit.md §14.2`, `framework-version-audit.md §5.2`) | 30 min |
| 9.21 | P3 | `LOG_LEVEL=debug` in production | Low | `.env` | Set to `info`. (`security-audit.md §12.2`) | 5 min |
| 9.22 | P3 | PII (consignee phone, address) not encrypted at rest | Low | n/a | Encrypt sensitive columns using `Crypt`. (`security-audit.md §11.2`) | 8h |

**Subtotal: 22 security issues. 5 are P0 (mass-assignment is the only Critical).**

---

## 10. Performance issues (P1)

> Source: `performance-report.md §1` (headline findings), `§2` (N+1), `§3` (indexes), `§6` (DB-level), `§8` (query-by-query).

| # | Priority | Issue | Severity | Files Affected | Recommended Fix | Effort |
|---|---|---|---|---|---|---|
| 10.1 | **P0** | `Model::all()` is used in 4+ listing pages (no pagination) | High | `dash/GrController.php`, `dash/GatepassController.php`, `dash/ChallanController.php`, `dash/FreightController.php`, `TruckdriverController.php`, `UserController.php`, `RoleController.php`, `PermissionController.php` | Replace with `->paginate(50)`; add pagination links in views. (`performance-report.md §4`) | 4h |
| 10.2 | **P0** | `User::all()->count()` in `AdminMiddleware` on every request | High | `app/Http/Middleware/AdminMiddleware.php` | Cache, or use a config flag. (`performance-report.md §8.6`, `security-audit.md §3.2`) | 1h |
| 10.3 | **P0** | No indexes on the most-queried columns | High | all transactional tables | Apply migration #5.1. (`performance-report.md §3.1`) | 4h |
| 10.4 | P1 | `gatepasses.gr_no` UNIQUE prevents one-to-many and forces an extra query | Medium | `database/migrations/2020_10_29_082717_create_gatepasses_table.php` | Drop UNIQUE; add `gr_id` FK. (`performance-report.md §1.6`, `§3.1`) — covered in #3.1 | (bundled) |
| 10.5 | P1 | `challan_iteams.gr_no` UNIQUE same problem | Medium | `database/migrations/2020_12_06_102452_create_challan_iteams_table.php` | Drop UNIQUE; add `gr_id` FK. (`performance-report.md §1.7`, `§3.1`) — covered in #3.2 | (bundled) |
| 10.6 | P1 | Inefficient per-office `gr_no` generation: `latest()->first()` + PHP string manipulation, 4 copies | Medium | `app/Http/Controllers/dash/GrController.php` | Use `NumberSequenceService`. (`performance-report.md §8.2`) | (bundled with #2.3) |
| 10.7 | P1 | Likely N+1 in `UserController::index` (roles per user), `RoleController::index` (permissions per role), `ChallanController::index` (item count per challan) | Medium | views and controllers | Add `with('roles')` / `with('permissions')` / `withCount('items')`; eager-load. (`performance-report.md §2`) | 4h |
| 10.8 | P1 | No caching anywhere (offices, trucks, permissions, roles, dashboard counts) | Low | entire app | Add `Cache::remember(...)` for dropdowns + dashboard. (`performance-report.md §5`, `§9`) | 4h |
| 10.9 | P1 | `gatepasses.gr_no` UNIQUE + `gr::latest()->first()` unused dead query in `GatepassController::index` | Low | `app/Http/Controllers/dash/GatepassController.php` | Remove dead code. (`performance-report.md §8.4`) | 15 min |
| 10.10 | P1 | `User::officeall` is dead (1 extra query per call) | Low | `app/Models/User.php` | Delete method. (`performance-report.md §8.5`, `§1.3`) — covered in #1.7 | 5 min |
| 10.11 | P1 | `decimal` columns have no precision → MySQL defaults to `decimal(10,0)` which **truncates paise** | Medium (data loss) | all transactional tables | Apply migration #5.3. (`performance-report.md §6`, `database-reconstruction-report.md §13`) | 8h |
| 10.12 | P1 | `string` date columns prevent date-range index usage | Medium | `grs.copy_date`, `gatepasses.gp_date`, `frieghts.fm_date`, `challans.challan_date` | Apply migration #5.3. (`performance-report.md §6`) | (bundled) |
| 10.13 | P2 | No queue (`QUEUE_CONNECTION=sync`) | Low | `config/queue.php`, `.env` | Switch to `database` or `redis`; add `GenerateGrPdfJob`, `SendDailyReportJob`, `ReconcileBranchBalancesJob`, `ProcessPodUploadJob`. (`performance-report.md §10`) | 8h |
| 10.14 | P2 | No production CSS/JS minification | Low | `vite.config.js`, `package.json` | Run `npm run build`; commit `public/build/` or use CDN. (`performance-report.md §7`) | 2h |
| 10.15 | P2 | jQuery + AJAX without debouncing on search inputs | Low | `resources/views/admin/category/copies.blade.php` and others | Add `.debounce(300)`. | 2h |
| 10.16 | P2 | Hard-coded Windows logo path in print template | Low | `resources/views/admin/category/copies_print.blade.php:13` | Use `{{ asset('images/logo.png') }}`. (`performance-report.md §7`) — covered in #7.3 | (bundled) |
| 10.17 | P2 | No `<link rel="preload">` for fonts | Low | `resources/views/admin/layout/top.blade.php` | Add preload hints. (`performance-report.md §7`) | 1h |
| 10.18 | P3 | No read replica configured | Low | `config/database.php` | Add `'read' => [...]` and `'write' => [...]` keys. (`performance-report.md §11`) | 2h |
| 10.19 | P3 | No MySQL connection pooling tuning | Low | MySQL config | Tune `max_connections`, `proxy_read_timeout`. (`performance-report.md §11`) | 2h |
| 10.20 | P3 | No `barryvdh/laravel-debugbar` in dev | Low | dev composer | Add. (`performance-report.md §14`) | 15 min |
| 10.21 | P3 | No `spatie/laravel-query-monitor` for slow-query log | Low | dev composer | Add. (`performance-report.md §14`) | 15 min |

**Subtotal: 21 performance issues. 3 are P0 (the three biggest single wins).**

---

## 11. Recommended execution order

The work is grouped into 8 phases. Each phase is a single PR (per `laravel-upgrade-plan.md §4` "no bundling"). Each phase ends with a green test run.

### Phase 0 — Freeze & stabilize the legacy (P0 bug fixes only)

**Goal:** the app boots on Laravel 8 + the existing data (whatever the user can supply), with no runtime fatals.

| Step | Tasks | Est |
|---|---|---|
| 0.1 | `git tag v0.8.0-legacy`; add `.gitignore` entries; delete `Composer-Setup.exe` (per `laravel-upgrade-plan.md §Step 0`) | 1h |
| 0.2 | Fix #1.2 (`challan_iteam` use statement) | 5 min |
| 0.3 | Fix #1.4 (`gst_amount` ghost in Gatepass update) | 30 min |
| 0.4 | Fix #1.3 (remove `PostController` route + model + migration) | 30 min |
| 0.5 | Fix #1.5 (rename duplicate users migration) | 15 min |
| 0.6 | Fix #1.6 (implement `down()` on GR migration) | 5 min |
| 0.7 | Fix #1.7 (delete `User::officeall()`) | 5 min |
| 0.8 | Fix #7.3 (logo path) + #7.4 (`packeges` typo) | 35 min |
| 0.9 | Fix #7.16 (`pm.numberic` typo) | 5 min |
| 0.10 | Fix #7.24 (TruckdriverController redirect URL) | 5 min |
| 0.11 | Fix #9.2 (`APP_DEBUG=false`) + #9.3 (verify `.gitignore` contains `.env`) | 20 min |
| 0.12 | Add `.env.example` | 30 min |
| 0.13 | Generate `composer.lock` (`composer update --no-scripts`) | 15 min |
| **Phase 0 total** | | **~4h** |

**Gate:** the app boots on `php artisan serve`; `/dash/gr` loads; the broken routes are documented as known-broken (Freight, Challan, Posts).

### Phase 1 — Add the test safety net (mandatory before any upgrade)

| Step | Tasks | Est |
|---|---|---|
| 1.1 | Add one smoke test (`GET /` returns 200; login page renders) | 4h |
| 1.2 | Add one Feature test per business module asserting the route exists | 4h |
| **Phase 1 total** | | **~8h** |

**Gate:** `php artisan test` is green on the legacy code.

### Phase 2 — Laravel framework upgrade L8 → L9 → L10 → L11

| Step | Tasks | Est |
|---|---|---|
| 2.1 | L8 → L9 (cosmetic) | 4h |
| 2.2 | L9 → L10 (`setPasswordAttribute` → `hashed` cast) | 6h |
| 2.3 | L10 → L11 (port middleware to `bootstrap/app.php`, port console to `routes/console.php`, port exceptions, replace `Auth::routes()`, replace `Route::resource` string syntax with `::class`, drop `fruitcake/laravel-cors`, drop `laravelcollective/html`, fix `.env` keys `CACHE_DRIVER` → `CACHE_STORE`, `BCRYPT_ROUNDS`) | 16h |
| **Phase 2 total** | | **~26h** |

**Gate:** `php artisan test` is green on Laravel 11; `php artisan route:list` shows all 47 routes.

### Phase 3 — Modernize the code (rename, refactor, no behavior change)

| Step | Tasks | Est |
|---|---|---|
| 3.1 | Fix #1.10 (`APP_DEBUG`), #9.20 (drop `fruitcake/laravel-cors`), #9.19 (drop `laravelcollective/html`) | 30 min |
| 3.2 | Fix #7.25, #7.26, #7.27, #7.28 (delete dead files) | 30 min |
| 3.3 | Fix #7.33 (delete `webpack.mix.js`) | 5 min |
| 3.4 | Fix #7.6–#7.15 (column renames) — ship as part of migration #5.15 | (in #5.15) |
| 3.5 | Fix #7.17 (add `name` attributes to Freight Memo form) | 2h |
| 3.6 | Fix #7.23 (duplicate `from_dest` select) | 30 min |
| 3.7 | Fix #7.21, #7.22 (drop wrong UNIQUE on truckdrivers) | 1.5h |
| 3.8 | Implement Freight Memo `store/update/destroy` (currently stubs) | 16h |
| 3.9 | Fix #1.8 by generating `database/migrations_new/` (P0 migrations #5.1–#5.6) and a baseline seeder | 22h |
| 3.10 | Add the relationships from `relationship-map.md §8` to all models | 4h |
| 3.11 | Implement `NumberSequenceService` + use it everywhere #7.18, #7.19, #7.20 | 16h |
| 3.12 | Fix #7.29, #7.30 (build real dashboard) | 8h |
| **Phase 3 total** | | **~70h** |

**Gate:** the app boots; all 47 routes resolve; the 3 broken modules (Challan, Freight, Posts) are functional or removed.

### Phase 4 — Authorization & security P0/P1 (without redesigning)

| Step | Tasks | Est |
|---|---|---|
| 4.1 | Fix #9.1 (replace `Model::insert($request->all())` with `CreateChallanRequest` + `::create($request->validated())`) | 30 min |
| 4.2 | Fix #9.4 (replace `AdminMiddleware` count with cache + flag) | 1h |
| 4.3 | Fix #9.7 (add `throttle:5,1` to login) | 30 min |
| 4.4 | Fix #9.5 (verify CSRF tokens in browser; add meta tag) | 1h |
| 4.5 | Fix #9.8 (password complexity rule) | 4h |
| 4.6 | Fix #9.12 (regex for GSTIN / mobile / E-Way bill) | 2h |
| 4.7 | Fix #9.13, #9.14 (`SESSION_SECURE_COOKIE`, `BCRYPT_ROUNDS=12`) | 10 min |
| 4.8 | Fix #9.16, #9.17 (CSP + X-Frame-Options headers) | 2h |
| 4.9 | Add `GoodsReceiptPolicy`, `GatepassPolicy`, `ChallanPolicy`, `FreightMemoPolicy`, `TruckDriverPolicy` (#9.6) | 8h |
| **Phase 4 total** | | **~19h** |

**Gate:** all 22 security items in §9 are P2 or lower; `php artisan test --filter=Auth` is green.

### Phase 5 — Performance P0/P1 (one migration, then a few controllers)

| Step | Tasks | Est |
|---|---|---|
| 5.1 | Apply migration #5.1 (the 20 indexes) | 4h |
| 5.2 | Apply migration #5.3 (the type fixes) | 8h |
| 5.3 | Fix #10.1 (replace `Model::all()` with `->paginate(50)` in all 8 controllers) | 4h |
| 5.4 | Fix #10.7 (eager-load + `withCount` for N+1) | 4h |
| 5.5 | Fix #10.8 (cache offices, trucks, perms, roles, dashboard) | 4h |
| 5.6 | Add `barryvdh/laravel-debugbar` (dev) + verify index usage with `EXPLAIN` | 4h |
| **Phase 5 total** | | **~26h** |

**Gate:** `GET /dash/gr` is <100ms TTFB on a 1M-row dataset; `php artisan test` is green.

### Phase 6 — Reports P1 (the 4 operational reports)

| Step | Tasks | Est |
|---|---|---|
| 6.1 | Add `barryvdh/laravel-dompdf` + `maatwebsite/excel` (#8.18) | 4h |
| 6.2 | Daily GR Register (#8.1) | 8h |
| 6.3 | Pending deliveries (#8.2) | 4h |
| 6.4 | Branch-wise GR count (#8.3) | 4h |
| 6.5 | In-transit list (#8.4) | 4h |
| **Phase 6 total** | | **~24h** |

**Gate:** four reports render in PDF + CSV; smoke tests for each.

### Phase 7 — Modernize the schema (P1 migrations)

| Step | Tasks | Est |
|---|---|---|
| 7.1 | Apply migration #5.7 (`branch_id` FKs) | 8h |
| 7.2 | Apply migration #5.8 (`customers` table + FKs) | 8h |
| 7.3 | Apply migration #5.9 (`vendors` table) | 4h |
| 7.4 | Apply migration #5.14 (`freight_lines` table) | 4h |
| 7.5 | Apply migration #5.15 (rename typo columns) | 4h |
| 7.6 | Apply migration #5.16 (rename `frieghts` → `freight_memos`, `challan_iteams` → `challan_lines`) | 4h |
| 7.7 | Apply migration #5.17 (soft deletes + audit columns) | 8h |
| 7.8 | Apply migration #5.18 (drop `posts` table) | 1h |
| 7.9 | Update all models, controllers, views, and routes to use the new column / table names | 24h |
| 7.10 | Update Eloquent relationships per `relationship-map.md §8` | 4h |
| **Phase 7 total** | | **~69h** |

**Gate:** all 17 P1 migrations applied; existing routes still work; new relationships are queryable.

### Phase 8 — Long-term (deferred)

| Step | Tasks | Est |
|---|---|---|
| 8.1 | Apply migration #5.10 (split `truckdrivers` into `trucks` + `drivers` + `truck_assignments`) | 24h |
| 8.2 | Apply migration #5.11 (`pod_uploads`) + upload UI | 24h |
| 8.3 | Apply migration #5.12 + #5.13 (`payments`, `freight_payments`) + settlement workflow | 32h |
| 8.4 | Reports #8.5–#8.17 | 96h |
| 8.5 | API layer (Sanctum + JSON resources) | 24h |
| 8.6 | 2FA (Google Authenticator) | 8h |
| 8.7 | Customer / vendor portal | 40h |
| **Phase 8 total** | | **~248h** |

**Gate:** a complete modern ERP with POD, settlement, and reports.

### Totals

| Phase | Description | Effort |
|---|---|---|
| 0 | Freeze + P0 bug fixes | 4h |
| 1 | Test safety net | 8h |
| 2 | Framework upgrade L8 → L11 | 26h |
| 3 | Code modernization | 70h |
| 4 | Security P0/P1 | 19h |
| 5 | Performance P0/P1 | 26h |
| 6 | Reports P1 | 24h |
| 7 | Schema modernization P1 | 69h |
| 8 | Long-term (deferred) | 248h |
| **Total Phases 0–7** | (in scope for first modernization pass) | **~246h** |
| Phase 8 | (deferred) | 248h |

This matches the ~250h estimate in `final-discovery-report.md §1` and `refactoring-plan.md §11` (which lists 18 refactors totaling ~250h).

---

## 12. Cross-cutting concerns

### 12.1 Approval gates (from `final-discovery-report.md §13`)
- **Gate 1 (Discovery):** the 18 documents in `docs/` are reviewed and accurate. ← **This roadmap is the deliverable for Gate 1.**
- **Gate 2 (Database):** the schema in `database-reconstruction-report.md §14` matches the user's understanding. The proposed migrations in §5 are accepted.
- **Gate 3 (Critical bugs):** the 10 P0 items in §1 are acknowledged.
- **Gate 4 (Upgrade plan):** the L8 → L11 step-by-step in `laravel-upgrade-plan.md §4` is approved.
- **Gate 5 (Refactor plan):** `refactoring-plan.md` is approved (or trimmed).
- **Gate 6 (Security):** the P0/P1 items in §9 are acknowledged.
- **Gate 7 (Performance):** the P0/P1 items in §10 are acknowledged.
- **Gate 8 (Deployment):** `deployment-plan.md` matches reality.
- **Gate 9 (Open Questions):** the 25 open questions in `final-discovery-report.md §12` are answered.
- **Gate 10 (Scope):** Phases 0–7 are approved; Phase 8 is explicitly deferred.

### 12.2 Open questions blocking Phase 0
From `final-discovery-report.md §12`:
- Is the production database accessible? (Strongly preferred over reconstruction.)
- Should `branches` be introduced? (Recommended: yes.)
- Should `customers` and `vendors` be introduced? (Recommended: yes, defer to P1.)
- Should the legacy spellings (`frieghts`, `challan_iteams`, `nor_adress`) be renamed? (Recommended: rename everything, with legacy aliases.)
- Should one-to-many be restored on `gr_no`? (Almost certainly yes.)
- Should the GR numbering scheme `AA-NNNNN` be preserved? (Recommended: yes.)
- Is a real settlement workflow in scope? (Recommended: yes, deferred to Phase 8.)
- Is POD upload in scope? (Recommended: yes, deferred to Phase 8.)
- Are reports in scope? (Recommended: yes, Phase 6.)
- Should the legacy `Auth\LoginController` stack be migrated to Breeze? (Recommended: Breeze, deferred.)

### 12.3 What this roadmap does NOT cover
- Multi-company (multi-tenant) isolation (`erd.md §7`).
- Multi-currency (`erd.md §7`).
- Multi-language (`erd.md §7`).
- Customer / vendor portal (Phase 8.7).
- Real-time updates (no WebSockets planned).
- Mobile app.

---

## 13. Definition of done for the entire roadmap

- [ ] All 10 P0 items in §1 fixed.
- [ ] All 13 missing tables created.
- [ ] All 17 missing FKs added.
- [ ] All 20 indexes added.
- [ ] All 22 P0/P1 migrations applied.
- [ ] All 34 ghost fields resolved.
- [ ] All 18 reports (P1 subset) implemented.
- [ ] All 22 security items at P2 or lower.
- [ ] All 21 performance items at P2 or lower.
- [ ] Laravel 11 + PHP 8.2 + Spatie v6 + Sanctum in production.
- [ ] CI/CD on GitHub Actions (per `deployment-plan.md §4`).
- [ ] Daily backup + DR drill (per `deployment-plan.md §6`).
- [ ] Sentry + Laravel Pulse monitoring (per `deployment-plan.md §7`).
- [ ] All public endpoints covered by Feature tests.
- [ ] All public methods have PHPDoc summaries.
- [ ] `composer audit` and `npm audit` are clean.

---

## 14. Next step

**Pause for user approval.** This roadmap synthesizes all 18 docs. The team awaits the answer to §12.1 (10 approval gates) and §12.2 (10 blocking open questions). No code will be written, no migrations generated, no controllers touched, until the user approves the scope and answers the open questions.
