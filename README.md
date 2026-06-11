# SXpress — Transport & Logistics ERP

**Saurashtra Express (SXpress)** is a full-featured transport management system built for Indian truck transport companies. It manages the complete goods movement lifecycle — from booking (GR) to delivery (POD) to truck owner settlement (Freight Memo) — across multiple branch offices.

---

## Features

### Core Modules
- **GR (Goods Receipt / Bilty)** — Consignment booking with auto-numbering per branch prefix
- **Gatepass** — Authorizes goods dispatch, links multiple GRs
- **Challan (Delivery Challan)** — Truck trip manifest with GR items auto-populated
- **Freight Memo** — Truck owner settlement linked to Challan (Indian transport standard)
- **POD (Proof of Delivery)** — File upload with auto status transition
- **TO-PAY Collection** — Track and collect pending payments with aging

### Operations
- **Dashboard** — Real-time KPIs, charts (revenue trend, status distribution, weekly comparison)
- **Reports** — GR Register, Daily Booking, Revenue, Pending TO-PAY, Pending Delivery, Pending POD, Branch Performance, Vehicle, Driver — all with CSV export
- **Office Impersonation** — SuperAdmin can work as any branch with one click
- **Public Tracking** — Track shipment by GR number without login

### Administration
- **Multi-Branch** — 7 offices with independent GR serial numbering
- **Role-Based Access** — SuperAdmin, Admin, Manager, Staff, Viewer
- **User Management** — Soft-delete, activate/deactivate, branch assignment
- **Branch Management** — GR prefix lock, deletion guards, rename cascade

---

## Tech Stack

| Component | Version |
|-----------|---------|
| Framework | Laravel 10.50.2 |
| PHP | 8.2 |
| Database | MariaDB 10.4 / MySQL 8 |
| Frontend | Bootstrap 4 + Chart.js |
| Auth | Spatie Laravel Permission |
| Admin Template | Sufee Admin |

---

## Installation

```bash
# Clone
git clone https://github.com/vivekpurohit222/sxpress-new.git
cd sxpress-new

# Install dependencies
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
DB_DATABASE=sxpress
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate

# Seed roles, permissions, and test data
php artisan db:seed

# Storage link (for POD uploads)
php artisan storage:link

# Run server
php artisan serve
```

---

## Default Login

| Role | Email | Password |
|------|-------|----------|
| SuperAdmin | superadmin@sxpress.com | password |

---

## Roles & Permissions

```
SuperAdmin  → Full access, all branches, settings, impersonation
Admin       → Branch-level operations (no settings access)
Manager     → Create/edit GR, Challan, Gatepass, Freight Memo, Reports
Staff       → Day-to-day GR/Challan/Gatepass operations
Viewer      → Read-only access
```

---

## Indian Transport Workflow

```
1. Customer brings goods → GR Created (Bilty/Lorry Receipt)
2. Goods loaded on truck → Gatepass Created (dispatch authorization)
3. Truck departs → Challan Created (trip manifest)
4. Goods delivered → POD Uploaded (proof of delivery)
5. Truck owner paid → Freight Memo Created (settlement)
```

Each GR follows a state machine:
```
[created] → [dispatched] → [in_transit] → [delivered] → [closed]
```

---

## Branch & GR Numbering

Each branch has a unique 2-letter prefix:
- Rajkot → AA-00001, AA-00002, ...
- Navagam → NV-00001, NV-00002, ...
- Shapar → S1-00001, S1-00002, ...

SuperAdmin assigns prefixes and starting serial numbers per branch.

---

## Office Impersonation

SuperAdmin can "impersonate" any branch office from the header bar. When impersonating:
- All data is scoped to that branch
- GR creation uses that branch's prefix
- Dashboard shows that branch's KPIs
- Reports show that branch's data
- Click "Stop" to return to all-office view

---

## Testing

```bash
# Run all tests
php artisan test

# Current: 205 tests, 489 assertions, 0 failures
```

Test coverage:
- Authentication (login, throttle, session, password complexity)
- RBAC (role access, permission enforcement)
- User Management (CRUD, soft-delete, toggle active)
- Branch Management (prefix lock, deletion guards)
- GR Module (numbering, office isolation, status transitions)
- Gatepass (multi-GR linking, status reversal)
- Challan (GR data auto-fill, item management)
- Freight Memo (challan-linked, balance calculation)
- POD (upload, download, status transition)
- Dashboard (KPIs, charts, role-based widgets)
- Reports (all 11 reports, CSV export, filters)

---

## Project Structure

```
app/
├── Http/Controllers/
│   ├── dash/               # GR, Challan, Gatepass, Freight, Dashboard
│   ├── Auth/               # Login, Register, Password Reset
│   ├── SuperAdmin/         # Serial assignment
│   ├── ReportController    # All reports with CSV export
│   ├── BranchController    # Branch CRUD
│   └── UserController      # User management
├── Models/                 # Eloquent models with relationships
├── Services/               # GrWorkflowService (state machine)
├── Traits/                 # OfficeScopeTrait, Auditable
└── Events/                 # GRCreated, GRDispatched, PODUploaded

resources/views/
├── admin/
│   ├── layout/             # Master, navigation, header
│   ├── category/           # GR, Challan, Gatepass, Freight, Branch forms
│   ├── reports/            # 11 report views
│   └── dashboard.blade.php
└── auth/                   # Login, register

tests/Feature/              # 205 feature tests
docs/                       # Logic skill, implementation log
```

---

## Key Design Decisions

1. **Office isolation by default** — Every query scoped by `office` column. SuperAdmin bypasses.
2. **Atomic number generation** — `DB::transaction()` + `lockForUpdate()` prevents race conditions.
3. **Challan-linked Freight Memo** — Follows real Indian transport workflow, not generic invoicing.
4. **Session-based impersonation** — No database changes, instant office switching.
5. **Server-side totals** — Client calculations are never trusted; always recalculated server-side.

---

## License

Private / Proprietary — Saurashtra Express Logistics.
