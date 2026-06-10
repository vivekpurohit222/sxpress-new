# SXpress Logistics — Master System Document
## Complete Build Specification for Claude Code

**Version:** 2.0  
**Last Updated:** June 9, 2026  
**Framework:** Laravel 10 + PHP 8.x + MySQL 8  
**Frontend:** Bootstrap 5 + Custom SCSS + Blade Templates  
**Project Path:** `D:\sxpress\new sxpress\sxpress - Copy`

---

## TABLE OF CONTENTS

1. [System Overview](#1-system-overview)
2. [Business Domain & Terminology](#2-business-domain--terminology)
3. [Complete Workflow — How the System Works](#3-complete-workflow)
4. [Module Specifications](#4-module-specifications)
5. [Database Schema](#5-database-schema)
6. [Controllers & Routes](#6-controllers--routes)
7. [Views & UI Conventions](#7-views--ui-conventions)
8. [Authentication & Role-Based Access](#8-authentication--role-based-access)
9. [Pending Features to Build](#9-pending-features-to-build)
10. [Business Rules & Validations](#10-business-rules--validations)
11. [Print / Document Formats](#11-print--document-formats)
12. [Dashboard & Reports](#12-dashboard--reports)
13. [Quick Reference for Claude Code](#13-quick-reference-for-claude-code)

---

## 1. SYSTEM OVERVIEW

SXpress is an **Indian Road Freight / Transport Management System (TMS)** for a multi-branch transport company. It digitizes the full lifecycle of a goods consignment — from booking at origin (GR), dispatch (Challan/Gatepass), transit, delivery, to billing (Freight Memo).

### Business Context (India-Specific)

- Goods transported by road between multiple cities/branches
- Each shipment generates legally required documents: GR (Goods Receipt / Bilty / LR), Delivery Challan, E-Way Bill
- Freight is either **Paid** (consignor pays upfront) or **To-Pay** (consignee pays on delivery)
- Multi-branch operation: each branch handles its own GRs, with unique numbering prefixes
- GST compliance is mandatory — GSTIN of consignor and consignee tracked on every GR
- E-Way Bill number from government portal is recorded on each shipment

### Branches & GR Prefix Map

| Branch Name    | GR Prefix |
|----------------|-----------|
| Rajkot         | AA        |
| Kashmore Gate  | CG        |
| Navagam        | NV        |
| Dayabasti      | DB        |
| Swarup Nagar   | SN        |
| Shapar (1)     | S1        |
| Shapar (2)     | S2        |

GR number format: `PREFIX-00001` (5-digit zero-padded, independent sequence per branch).

---

## 2. BUSINESS DOMAIN & TERMINOLOGY

| Term | Meaning |
|------|---------|
| **GR (Goods Receipt)** | The primary consignment document. Also called Bilty, LR (Lorry Receipt). Issued at origin when goods are received for transport. |
| **Consignor** | The sender/shipper of goods. Has GST number. |
| **Consignee** | The receiver of goods. Has GST number. |
| **From Dest / To Dest** | Origin and destination stations/cities |
| **Paid** | Freight paid by consignor at booking time |
| **To Pay** | Freight collected from consignee at delivery |
| **E-Way Bill** | Mandatory GST e-document from govt portal for goods > ₹50,000. Number stored on GR. |
| **Nuggets** | Number of packages/units in the consignment |
| **Meth (Method)** | Mode of packing: bag, box, bundle, etc. |
| **PM** | Per Measure — unit for freight calculation (per kg, per piece, etc.) |
| **Weight** | Actual weight of consignment in kg |
| **Freight Amount** | Base freight charge |
| **Sur Ch (Surcharge)** | Additional surcharge on freight |
| **C.R.** | Cartage/Handling charge |
| **Other** | Miscellaneous charges |
| **BC Amount** | Booking charge |
| **Total Amount** | Sum of all charges |
| **Bill Amount** | Invoice/bill value of goods being transported |
| **Gatepass** | Document authorizing goods to leave origin gate. Linked to GR. |
| **Challan** | Delivery Challan — document accompanying goods in transit. Contains line items. |
| **Freight Memo** | Final billing memo for the freight transaction |
| **POD (Proof of Delivery)** | Signed receipt from consignee confirming delivery |
| **Truck Driver** | Driver assigned to vehicle for a trip |
| **Vehicle** | Truck/lorry used for transport |
| **Route** | Defined road path between two stations |
| **Station** | City/town/locality served by the transport company |
| **Branch** | Company's operating office location |
| **Office** | User's assigned branch (controls data visibility) |

---

## 3. COMPLETE WORKFLOW

### Phase 1: BOOKING (GR Creation)
```
Consignor arrives at branch with goods
    ↓
Staff creates GR (Goods Receipt / Bilty)
    ↓
GR Number auto-generated per branch (e.g., AA-00001)
    ↓
Details captured: consignor, consignee, origin, destination,
  goods description, weight, nuggets, packing method,
  freight amount, E-Way Bill number, GST numbers
    ↓
Payment type set: PAID (collected now) or TO-PAY (collect on delivery)
    ↓
GR printed in multiple copies:
  - Original → Consignee
  - Duplicate → Consignor
  - Triplicate → Transporter (SXpress)
```

### Phase 2: DISPATCH (Gatepass + Challan)
```
Goods loaded onto truck at origin branch
    ↓
Gatepass created (linked to GR)
  → Authorizes goods to exit the gate
  → Records vehicle number, driver, date/time
    ↓
Challan (Delivery Challan) created
  → Lists all GRs/items being dispatched together
  → Vehicle and driver details recorded
  → Sent with goods during transit
```

### Phase 3: TRANSIT
```
Truck moves from origin to destination
    ↓
Driver carries: Challan + GR copies + E-Way Bill copy
    ↓
Intermediate branches may receive and forward goods
  (Hub-and-spoke model across Rajkot, Kashmore, Navagam, etc.)
```

### Phase 4: DELIVERY
```
Goods arrive at destination branch
    ↓
Consignee notified
    ↓
If TO-PAY: freight collected from consignee
    ↓
Goods delivered to consignee
    ↓
POD (Proof of Delivery) collected
  → Consignee signature + stamp
  → Delivery date/time recorded
    ↓
POD dispatched back to booking branch
```

### Phase 5: BILLING (Freight Memo)
```
After POD received at booking branch:
    ↓
Freight Memo created
  → Consolidates freight charges
  → Used for invoicing/account settlement
    ↓
Money receipt issued (for TO-PAY collections)
    ↓
Accounts updated
```

### Document Flow Diagram
```
GR Created ──→ Gatepass ──→ Challan ──→ [In Transit] ──→ Delivery ──→ Freight Memo
    ↓                                                          ↓
GR Print                                                   POD Upload
(3 copies)                                              (Proof of Delivery)
```

---

## 4. MODULE SPECIFICATIONS

---

### 4.1 GR (Goods Receipt) Module
**Route prefix:** `/dash/gr`  
**Controller:** `app/Http/Controllers/dash/GrController.php`  
**Status: COMPLETE**

#### Operations
| Action | URL | Method |
|--------|-----|--------|
| List | `/dash/gr` | GET |
| Create Form | `/dash/gr/create` | GET |
| Store | `/dash/gr` | POST |
| Edit Form | `/dash/gr/{id}/edit` | GET |
| Update | `/dash/gr/{id}` | PUT/PATCH |
| Delete | `/dash/gr/{id}/delete` | GET/DELETE |
| Print | `/dash/gr/{id}/print` | GET |

#### All GR Fields
```
gr_no               — Auto-generated, branch-prefixed (e.g. AA-00001)
copy_date           — Date of GR booking
from_dest           — Origin station
to_dest             — Destination station
consignor           — Consignor name (sender)
consignor_address   — Consignor full address
consignor_gst_no    — Consignor GSTIN
consignee           — Consignee name (receiver)
consignee_address   — Consignee full address
consignee_gst_no    — Consignee GSTIN
nuggets             — Number of packages
meth                — Packing method (Bag/Box/Bundle/Drum/etc.)
eway_bill_number    — E-Way Bill number from GST portal
bill_amount         — Invoice value of goods being transported
description         — Goods description
pm                  — Per measure unit
weight              — Weight in kg
paid                — Boolean: is it prepaid?
to_pay              — Boolean: collect on delivery?
freight_amount      — Base freight charge (₹)
sur_ch              — Surcharge (₹)
c_r                 — Cartage/handling (₹)
other               — Other charges (₹)
bc_amount           — Booking charge (₹)
total_amount        — Grand total charges (₹)
office              — Branch that created this GR (from Auth::user()->office)
```

#### GR Number Generation Logic
```php
$prefixes = [
    'Rajkot'        => 'AA',
    'Kashmore Gate' => 'CG',
    'Navagam'       => 'NV',
    'Dayabasti'     => 'DB',
    'Swarup Nagar'  => 'SN',
    'Shapar (1)'    => 'S1',
    'Shapar (2)'    => 'S2',
];
$userOffice = Auth::user()->office;
$prefix = $prefixes[$userOffice] ?? 'GR';
$lastGR = GR::where('gr_no', 'like', $prefix . '-%')->orderBy('id', 'desc')->first();
$nextNumber = $lastGR ? (intval(substr($lastGR->gr_no, strlen($prefix) + 1)) + 1) : 1;
$grNo = $prefix . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
```

#### Data Filtering
All GR lists are filtered by `Auth::user()->office`:
```php
$grs = GR::where('office', Auth::user()->office)->latest()->get();
```

---

### 4.2 Gatepass Module
**Route prefix:** `/dash/gatepass`  
**Controller:** `app/Http/Controllers/dash/GatepassController.php`  
**Status: FUNCTIONAL — needs GR linking verified**

#### Purpose
Authorizes goods to exit the origin branch gate. Must be linked to one or more GRs.

#### Fields
```
gatepass_no     — Auto-generated gatepass number
gatepass_date   — Date of gatepass
gr_id           — Foreign key to GR (or multiple GRs via pivot table)
vehicle_no      — Vehicle/truck number
driver_name     — Driver name
driver_license  — Driver license number
from_station    — Origin
to_station      — Destination
remarks         — Optional notes
office          — Branch that created it
```

#### Business Rule
- A Gatepass must reference at least one GR
- Once Gatepass is created, goods are considered dispatched from origin

---

### 4.3 Challan (Delivery Challan) Module
**Route prefix:** `/dash/challan`  
**Controller:** `app/Http/Controllers/dash/ChallanController.php`  
**Status: FUNCTIONAL — item deletion needs verification**

#### Purpose
The document travelling with goods in transit. Contains list of all GRs/items in this consignment batch.

#### Tables: `challans` + `challan_items`

#### Challan Header Fields
```
challan_no      — Auto-generated
challan_date    — Date
vehicle_no      — Truck number
driver_name     — Driver
from_station    — Origin
to_station      — Destination
total_items     — Count of items
office          — Branch
```

#### Challan Item Fields
```
challan_id      — FK to challans
gr_no           — GR number reference
description     — Goods description
nuggets         — Number of packages
weight          — Weight
remarks         — Notes
```

#### Business Rule
- Challan items should be deletable individually on edit screen
- One challan can reference multiple GRs

---

### 4.4 Freight Memo Module
**Route prefix:** `/dash/frieghtmemo`  
**Controller:** `app/Http/Controllers/dash/FreightController.php`  
**Table:** `frieghts` (note the typo in table name — do NOT rename, it's in production)  
**Status: FUNCTIONAL**

#### Purpose
Final billing document for freight transactions. Used for accounts reconciliation.

#### Fields
```
memo_no         — Auto-generated
memo_date       — Date
gr_no           — Reference to GR
consignor       — Sender
consignee       — Receiver
freight_amount  — Freight charge
other_charges   — Additional charges
total           — Total amount
payment_type    — Paid / To-Pay
payment_status  — Pending / Collected
office          — Branch
```

---

### 4.5 Truck Driver Module
**Route prefix:** `/dash/truckdriver`  
**Controller:** Truck driver controller  
**Status: FUNCTIONAL**

#### Fields
```
name            — Driver full name
license_no      — Driving license number
phone           — Mobile number
address         — Home address
status          — active / inactive
```

---

### 4.6 Vehicle Module
**Route prefix:** `/dash/vehicle`  
**Status: FUNCTIONAL**

#### Fields
```
vehicle_no      — Registration number (e.g. GJ-03-AB-1234)
vehicle_type    — Truck / Tempo / Container / etc.
capacity        — Load capacity in tons
owner_name      — Owner name
status          — active / inactive
```

---

### 4.7 Branch Module
**Route prefix:** `/dash/branch`  
**Status: FUNCTIONAL**

#### Fields
```
name            — Branch name (must match Auth user office values exactly)
address         — Full address
city            — City
phone           — Contact number
manager_name    — Branch manager
```

---

### 4.8 Customer / Consignor / Consignee Modules
**Routes:** `/dash/customer`, `/dash/consignor`, `/dash/consignee`  
**Status: FUNCTIONAL**

#### Consignor / Consignee Fields
```
name            — Company or individual name
address         — Full address
city            — City
state           — State
gst_no          — GSTIN (15-character GST number)
phone           — Contact
email           — Email (optional)
```

**Key Feature to Build:** On the GR create form, when user starts typing a consignor/consignee name, auto-fill address and GST number from the database (AJAX autocomplete).

---

### 4.9 Route Module
**Route prefix:** `/dash/route`  
**Status: FUNCTIONAL**

#### Fields
```
from_station    — Origin
to_station      — Destination
distance_km     — Distance in kilometers
rate_per_kg     — Standard freight rate
```

---

### 4.10 Station Module
**Route prefix:** `/dash/station`  
**Status: FUNCTIONAL**

#### Fields
```
name            — Station/city name
state           — State
pincode         — PIN code
```

---

### 4.11 User Management
**Controller:** `UserController`  
**Roles:** Managed via Spatie Permission package  
**Status: FUNCTIONAL — UI is basic, needs improvement**

#### Fields
```
name            — Full name
email           — Login email
password        — Hashed
office          — Assigned branch (controls data filter)
roles           — Via Spatie (Admin, Manager, Staff, etc.)
```

---

## 5. DATABASE SCHEMA

### Core Tables

#### `grs` table
```sql
id                  BIGINT PK AUTO_INCREMENT
gr_no               VARCHAR(20) UNIQUE NOT NULL
copy_date           DATE
from_dest           VARCHAR(100)
to_dest             VARCHAR(100)
consignor           VARCHAR(200)
consignor_address   TEXT
consignor_gst_no    VARCHAR(20)
consignee           VARCHAR(200)
consignee_address   TEXT
consignee_gst_no    VARCHAR(20)
nuggets             INT
meth                VARCHAR(50)
eway_bill_number    VARCHAR(50)
bill_amount         DECIMAL(10,2)
description         TEXT
pm                  VARCHAR(50)
weight              DECIMAL(10,2)
paid                TINYINT(1) DEFAULT 0
to_pay              TINYINT(1) DEFAULT 0
freight_amount      DECIMAL(10,2)
sur_ch              DECIMAL(10,2)
c_r                 DECIMAL(10,2)
other               DECIMAL(10,2)
bc_amount           DECIMAL(10,2)
total_amount        DECIMAL(10,2)
office              VARCHAR(100)
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

Note: Legacy columns `nor_adress`, `nee_adress`, `nor_gst_no`, `nee_gst_no` were renamed via migration. The `Gr::$fillable` still accepts legacy names for form backward compatibility.

#### `gatepasses` table
```sql
id              BIGINT PK
gatepass_no     VARCHAR(20) UNIQUE
gatepass_date   DATE
vehicle_no      VARCHAR(20)
driver_name     VARCHAR(100)
driver_license  VARCHAR(50)
from_station    VARCHAR(100)
to_station      VARCHAR(100)
remarks         TEXT
office          VARCHAR(100)
created_at, updated_at TIMESTAMPS
```

#### `challans` table
```sql
id              BIGINT PK
challan_no      VARCHAR(20) UNIQUE
challan_date    DATE
vehicle_no      VARCHAR(20)
driver_name     VARCHAR(100)
from_station    VARCHAR(100)
to_station      VARCHAR(100)
total_items     INT
office          VARCHAR(100)
created_at, updated_at TIMESTAMPS
```

#### `challan_items` table
```sql
id              BIGINT PK
challan_id      BIGINT FK → challans.id
gr_no           VARCHAR(20)
description     TEXT
nuggets         INT
weight          DECIMAL(10,2)
remarks         TEXT
created_at, updated_at TIMESTAMPS
```

#### `frieghts` table (NOTE: intentional spelling — do not rename)
```sql
id              BIGINT PK
memo_no         VARCHAR(20)
memo_date       DATE
gr_no           VARCHAR(20)
consignor       VARCHAR(200)
consignee       VARCHAR(200)
freight_amount  DECIMAL(10,2)
other_charges   DECIMAL(10,2)
total           DECIMAL(10,2)
payment_type    ENUM('paid','to_pay')
payment_status  ENUM('pending','collected') DEFAULT 'pending'
office          VARCHAR(100)
created_at, updated_at TIMESTAMPS
```

#### `truckdrivers` table
```sql
id              BIGINT PK
name            VARCHAR(100)
license_no      VARCHAR(50)
phone           VARCHAR(20)
address         TEXT
status          ENUM('active','inactive') DEFAULT 'active'
created_at, updated_at TIMESTAMPS
```

#### `vehicles` table
```sql
id              BIGINT PK
vehicle_no      VARCHAR(30)
vehicle_type    VARCHAR(50)
capacity        DECIMAL(8,2)
owner_name      VARCHAR(100)
status          ENUM('active','inactive') DEFAULT 'active'
created_at, updated_at TIMESTAMPS
```

#### `branches` table
```sql
id              BIGINT PK
name            VARCHAR(100) -- Must match User.office exactly
address         TEXT
city            VARCHAR(100)
phone           VARCHAR(20)
manager_name    VARCHAR(100)
created_at, updated_at TIMESTAMPS
```

#### `consignors` table
```sql
id              BIGINT PK
name            VARCHAR(200)
address         TEXT
city            VARCHAR(100)
state           VARCHAR(100)
gst_no          VARCHAR(20)
phone           VARCHAR(20)
email           VARCHAR(100) NULLABLE
created_at, updated_at TIMESTAMPS
```

#### `consignees` table
```sql
id              BIGINT PK
name            VARCHAR(200)
address         TEXT
city            VARCHAR(100)
state           VARCHAR(100)
gst_no          VARCHAR(20)
phone           VARCHAR(20)
email           VARCHAR(100) NULLABLE
created_at, updated_at TIMESTAMPS
```

#### `customers` table
```sql
id              BIGINT PK
name            VARCHAR(200)
address         TEXT
gst_no          VARCHAR(20)
phone           VARCHAR(20)
city            VARCHAR(100)
created_at, updated_at TIMESTAMPS
```

#### `routes` table
```sql
id              BIGINT PK
from_station    VARCHAR(100)
to_station      VARCHAR(100)
distance_km     INT
rate_per_kg     DECIMAL(8,2)
created_at, updated_at TIMESTAMPS
```

#### `stations` table
```sql
id              BIGINT PK
name            VARCHAR(100)
state           VARCHAR(100)
pincode         VARCHAR(10)
created_at, updated_at TIMESTAMPS
```

#### `users` table (Laravel standard + custom fields)
```sql
id              BIGINT PK
name            VARCHAR(100)
email           VARCHAR(100) UNIQUE
password        VARCHAR(255)
office          VARCHAR(100)  -- Branch assignment
remember_token  VARCHAR(100)
created_at, updated_at TIMESTAMPS
```

#### `model_has_roles`, `roles`, `permissions` — via Spatie

---

## 6. CONTROLLERS & ROUTES

### Route File: `routes/web.php`

```php
// Auth Routes
Auth::routes();

// Dashboard
Route::middleware(['auth'])->group(function () {
    Route::get('/dash', [DashboardController::class, 'index'])->name('dashboard');

    // GR Module
    Route::prefix('dash/gr')->group(function () {
        Route::get('/', [GrController::class, 'index'])->name('gr.index');
        Route::get('/create', [GrController::class, 'create'])->name('gr.create');
        Route::post('/', [GrController::class, 'store'])->name('gr.store');
        Route::get('/{id}/edit', [GrController::class, 'edit'])->name('gr.edit');
        Route::put('/{id}', [GrController::class, 'update'])->name('gr.update');
        Route::get('/{id}/delete', [GrController::class, 'destroy'])->name('gr.destroy');
        Route::get('/{id}/print', [GrController::class, 'print'])->name('gr.print');
    });

    // Gatepass Module
    Route::resource('dash/gatepass', GatepassController::class);

    // Challan Module
    Route::resource('dash/challan', ChallanController::class);
    Route::get('dash/challan/edit/{id}', [ChallanController::class, 'edit']);

    // Freight Memo
    Route::resource('dash/frieghtmemo', FreightController::class);

    // Masters
    Route::resource('dash/truckdriver', TruckDriverController::class);
    Route::resource('dash/vehicle', VehicleController::class);
    Route::resource('dash/branch', BranchController::class);
    Route::resource('dash/customer', CustomerController::class);
    Route::resource('dash/consignor', ConsignorController::class);
    Route::resource('dash/consignee', ConsigneeController::class);
    Route::resource('dash/route', RouteController::class);
    Route::resource('dash/station', StationController::class);

    // User management
    Route::resource('dash/users', UserController::class);
});
```

### Controller Locations
```
app/Http/Controllers/
├── Auth/
│   ├── LoginController.php
│   ├── RegisterController.php       ← Handles 'office' field
│   └── ...
└── dash/
    ├── GrController.php             ← Main module
    ├── GatepassController.php
    ├── ChallanController.php
    ├── FreightController.php
    ├── TruckDriverController.php
    ├── VehicleController.php
    ├── BranchController.php
    ├── CustomerController.php
    ├── ConsignorController.php
    ├── ConsigneeController.php
    ├── RouteController.php
    ├── StationController.php
    └── UserController.php
```

### Model Locations
```
app/Models/
├── Gr.php               ← $fillable includes both new and legacy column names
├── Gatepass.php
├── Challan.php
├── ChallanItem.php
├── Freight.php          ← table: 'frieghts'
├── TruckDriver.php
├── Vehicle.php
├── Branch.php
├── Customer.php
├── Consignor.php
├── Consignee.php
├── Route.php
├── Station.php
└── User.php             ← Has 'office' attribute
```

### Gr Model Fillable (Important — includes legacy names)
```php
protected $fillable = [
    'gr_no', 'from_dest', 'to_dest', 'copy_date',
    'consignor', 'consignor_address', 'consignor_gst_no',
    'consignee', 'consignee_address', 'consignee_gst_no',
    // Legacy backward compat
    'nor_adress', 'nee_adress', 'nor_gst_no', 'nee_gst_no',
    'nuggets', 'meth', 'eway_bill_number', 'bill_amount',
    'description', 'pm', 'weight',
    'paid', 'to_pay',
    'freight_amount', 'sur_ch', 'c_r', 'other', 'bc_amount', 'total_amount',
    'office',
];
```

---

## 7. VIEWS & UI CONVENTIONS

### Directory Structure
```
resources/views/
├── layouts/
│   └── app.blade.php           ← Main layout (conditionally hides navbar for auth pages)
├── auth/
│   ├── login.blade.php         ← Split-screen modern UI, dark gradient, floating labels
│   └── register.blade.php     ← Matching design, includes office dropdown
└── admin/
    └── category/
        ├── gr.blade.php                ← GR create form
        ├── gr_list.blade.php           ← GR list
        ├── gr_edit.blade.php           ← GR edit form
        ├── gr_view.blade.php           ← GR detail
        ├── copies_print.blade.php      ← GR print layout (3 copies)
        ├── gatepass.blade.php
        ├── gatepass_list.blade.php
        ├── challan.blade.php
        ├── challan_list.blade.php
        ├── challan_edit.blade.php
        ├── frieghtmemo.blade.php
        ├── frieghtmemo_list.blade.php
        ├── truckdriver.blade.php
        ├── truckdriver_list.blade.php
        └── [other modules follow same naming pattern]
```

### Naming Convention
| File Pattern | Purpose |
|---|---|
| `{module}.blade.php` | Create form |
| `{module}_list.blade.php` | List/index view |
| `{module}_edit.blade.php` | Edit form |
| `{module}_view.blade.php` | Detail/read-only view |
| `copies_print.blade.php` | Print layout for GR (no navbar, print-optimized) |

### Layout Behavior
- Auth pages (`/login`, `/register`): Full-screen split layout, NO navbar/sidebar
- Dashboard pages (`/dash/*`): Standard admin layout WITH navbar and sidebar
- The `layouts/app.blade.php` detects auth pages via route check and hides nav accordingly

### CSS / Assets
```
resources/admin/scss/       ← Source SCSS
public/admin/               ← Compiled CSS/JS (run: npm run dev or npm run build)
```
Stack: Bootstrap 5 + custom SCSS variables + Bootstrap Icons

### UI Design Style
- Login/Register: Dark gradient background (#1a1a2e or similar), floating labels, modern card design
- Dashboard: Clean admin panel, white cards, sidebar navigation
- Tables: Bootstrap 5 responsive tables with action buttons (Edit/Delete/Print)
- Forms: Floating labels, Bootstrap 5 form controls

---

## 8. AUTHENTICATION & ROLE-BASED ACCESS

### Auth Flow
1. User visits `/login`
2. Enters email + password
3. On success → redirected to `/dash`
4. Dashboard shows data filtered by `Auth::user()->office`

### Registration
```php
// RegisterController validates and stores 'office'
protected function validator(array $data) {
    return Validator::make($data, [
        'name'     => ['required', 'string', 'max:255'],
        'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'office'   => ['required', 'string'],
    ]);
}

protected function create(array $data) {
    return User::create([
        'name'     => $data['name'],
        'email'    => $data['email'],
        'password' => Hash::make($data['password']),
        'office'   => $data['office'],
    ]);
}
```

### Roles (via Spatie Permission)
| Role | Access |
|------|--------|
| Admin | Full access all branches |
| Manager | Full access own branch |
| Staff | Create/read own branch, no delete |
| Viewer | Read-only |

### Data Isolation
Every controller filters by `Auth::user()->office`. Admin role bypasses this filter to see all branches.

---

## 9. PENDING FEATURES TO BUILD

This section defines all incomplete features. These are the priority tasks for development.

---

### 9.1 Dashboard Charts & Widgets (HIGH PRIORITY)
**File:** `app/Http/Controllers/DashboardController.php` (exists, empty)

Build a real-time dashboard at `/dash` showing:

**KPI Cards (Top Row)**
- Total GRs today (current branch)
- Total GRs this month
- Pending TO-PAY collections (sum of to_pay GRs not yet collected)
- Active vehicles count

**Charts**
- Bar chart: GRs created per day (last 30 days) — use Chart.js
- Pie chart: Paid vs To-Pay split this month
- Line chart: Monthly freight revenue trend (total_amount sum by month)

**Recent Activity Table**
- Last 10 GRs created at current branch (gr_no, consignor, to_dest, total_amount, status)

**Quick Actions**
- Button: "New GR" → `/dash/gr/create`
- Button: "New Gatepass" → `/dash/gatepass/create`

---

### 9.2 Consignor/Consignee Autocomplete on GR Form (HIGH PRIORITY)

When creating a GR, the consignor and consignee name fields should have AJAX autocomplete:
- User types 3+ characters
- Endpoint: `GET /dash/gr/autocomplete/consignor?q=xyz` → returns JSON: `[{name, address, gst_no}]`
- On selection → auto-fill address and gst_no fields
- Same for consignee

**Implementation:**
```javascript
// In gr.blade.php
$('#consignor').on('input', function() {
    let q = $(this).val();
    if (q.length < 3) return;
    $.get('/dash/gr/autocomplete/consignor', {q}, function(data) {
        // populate datalist or dropdown
    });
});
```

---

### 9.3 POD (Proof of Delivery) Upload (MEDIUM PRIORITY)

**Feature:** Allow uploading a signed POD document against a GR after delivery.

**What to build:**
- Add `pod_file` (varchar, nullable) and `pod_date` (date, nullable) and `delivery_status` ENUM('pending','delivered') to `grs` table
- New migration: `php artisan make:migration add_pod_fields_to_grs_table`
- On GR list view: show delivery status badge (Pending/Delivered)
- On GR view/edit: "Upload POD" button → opens upload form
- Controller: `GrController@uploadPod` — validates file (PDF/JPG/PNG, max 5MB), stores in `storage/app/public/pods/`, saves path to DB
- Display POD link on GR view

---

### 9.4 Gatepass ↔ GR Relationship (MEDIUM PRIORITY)

Currently gatepasses and GRs are not formally linked.

**What to build:**
- Create `gatepass_gr` pivot table: `gatepass_id`, `gr_no`
- On gatepass create form: multi-select or search-add field to attach GR numbers
- Show attached GRs on gatepass view/print
- On GR list: show "Has Gatepass" badge if linked

---

### 9.5 Freight Memo → GR Linking (MEDIUM PRIORITY)

- Add foreign key `gr_id` to `frieghts` table (optional, some memos may be manual)
- On Freight Memo create: searchable dropdown to select GR (auto-fills consignor, consignee, freight amount)
- Show "Memo Created" badge on GR list for GRs that have a memo

---

### 9.6 TO-PAY Collection Tracking (MEDIUM PRIORITY)

**Feature:** Track which TO-PAY GRs have been collected.

**What to build:**
- Add `topay_collected` BOOLEAN and `topay_collected_date` DATE to `grs`
- On GR list: filter tabs — All / Paid / To-Pay Pending / To-Pay Collected
- Action button "Mark Collected" on TO-PAY GRs
- Dashboard KPI: sum of pending TO-PAY amounts

---

### 9.7 Print Views Polish (MEDIUM PRIORITY)

**GR Print (`copies_print.blade.php`)** must produce 3 copies on one A4 page:
- Top third: Original (for Consignee)
- Middle third: Duplicate (for Consignor)
- Bottom third: Triplicate (for Transporter)

Each copy must contain:
- Company letterhead (SXpress name, address)
- GR Number prominently
- Date
- From / To destinations
- Consignor details (name, address, GSTIN)
- Consignee details (name, address, GSTIN)
- Goods: description, nuggets, weight, method
- E-Way Bill number
- Freight breakdown table (freight + surcharge + CR + other + BC = total)
- Paid / To-Pay checkbox/indicator
- Copy label (ORIGINAL / DUPLICATE / TRIPLICATE)

**Print CSS:** Use `@media print` to hide navbar/sidebar, break pages correctly.

---

### 9.8 Role Management UI (LOW PRIORITY)

Current Spatie roles exist but UI is basic.

**What to build:**
- `/dash/users` list with role badges
- On user edit: role assignment dropdown/checkboxes
- Role-based nav: hide menu items user doesn't have permission for

---

### 9.9 Branch-wise Reports (LOW PRIORITY)

Reports accessible at `/dash/reports`:

| Report | Description |
|--------|-------------|
| GR Register | All GRs for date range, filterable by branch |
| Freight Collection | Total freight collected (paid) and pending (to-pay) |
| Branch-wise Summary | GR count and revenue per branch for a period |
| Driver Trip Report | Trips per driver with vehicle and route |
| Daily Dispatch Summary | Challans created per day with total weight |

---

### 9.10 E-Way Bill Expiry Alert (LOW PRIORITY)

E-Way Bills expire (validity depends on distance). Add:
- `eway_bill_date` field to `grs` table (date EWB was generated)
- Warning badge on GR list if EWB is older than threshold (e.g., 24h for <100km, 1 day per 100km)
- Dashboard alert for expiring/expired EWBs in transit

---

## 10. BUSINESS RULES & VALIDATIONS

### GR Validation Rules
```php
// GrController@store validation
$rules = [
    'copy_date'         => 'required|date',
    'from_dest'         => 'required|string|max:100',
    'to_dest'           => 'required|string|max:100',
    'consignor'         => 'required|string|max:200',
    'consignee'         => 'required|string|max:200',
    'consignor_gst_no'  => 'nullable|string|max:20|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
    'consignee_gst_no'  => 'nullable|string|max:20|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
    'nuggets'           => 'nullable|integer|min:1',
    'weight'            => 'nullable|numeric|min:0',
    'freight_amount'    => 'nullable|numeric|min:0',
    'total_amount'      => 'nullable|numeric|min:0',
    'eway_bill_number'  => 'nullable|string|max:50',
];
```

### Total Amount Calculation
```javascript
// Auto-calculate total on form (frontend JS)
function calculateTotal() {
    let freight = parseFloat($('#freight_amount').val()) || 0;
    let surCh   = parseFloat($('#sur_ch').val()) || 0;
    let cr      = parseFloat($('#c_r').val()) || 0;
    let other   = parseFloat($('#other').val()) || 0;
    let bc      = parseFloat($('#bc_amount').val()) || 0;
    $('#total_amount').val((freight + surCh + cr + other + bc).toFixed(2));
}
// Bind to all charge inputs
$('#freight_amount, #sur_ch, #c_r, #other, #bc_amount').on('input', calculateTotal);
```

### Paid / To-Pay Logic
- `paid` and `to_pay` are mutually exclusive checkboxes
- At least one must be selected
- Validate: `paid XOR to_pay = true`

### GST Number Format (India)
- 15 characters: `NNAAAANNNNAAZC` format
- Example: `24AABCU9603R1ZX`
- Regex: `/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/`
- GST fields are optional (some consignors/consignees may be unregistered)

---

## 11. PRINT / DOCUMENT FORMATS

### GR Print (3-Copy Layout)
```
┌─────────────────────────────────────────┐
│  SXPRESS LOGISTICS                      │
│  [Address]           ORIGINAL           │
│  GR No: AA-00001    Date: 01/06/2026    │
│  From: Rajkot → To: Surat               │
│  ─────────────────────────────────────  │
│  Consignor: [Name]  GST: [GSTIN]        │
│  Address: [Address]                     │
│  Consignee: [Name]  GST: [GSTIN]        │
│  Address: [Address]                     │
│  ─────────────────────────────────────  │
│  Pkgs: 5   Method: Box   Wt: 50kg       │
│  Goods: [Description]                   │
│  E-Way Bill: 1234567890123              │
│  ─────────────────────────────────────  │
│  Freight: ₹500  Sur: ₹50  CR: ₹25      │
│  Other: ₹0   BC: ₹25   TOTAL: ₹600     │
│  [PAID ✓]  [TO-PAY □]                  │
└─────────────────────────────────────────┘
[--- cut line ---]
┌─────────────────────────────────────────┐
│  [Same layout]              DUPLICATE   │
└─────────────────────────────────────────┘
[--- cut line ---]
┌─────────────────────────────────────────┐
│  [Same layout]             TRIPLICATE   │
└─────────────────────────────────────────┘
```

### Delivery Challan Print Format
```
SXPRESS LOGISTICS — DELIVERY CHALLAN
Challan No: CH-00001    Date: 01/06/2026
Vehicle: GJ-03-AB-1234  Driver: [Name]
From: Rajkot → To: Surat

Sr | GR No    | Description | Pkgs | Weight | Remarks
1  | AA-00001 | Electronics | 5    | 50kg   |
2  | AA-00002 | Textiles    | 10   | 80kg   |
─────────────────────────────────────────
Total Packages: 15    Total Weight: 130kg
```

---

## 12. DASHBOARD & REPORTS

### DashboardController (to be built)
**File:** `app/Http/Controllers/DashboardController.php`

```php
public function index() {
    $office = Auth::user()->office;
    
    $data = [
        'totalGRsToday'     => GR::where('office', $office)->whereDate('copy_date', today())->count(),
        'totalGRsMonth'     => GR::where('office', $office)->whereMonth('copy_date', now()->month)->count(),
        'pendingTopay'      => GR::where('office', $office)->where('to_pay', 1)->sum('total_amount'),
        'activeVehicles'    => Vehicle::where('status', 'active')->count(),
        'recentGRs'         => GR::where('office', $office)->latest()->take(10)->get(),
        'grsByDay'          => GR::where('office', $office)
                                  ->selectRaw('DATE(copy_date) as date, COUNT(*) as count')
                                  ->groupBy('date')->orderBy('date')->take(30)->get(),
        'paidVsTopay'       => GR::where('office', $office)
                                  ->selectRaw('paid, to_pay, COUNT(*) as count')
                                  ->groupBy('paid','to_pay')->get(),
    ];
    
    return view('admin.category.dashboard', $data);
}
```

---

## 13. QUICK REFERENCE FOR CLAUDE CODE

### Golden Rules — Always Follow

1. **Data filtering by office** — Every query on GRs, Challans, Gatepasses, Freight Memos must filter `where('office', Auth::user()->office)`. Admin role bypasses this.

2. **GR number generation** — Use the branch prefix map. Never use a global sequence. Query `WHERE gr_no LIKE 'PREFIX-%'` to find the last number for that branch.

3. **Column names** — After the migration rename, use new column names (`consignor_address`, `consignee_address`, `consignor_gst_no`, `consignee_gst_no`) in all queries and forms. The `Gr::$fillable` accepts both old and new for form input compatibility.

4. **frieghts table** — This table name has a typo and it must stay that way. Specify `protected $table = 'frieghts';` in the Freight model.

5. **Auth layout** — Login/Register pages must NOT show the navbar/sidebar. The `layouts/app.blade.php` handles this conditionally. Do not break this.

6. **Branch prefix map** — When adding new branches, add them to the prefix map in `GrController`. The prefix must be 2 uppercase letters, unique.

7. **Print views** — Print routes (e.g., `/dash/gr/{id}/print`) should return a view that is fully self-contained — no navbar, includes all data, uses print CSS.

8. **Spatie Permissions** — Use `$user->hasRole('Admin')` and `$user->hasPermissionTo('...')`. Roles: Admin, Manager, Staff.

9. **AJAX autocomplete** — Consignor/Consignee autocomplete endpoints must be registered in `routes/web.php` and protected by `auth` middleware.

10. **Migrations** — Always run `php artisan migrate` after adding new migrations. Never modify existing migration files; create new ones for changes.

### Common Artisan Commands
```bash
php artisan migrate                    # Run new migrations
php artisan migrate:rollback           # Undo last migration
php artisan make:migration add_x_to_y_table --table=y
php artisan make:controller dash/XController --resource
php artisan make:model X -m            # Model + migration
php artisan storage:link               # For POD file uploads
php artisan cache:clear
php artisan config:clear
php artisan route:list                 # Check all routes
npm run dev                            # Compile SCSS/JS (watch mode)
npm run build                          # Compile for production
```

### File Naming for New Modules
When adding a new module (e.g., "Payment"):
- Controller: `app/Http/Controllers/dash/PaymentController.php`
- Model: `app/Models/Payment.php`
- Views: `resources/views/admin/category/payment.blade.php` (create), `payment_list.blade.php` (list), `payment_edit.blade.php` (edit)
- Route: `Route::resource('dash/payment', PaymentController::class);` in `routes/web.php`

### Error Patterns to Watch For
| Error | Cause | Fix |
|-------|-------|-----|
| `Column not found: 1054 Unknown column 'nor_adress'` | Migration renamed columns but controller still uses old names | Use `consignor_address`, `consignee_address` etc. |
| `SQLSTATE[42S02]: Base table 'frieghts' not found` | Model doesn't specify table name | Add `protected $table = 'frieghts';` |
| Blank dashboard / no data | Missing `where('office', ...)` filter | Add office filter to all queries |
| GR number duplicates | Prefix not filtered in last-GR query | Query `WHERE gr_no LIKE 'PREFIX-%'` |
| Print shows navbar | Print view extends `app.blade.php` | Use a minimal layout or `@extends('layouts.print')` |

---

## APPENDIX: DEVELOPMENT PRIORITIES

### Immediate (Sprint 1)
- [ ] Dashboard charts and KPI widgets (9.1)
- [ ] Autocomplete on GR form for consignor/consignee (9.2)
- [ ] Fix Challan item deletion (9.3 from known issues)
- [ ] GR print view polish — proper 3-copy layout (9.7)

### Short-term (Sprint 2)
- [ ] POD upload feature (9.3)
- [ ] Gatepass ↔ GR linking (9.4)
- [ ] TO-PAY collection tracking (9.6)

### Medium-term (Sprint 3)
- [ ] Freight Memo → GR linking (9.5)
- [ ] Branch-wise reports page (9.9)
- [ ] Role management UI (9.8)

### Future
- [ ] E-Way Bill expiry alerts (9.10)
- [ ] SMS/WhatsApp notification to consignee on delivery
- [ ] Mobile-responsive optimizations
- [ ] Export reports to Excel/PDF

---

*This document is the single source of truth for the SXpress system. Update it after every major feature addition or change.*
