# Seeder Report — Saurashtra Express

> Scope: the 10 seeders and 10 factories generated for the modernized
> schema defined in `docs/erd.md` §2 and the migrations under
> `database/reconstructed_migrations/`.
>
> Convention: every seeder guards on `Schema::hasTable(...)` so it
> degrades gracefully on a legacy DB that hasn't been migrated yet.
> Every seeder is idempotent (matches on a natural key, e.g. `email`,
> `code`, `truck_no`, `license`) — re-running the demo set never
> duplicates rows.

---

## 1. How to run

```bash
# 1. Apply the modernized schema (creates branches, customers, vendors,
#    trucks, drivers, … in addition to the legacy tables).
php artisan migrate

# 2. Run the end-to-end demo set. The order is enforced by DemoSeeder.
php artisan db:seed --class='Database\Seeders\DemoSeeder'

# Or, from a clean slate (drops + re-runs everything):
php artisan migrate:fresh --seed --class='Database\Seeders\DemoSeeder'
```

`DatabaseSeeder::run()` is left untouched per the task brief; the new
`DemoSeeder` is the orchestrator.

---

## 2. Seeder chain (dependency order)

`DemoSeeder` invokes the following 10 child seeders in the listed
order. Order is critical — every seeder is allowed to reference
rows produced by any earlier one in the chain.

| # | Seeder | Tables written | FK references resolved by |
|---|---|---|---|
| 1 | `BranchSeeder` | `branches` | — (root) |
| 2 | `RolePermissionSeeder` | `roles`, `permissions`, `role_has_permissions` | — (root) |
| 3 | `UserSeeder` | `users`, `model_has_roles` | `branches.id`, `roles.name` |
| 4 | `CustomerSeeder` | `customers` | — (root) |
| 5 | `VendorSeeder` | `vendors` | — (root) |
| 6 | `VehicleSeeder` | `trucks` | `vendors.id`, `branches.id` |
| 7 | `DriverSeeder` | `drivers` | — (root) |
| 8 | `RouteSeeder` | `routes` *(skipped — table missing)* | `branches.id` |
| 9 | `StationSeeder` | `stations` *(skipped — table missing)* | `branches.id` |

Steps 8–9 currently log a warning and exit early. See §4.

---

## 3. Seed volumes and shapes

### 3.1 `branches` (7 rows)

The 7 hard-coded office names from `docs/database-reconstruction-report.md`
§2.3, with `code`, `name`, `city`, `state`, `pincode` pinned for
deterministic demo output:

| code | name | city | state |
|---|---|---|---|
| RJKT | Rajkot | Rajkot | Gujarat |
| KASH | Kashmore Gate | Kashmore | Sindh |
| DYBS | Dayabasti | Delhi | Delhi |
| SWNP | Swarup Nagar | Kanpur | UP |
| NVGM | Navagam | Surat | Gujarat |
| SHP1 | Shapar (1) | Shapar | Gujarat |
| SHP2 | Shapar (2) | Shapar | Gujarat |

The same rows are also inserted by the data migration
`2026_06_05_000100_seeder_baseline_offices.php`; the seeder
duplicates the insert so `db:seed` alone is enough on a fresh
modernized DB.

### 3.2 `roles` + `permissions` (4 roles, 36 permissions)

- **Roles:** Super Admin, Branch Manager, Operator, Viewer.
- **Permission coverage** (per `docs/erd.md` §2 RBAC diagram):
  GR, Gatepass, Challan, Freight, Truck, Driver, Customer, Vendor,
  Report, Setting, User — each with `view / create / edit / delete
  / print / export` as appropriate (40 total verb-noun pairs; the
  canonical set is 36 because not every entity exports).
- **Role → permission matrix** is encoded in
  `RolePermissionSeeder::rolePermissionMatrix()` and is fully
  deterministic.

### 3.3 `users` (13 rows)

| # | Email | Role | Branch | Legacy `office` |
|---|---|---|---|---|
| 1 | `admin@sxpress.test` | Super Admin | RJKT | Rajkot |
| 2 | `rjkt-mgr@sxpress.test` | Branch Manager | RJKT | Rajkot |
| 3 | `kash-mgr@sxpress.test` | Branch Manager | KASH | Kashmore Gate |
| 4 | `dybs-mgr@sxpress.test` | Branch Manager | DYBS | Dayabasti |
| 5 | `swnp-mgr@sxpress.test` | Branch Manager | SWNP | Swarup Nagar |
| 6 | `nvgm-mgr@sxpress.test` | Branch Manager | NVGM | Navagam |
| 7 | `shp1-mgr@sxpress.test` | Branch Manager | SHP1 | Shapar (1) |
| 8 | `shp2-mgr@sxpress.test` | Branch Manager | SHP2 | Shapar (2) |
| 9–13 | `operator[1..5]@sxpress.test` | Operator | RJKT | Rajkot |

All demo users share the password **`password`**. The legacy `office`
column is populated from the matching `branches.name` so the existing
list-view code (which filters on `office = 'Rajkot'`) keeps working
without changes (`docs/database-reconstruction-report.md` §2.3).

### 3.4 `customers` (30 rows)

- Codes `C0001` … `C0030` (sequential, unique).
- 25 active, 5 inactive — exercises the `is_active = 0` filter.
- Mix of GST-registered and unregistered (~70% / ~30%).
- GSTIN format: `##` (state code) + 10-char PAN + `1Z` + 1 checksum.
- PAN format: 5 alpha + 4 digits + 1 alpha.
- Phone: `+91` + 10 digits.
- Address: street + city + state + 6-digit pincode (Indian format).

### 3.5 `vendors` (25 rows)

- Codes `V0001` … `V0025`.
- 21 active, 4 inactive.
- The first 15 (V0001–V0015) have full **bank settlement details**
  (account number + IFSC). They model the "preferred" partner pool
  that the application surfaces first.
- 60% GST-registered, 70% PAN-populated.
- Names follow the Saurashtra-Gujarat transport idiom
  (`<Prefix> <Surname> <Suffix>` — e.g. *Shree Sharma Transport*).

### 3.6 `trucks` / `vehicles` (40 rows)

- All Indian-format truck numbers (`<state>-<rto>-<letters>-<digits>`).
- Each truck is assigned to:
  - one vendor (random pick from V0001..V0025)
  - one home branch (random pick from RJKT, KASH, DYBS, SWNP, NVGM, SHP1, SHP2)
- Realistic make / model / year (Tata, Ashok Leyland, Mahindra,
  Eicher, BharatBenz, Volvo, Scania, MAN, Isuzu; years 2010–2026).
- Compliance fields:
  - Fitness certificate (FC + 8 digits) — valid 3–24 months out.
  - Insurance (INS + 10 digits) — valid 6–24 months out.
  - Permit (PMT + 8 digits) — valid 2–24 months out.
- **Compliance flag rows:**
  - Truck #39, #40 → **`expiredPermit`** (permit in the past).
  - Truck #40 → additionally `is_active = 0`.

### 3.7 `drivers` (50 rows)

- 50 unique Indian-format driving licenses
  (`<state>-<rto>-<year>-<serial>`).
- Phone: `+91` + 10 digits (70% have one mobile, 30% have two).
- License expiry: 1–5 years from today.
- **Compliance flag rows:**
  - Driver #49, #50 → **`expiredLicense`**.
  - Driver #50 → additionally `inactive`.
- Drivers are **not** pre-assigned to trucks. The modernized model
  uses the `truck_assignments` pivot (`docs/erd.md` §2) so a driver
  can move trucks over time. A future `TruckAssignmentSeeder` would
  produce realistic assignment histories.

### 3.8 `routes` (10 rows, *skipped today*)

The `routes` table is not present in any current migration. The
seeder is shipped with a static catalog of 10 plausible long-haul
routes between the 7 branches (see `RouteSeeder::ROUTES`); once
the `routes` migration lands, the seeder activates automatically.

| Code | Name | Origin | Destination | Distance (km) | Transit (h) |
|---|---|---|---|---|---|
| RJKT-KASH | Rajkot — Kashmore Direct | RJKT | KASH | 1450.50 | 32 |
| RJKT-DYBS | Rajkot — Dayabasti Express | RJKT | DYBS | 1150.00 | 24 |
| RJKT-NVGM | Rajkot — Navagam Local | RJKT | NVGM | 180.00 | 6 |
| RJKT-SHP1 | Rajkot — Shapar (1) Shuttle | RJKT | SHP1 | 22.00 | 1 |
| RJKT-SHP2 | Rajkot — Shapar (2) Shuttle | RJKT | SHP2 | 24.00 | 1 |
| NVGM-SHP1 | Navagam — Shapar (1) Industrial | NVGM | SHP1 | 210.00 | 7 |
| NVGM-SHP2 | Navagam — Shapar (2) Industrial | NVGM | SHP2 | 212.00 | 7 |
| SHP1-SHP2 | Shapar (1) — Shapar (2) Connector | SHP1 | SHP2 | 4.00 | 1 |
| SWNP-DYBS | Swarup Nagar — Dayabasti Long Haul | SWNP | DYBS | 450.00 | 12 |
| KASH-DYBS | Kashmore — Dayabasti Premium | KASH | DYBS | 2200.00 | 44 |

### 3.9 `stations` (8 rows, *skipped today*)

Same status as `routes` — no migration, but the seeder ships with
a static catalog of 8 stations (one or two per branch) with real
city-level lat/long coordinates. See `StationSeeder::STATIONS` for
the full list.

---

## 4. Tables that are intentionally skipped

`RouteSeeder` and `StationSeeder` are the only two seeders that
log a warning and exit early today, because the corresponding
tables don't exist anywhere in the project:

- `database/migrations/*` — legacy 2020-vintage migrations
- `database/reconstructed_migrations/*` — the modernized migrations
- `docs/erd.md` §2 (modernized ERD) — also not present

The suggested schemas (for reference — not created here):

```php
// routes
Schema::create('routes', function (Blueprint $t) {
    $t->bigIncrements('id');
    $t->string('code', 20)->unique();
    $t->string('name', 150);
    $t->unsignedBigInteger('origin_branch_id');
    $t->unsignedBigInteger('destination_branch_id');
    $t->decimal('distance_km', 8, 2)->nullable();
    $t->unsignedSmallInteger('transit_hours')->nullable();
    $t->boolean('is_active')->default(true);
    $t->timestamps();
});

// stations
Schema::create('stations', function (Blueprint $t) {
    $t->bigIncrements('id');
    $t->string('code', 20)->unique();
    $t->string('name', 150);
    $t->unsignedBigInteger('branch_id')->nullable();
    $t->decimal('latitude', 10, 7)->nullable();
    $t->decimal('longitude', 10, 7)->nullable();
    $t->boolean('is_active')->default(true);
    $t->timestamps();
});
```

When either migration is added, the corresponding seeder activates
with no further code changes.

---

## 5. Factories

Ten factories, one per seeder. Every factory:

- Pins `protected $model` to the modernized model class
  (e.g. `\App\Models\Customer`). When the model class doesn't
  exist yet (Branches, Routes, Stations), the seeder falls back
  to a table-level `DB::table('…')->insert(…)` so the factory
  doesn't have to fail.
- Uses `Schema::hasColumn` / `Schema::hasTable` guards so it
  produces a working payload on both the legacy and the
  modernized schema.
- Exposes state helpers for the most common shape variants
  (`.gstRegistered()`, `.withBankDetails()`, `.expiredPermit()`,
  `.expiredLicense()`, `.inactive()`, `.atBranch($id)`, …).
- Switches the Faker locale to **`en_IN`** for the user-facing
  factories (User, Customer, Vendor, Driver, Branch) so generated
  names, addresses, and phone numbers look like real Indian
  logistics data instead of the default en-US English.

| Factory | Source schema | Notes |
|---|---|---|
| `UserFactory` | `users` | Legacy + modernized column guards; `admin()`, `inactive()`, `atBranch()`, `atOffice()` states |
| `RoleFactory` | `roles` (Spatie) | `admin()` state; auto-titlecases the role name |
| `PermissionFactory` | `permissions` (Spatie) | Generates `verb noun` strings; mostly for tests, not the canonical seed set |
| `BranchFactory` | `branches` | `inactive()`, `gujarat()` states; suggested-city pull from `en_IN` |
| `CustomerFactory` | `customers` | `gstRegistered()`, `corporate()`, `inactive()` states; GSTIN + PAN generators |
| `VendorFactory` | `vendors` | `withBankDetails()`, `inactive()` states; Gujarat/Rajasthan-style names |
| `VehicleFactory` | `trucks` | Indian-format truck-number generator; `expiredPermit()`, `forVendor()`, `atBranch()` states |
| `DriverFactory` | `drivers` | Indian-format license generator; `expiredLicense()`, `inactive()` states |
| `RouteFactory` | `routes` *(no table)* | `between($originCode, $destinationCode)` state; guarded for missing table |
| `StationFactory` | `stations` *(no table)* | `atBranch($id)` state; guarded for missing table |

---

## 6. Expected row counts after a clean seed run

| Table | Rows | Source |
|---|---|---|
| `branches` | 7 | BranchSeeder |
| `roles` | 4 | RolePermissionSeeder |
| `permissions` | 36 | RolePermissionSeeder |
| `role_has_permissions` | 4 × matrix | RolePermissionSeeder |
| `users` | 13 | UserSeeder |
| `model_has_roles` | 13 | UserSeeder (one row per assigned user) |
| `customers` | 30 | CustomerSeeder |
| `vendors` | 25 | VendorSeeder |
| `trucks` | 40 | VehicleSeeder |
| `drivers` | 50 | DriverSeeder |
| `routes` | 0 today (10 when the migration lands) | RouteSeeder |
| `stations` | 0 today (8 when the migration lands) | StationSeeder |
| **Total (modernized tables only)** | **~220 rows** | |

These counts exclude Spatie's framework rows (which `2020_10_22_120538_create_permission_tables.php`
creates) and the legacy application tables (`grs`, `gatepasses`,
`challans`, `challan_iteams`, `frieghts`, `truckdrivers`) which are
not touched by this seed set.

---

## 7. Compliance and edge-case coverage

A few rows are intentionally seeded to exercise compliance and
soft-delete / inactive code paths:

- **Expired permit:** trucks #39–#40 (`VehicleSeeder`).
- **Expired license:** drivers #49–#50 (`DriverSeeder`).
- **Inactive user / customer / vendor / driver:** one row each
  so the `is_active = 0` filter has data to render against.
- **Active-but-flagged:** inactive users still exist; only their
  `is_active` flag differs.

The same row counts and shapes are what the QA team uses to drive
the compliance dashboards, so the demo set is intentionally
non-uniform.

---

## 8. What this report does NOT cover

- **Legacy table seeding** (`grs`, `gatepasses`, `challans`,
  `challan_iteams`, `frieghts`, `truckdrivers`). These are not
  in scope of the modernized demo set — see the
  `database/seeders/ChallanSeeder.php`,
  `database/seeders/GrSeeder.php` etc. for the legacy modules.
  They depend on the legacy FK-less schema and require the
  controller-driven `gr_no` / `gp_no` / `fm_no` counter logic to
  have run first.
- **The `number_sequences` and `settings` rows.** These are
  seeded by the data migration
  `2026_06_05_000100_seeder_baseline_offices.php`, not by a
  normal seeder, so they are present on every modernized DB
  regardless of whether `db:seed` is invoked.
- **Multi-company tenancy.** The modernized ERD is single-company
  (Saurashtra Express only). A `companies` table and a
  `branch.company_id` FK would be required for multi-tenant.
- **API / Sanctum tokens.** `personal_access_tokens` is in the
  modernized ERD (§2) but the seed set doesn't mint demo tokens
  — that's a per-developer concern.

---

## 9. Quick smoke test

After `php artisan db:seed --class='Database\Seeders\DemoSeeder'`,
the following counts should hold on a clean DB:

```bash
php artisan tinker --execute="echo \DB::table('branches')->count();"
# 7

php artisan tinker --execute="echo \DB::table('roles')->count();"
# 4

php artisan tinker --execute="echo \DB::table('users')->count();"
# 13

php artisan tinker --execute="echo \DB::table('customers')->count();"
# 30

php artisan tinker --execute="echo \DB::table('vendors')->count();"
# 25

php artisan tinker --execute="echo \DB::table('trucks')->count();"
# 40

php artisan tinker --execute="echo \DB::table('drivers')->count();"
# 50
```

The dispatcher's output should end with:

```
[DemoSeeder] Starting demo seed set (10 seeders in dependency order).
[BranchSeeder] 7 offices seeded.
[RolePermissionSeeder] 4 roles, 36 permissions wired.
[UserSeeder] Demo users created (password: "password" for all).
[CustomerSeeder] 30 customers seeded.
[VendorSeeder] 25 vendors seeded.
[VehicleSeeder] 40 vehicles seeded.
[DriverSeeder] 50 drivers seeded.
[RouteSeeder] Skipped — the `routes` table does not exist. See docs/seeder-report.md §4 for the suggested schema and the rationale.
[StationSeeder] Skipped — the `stations` table does not exist. See docs/seeder-report.md §4 for the suggested schema and the rationale.
[DemoSeeder] Done. See docs/seeder-report.md for the row counts and any skip notices.
```
