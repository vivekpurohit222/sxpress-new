# Saurashtra Express — Transport ERP

A Laravel 10 ERP system for **Saurashtra Express**, a transport logistics company operating across multiple branches in Gujarat, India.

## Login

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@sxpress.com | password |
| Branch Manager | (created per branch) | password |
| Agent | (created by super admin) | password |

## System Overview

The system manages the full lifecycle of goods transport — from booking at the origin office to delivery at the destination office.

### Workflow

```
ORIGIN OFFICE (Booking)              DESTINATION OFFICE (Delivery)
─────────────────────────            ────────────────────────────
1. GR (Goods Receipt)                4. Import Challan
   status: created                      challan → completed
                                        GRs → in_transit
2. Challan
   picks GRs → loaded               5. Gate Pass
   assigns truck + driver               picks GRs → delivered
                                        releases to consignee
3. Freight Memo
   challan → in_transit              6. DDS (Daily Delivery Statement)
   settles truck owner                  report of today's deliveries
```

### Status Flow

| Document | Statuses |
|----------|----------|
| GR | `created` → `loaded` → `in_transit` → `delivered` |
| Challan | `created` → `in_transit` → `completed` |
| Gate Pass | `created` → `released` → `delivered` |

## Roles & Permissions

No Spatie package. Roles stored as a `role` column on the `users` table.

| Role | Description |
|------|-------------|
| `super_admin` | Full access. One user, no branch. Manages everything. |
| `branch_manager` | Created automatically with each branch. Scoped to their branch. |
| `agent` | Created by super admin. Scoped to their branch. |

### Module Permissions

Six sub-modules, controlled via two tables:

- `branch_permissions` — what a **branch** (and its manager) can access
- `user_permissions` — what an **agent** can access

| Permission Key | Module |
|----------------|--------|
| `gr` | GR (Goods Receipt) |
| `challan` | Challan |
| `freight_memo` | Freight Memo |
| `import_challan` | Import Challan |
| `gate_pass` | Gate Pass |
| `dds` | DDS (Daily Delivery Statement) |

Middleware: `permission:gr`, `permission:challan`, etc. in `routes/web.php`.

## Tech Stack

- **Framework:** Laravel 10
- **PHP:** 8.1+
- **Database:** MySQL 8
- **Frontend:** Blade + Bootstrap 4 + FontAwesome
- **Auth:** Custom role column (no Spatie)

## Project Structure

```
app/
├── Http/Controllers/
│   ├── dash/                    # Operational modules
│   │   ├── GrController.php
│   │   ├── ChallanController.php
│   │   ├── FreightController.php
│   │   ├── ImportChallanController.php
│   │   ├── GatepassController.php
│   │   ├── DdsController.php
│   │   └── DashboardController.php
│   ├── BranchController.php     # Branch + manager + permissions CRUD
│   ├── UserController.php       # Agent CRUD
│   └── Middleware/
│       ├── CheckPermission.php  # permission:X middleware
│       └── CheckRole.php        # role:SuperAdmin middleware
├── Models/
│   ├── User.php                 # has can_access() method
│   ├── Gr.php
│   ├── challan.php
│   ├── Freight.php              # table: frieghts
│   ├── gatepass.php
│   ├── Branch.php
│   ├── BranchPermission.php
│   └── UserPermission.php
├── Traits/
│   └── OfficeScopeTrait.php     # Branch scoping for all controllers
routes/
└── web.php                      # All routes with permission middleware
resources/views/
├── admin/
│   ├── layout/
│   │   ├── master.blade.php
│   │   └── navigation.blade.php # Sidebar with Booking/Delivery sections
│   ├── dashboard.blade.php      # Module cards dashboard
│   └── category/
│       ├── challan/
│       ├── Gatepass/
│       ├── FrieghtMemo/
│       ├── import_challan/
│       ├── dds/
│       └── Branch/
└── users/                       # Agent management views
```

## Setup

```bash
# Install dependencies
composer install

# Copy env
cp .env.example .env
php artisan key:generate

# Configure database in .env then run migrations
php artisan migrate

# Seed super admin
php artisan db:seed --class=SuperAdminSeeder

# Serve
php artisan serve
```

## Branch Setup (Super Admin)

1. Go to **Settings > Branches**
2. Create a branch — this also creates the branch_manager user
3. Check the module permissions (GR, Challan, Freight Memo, etc.)
4. Go to **Settings > Users** to create agents for that branch

## Key Tables

| Table | Purpose |
|-------|---------|
| `grs` | Goods Receipts |
| `challans` | Challans (truck loading documents) |
| `challan_items` | Items in a challan (linked GRs) |
| `frieghts` | Freight Memos (truck owner settlements) |
| `gatepasses` | Gate Passes (delivery release) |
| `branches` | Company branches/offices |
| `branch_permissions` | Module access per branch |
| `user_permissions` | Module access per agent |
| `users` | All users with `role` column |
| `vehicles` | Truck fleet |
| `truckdrivers` | Driver records |

## Notes

- All queries are branch-scoped via `OfficeScopeTrait`
- Super admin sees all branches, can impersonate any office
- Auto-numbering for GR/Challan/FM/GP uses atomic row-locking (no race conditions)
- Accounting module is suppressed — will be reworked in a future phase
