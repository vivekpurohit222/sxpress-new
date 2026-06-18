# SXpress — Development Log (18 June 2026)

## Summary

Complete system overhaul in a single day — redesigned authentication, permissions, serial numbering, transport workflow, dashboard, and GR form. The system went from a Spatie-based role system to a custom lightweight permission engine, with a full booking-to-delivery workflow and financial-year-based serial numbers.

---

## 1. Roles & Permissions Redesign

### What was removed:
- Spatie `laravel-permission` package (no longer used in code)
- Old 5-role system (SuperAdmin, Admin, Manager, Staff, Viewer)
- RoleController and PermissionController
- All `@role()` and `@hasanyrole()` Blade directives

### What was built:
- **3 user types** stored as `users.role` column: `super_admin`, `branch_manager`, `agent`
- **`branch_permissions` table** — controls what modules a branch (and its manager) can access
- **`user_permissions` table** — controls what modules an agent can access
- **`CheckPermission` middleware** — `permission:gr`, `permission:challan`, etc.
- **`CheckRole` middleware** — backward-compatible `role:SuperAdmin` for settings routes
- **`User::can_access('module')`** method — single permission check for all roles

### 6 Module Permissions:
| Key | Module |
|-----|--------|
| `gr` | GR (Goods Receipt) |
| `challan` | Challan |
| `freight_memo` | Freight Memo |
| `import_challan` | Import Challan |
| `gate_pass` | Gate Pass |
| `dds` | DDS (Daily Delivery Statement) |

### Login:
- Super Admin: `admin@sxpress.com` / `password`
- Branch managers: created per branch
- Agents: created by super admin

---

## 2. Serial Number System

### Format:
```
26/27 - 000001
```
- `26/27` = Financial Year (April 2026 – March 2027)
- `000001` = 6-digit zero-padded serial
- Resets every April 1

### How it works:
- Super Admin assigns a number **range** to each branch per module (e.g. Rajkot GR: 000001–100000)
- Each branch counts independently within its range
- **Collision detection** prevents overlapping ranges
- **Dashboard warning** when ≤ 50 numbers remaining
- **Blocks creation** if limit reached
- Atomic row-locking prevents race conditions

### Configuration:
- Branch Create/Edit form → Serial Number Ranges section
- Settings → GR Serial page → view/edit all assignments

### Tables:
- `branch_serials` (branch_id, module, fy_year, range_start, range_end, current_value)

### Controllers updated:
- GrController, ChallanController, FreightController, GatepassController — all use `SerialNumberService`

---

## 3. Transport Workflow (Booking → Delivery)

### Status Flow:
```
GR:      created → loaded → in_transit → delivered
Challan: created → in_transit → completed
```

### Booking Module (origin office):
| Step | Module | Action | GR Status | Challan Status |
|------|--------|--------|-----------|----------------|
| 1 | GR | Book goods | `created` | — |
| 2 | Challan | Load onto truck | `loaded` | `created` |
| 3 | Freight Memo | Settle truck owner | — | `in_transit` |

### Delivery Module (destination office):
| Step | Module | Action | GR Status | Challan Status |
|------|--------|--------|-----------|----------------|
| 4 | Import Challan | Receive shipment | `in_transit` | `completed` |
| 5 | Gate Pass | Release to consignee | `delivered` | — |
| 6 | DDS | View daily deliveries | — | — |

### Status enforcement:
- Challan only picks GRs with `status='created'`
- Freight Memo only links challans with `status='created'` or `'loaded'`
- Import only works on `status='in_transit'` challans
- Gate Pass only picks GRs with `status='in_transit'`
- Delete gatepass reverts GR to `in_transit`

### New Controllers:
- `ImportChallanController` — list + import action
- `DdsController` — date-filtered gatepass listing

---

## 4. Dashboard Redesign

### Old:
- KPI cards, charts, recent GRs table

### New:
- Clean module cards in two sections: **Booking** + **Delivery**
- Each card shows: icon, title, description, today's count, quick-create button
- **Serial number warnings** at top when ≤ 50 remaining
- **Accounting placeholder** (Coming Soon)
- Cards only visible if user has permission for that module

---

## 5. GR Form Redesign

### Old form:
- Consignor/Consignee with address fields
- Paid/To-Pay as separate checkboxes
- Basic charges: freight, surcharge, c_r, other, bc_amount
- Manual total calculation

### New form:
- **Payment Type** at top (TO PAY / PAID radio)
- **GST auto-fetch** — type GST number → auto-fills party name + rates from DB
- **"+ New" buttons** for consignor/consignee (links to create page)
- **No address fields** (removed)
- **Rate system** — pre-configured per consignor/consignee:
  - Rate per Nug (₹/package)
  - Rate per Kg (₹/weight)
- **Rate Type radio** — By Nugs / By Weight
- **Auto freight** — `nugs × rate` or `weight × rate` (editable)
- **New charge fields**: Surcharge, Labour, DD (Door Delivery), Local Charge, BC, Other
- **Bill Amount** — declared goods value (separate, required)
- **Real-time total** — auto-calculated as you type
- **Method "Other"** — shows text input to specify custom method type
- **Form submission guard** — prevents double-submit

### Rate logic:
- **PAID** → rate from consignor (sender pays)
- **TO PAY** → rate from consignee (receiver pays)
- Rate pre-fills from party record but is editable

### Database changes:
- Added to `customers`: `rate_per_nug`, `rate_per_kg`
- Added to `consignors`: `rate_per_nug`, `rate_per_kg`
- Added to `consignees`: `rate_per_nug`, `rate_per_kg`
- Added to `grs`: `labour`, `dd`, `local_charge`, `rate_type`, `rate`

---

## 6. Branch Management Updates

### Old:
- GR Prefix field (2-letter code like AA, CG)
- Basic branch details

### New:
- **Removed GR Prefix** — serial numbers now use FY format
- **Branch Manager section** — auto-creates manager user on branch creation
- **Module Permissions** — checkboxes for 6 modules
- **Serial Number Ranges** — start/end for each module with collision detection
- **Branch View** shows serial ranges table with used/remaining counts
- **Branch List** shows GR Range column instead of old prefix

---

## 7. Other Changes

### Global Uppercase:
- `UppercaseInput` middleware — converts all text input to UPPERCASE before controller
- CSS `text-transform: uppercase` on all inputs
- Excluded: passwords, emails, CSRF tokens

### Removed:
- Station routes and sidebar link
- Route routes and sidebar link
- Old Spatie role/permission routes

### Sidebar:
- Reorganized into **Booking** / **Delivery** sections
- Each link gated with `can_access('module')`
- Reports (super_admin + branch_manager only)
- Settings (super_admin only)

### GR status enum fixed:
- `ALTER TABLE grs` to include `'loaded'` in enum

### Gatepass:
- `gp_no` column changed from INT to VARCHAR(20) (for `26/27 - 000001` format)
- Delete now reverts GR to `in_transit` (not `created`)

---

## 8. Files Created/Modified

### New files:
```
app/Http/Middleware/CheckPermission.php
app/Http/Middleware/CheckRole.php
app/Http/Middleware/UppercaseInput.php
app/Http/Controllers/dash/ImportChallanController.php
app/Http/Controllers/dash/DdsController.php
app/Models/BranchPermission.php
app/Models/UserPermission.php
app/Models/BranchSerial.php
app/Services/SerialNumberService.php
database/migrations/2026_06_18_100000_redesign_permissions_system.php
database/migrations/2026_06_18_200000_create_branch_serials_table.php
database/migrations/2026_06_19_000001_add_rate_columns_to_customers.php
database/migrations/2026_06_19_000002_add_gr_charge_columns.php
database/migrations/2026_06_19_000003_add_rate_columns_to_consignors_and_consignees.php
database/seeders/SuperAdminSeeder.php
resources/views/admin/category/import_challan/index.blade.php
resources/views/admin/category/dds/index.blade.php
```

### Major rewrites:
```
app/Models/User.php (removed Spatie, added can_access)
app/Http/Controllers/BranchController.php (manager + permissions + serials)
app/Http/Controllers/UserController.php (agent CRUD)
app/Http/Controllers/dash/GrController.php (new form handling + serial)
app/Http/Controllers/dash/ChallanController.php (status enforcement + serial)
app/Http/Controllers/dash/FreightController.php (challan status check + serial)
app/Http/Controllers/dash/GatepassController.php (in_transit filter + serial)
app/Http/Controllers/SuperAdmin/SerialController.php (serial assignment page)
app/Traits/OfficeScopeTrait.php (no Spatie dependency)
routes/web.php (permission middleware for all modules)
resources/views/admin/layout/navigation.blade.php (Booking/Delivery sections)
resources/views/admin/dashboard.blade.php (module cards)
resources/views/admin/category/copies.blade.php (GR create — full redesign)
resources/views/admin/category/copies_edit.blade.php (GR edit — matching redesign)
resources/views/admin/category/Branch/branch.blade.php (serial ranges + manager)
resources/views/admin/category/Branch/branch_edit.blade.php (serial ranges + manager)
resources/views/admin/category/Branch/branch_list.blade.php (GR Range column)
resources/views/admin/category/Branch/branch_view.blade.php (serial table)
resources/views/users/create.blade.php (agent + permissions)
resources/views/users/edit.blade.php (agent + permissions)
resources/views/users/index.blade.php (agent list)
```

---

## 9. Test Results

- **131 tests passing** (core workflow)
- 11 failures in older test files that reference deprecated patterns (to be cleaned up)
- Full audit: 84/84 system checks passed

---

## 10. Git

- Pushed to `https://github.com/vivekpurohit222/sxpress-new` (main branch)
- Commit: `feat: complete roles/permissions redesign + full booking/delivery workflow`
