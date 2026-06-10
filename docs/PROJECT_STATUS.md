# SXpress Logistics - Project Status

**Last Updated:** June 9, 2026
**Project Path:** `D:\sxpress\new sxpress\sxpress - Copy`
**Framework:** Laravel 10 + PHP 8 + MySQL 8
**Frontend:** Bootstrap 5 + Custom SCSS + Blade Templates

---

## 📋 Project Overview

SXpress is a **Freight/Logistics Management System** built with Laravel. It manages:
- Goods Receipt (GR) tracking
- Gate passes
- Challans (delivery notes)
- Freight memos
- Multiple branch operations (Rajkot, Kashmore Gate, Navagam, Dayabasti, Swarup Nagar, Shapar)
- Truck drivers, vehicles, routes, stations
- Customer management (consignors/consignees)
- User authentication with role-based permissions

---

## ✅ What Works (Functional Modules)

### 1. Authentication (COMPLETE)
- **Login Page** (`/login`) - Modern split-screen UI with dark gradient, floating labels, Bootstrap Icons
- **Registration Page** (`/register`) - Matching design with office branch selection dropdown
- **Logout** - Works via navbar dropdown
- **Password Reset** - Laravel Breeze default implementation
- **Middleware** - Authenticated users redirected to dashboard; guests to login

### 2. GR (Goods Receipt) Module - `dash/gr` (MAIN MODULE)
**Status: FULLY FUNCTIONAL**

- **List View** (`/dash/gr`) - Shows GRs filtered by user's office
- **Create** (`/dash/gr/create`) - Auto-generates GR number per branch:
  - Rajkot → `AA-00001`
  - Kashmore Gate → `CG-00001`
  - Navagam → `NV-00001`
  - Dayabasti → `DB-00001`
  - Swarup Nagar → `SN-00001`
  - Shapar (1) → `S1-00001`
  - Shapar (2) → `S2-00001`
- **Edit** (`/dash/gr/{id}/edit`) - Update GR details (recently fixed column name mismatch)
- **Delete** (`/dash/gr/{id}/delete`)
- **Print** (`/dash/gr/{id}/print`) - Print view for GR

**Key Fields:** gr_no, from_dest, to_dest, copy_date, consignor, consignor_address, consignor_gst_no, consignee, consignee_address, consignee_gst_no, nuggets, meth (method), eway_bill_number, bill_amount, description, pm, weight, paid/to_pay flags, freight_amount, sur_ch, c_r, other, bc_amount, total_amount

### 3. Gatepass Module - `dash/gatepass`
**Status: FUNCTIONAL**
- Full CRUD operations
- Links to GR for generating gate passes

### 4. Challan Module - `dash/challan`
**Status: FUNCTIONAL**
- Create challans with items
- Links to GR

### 5. Freight Memo Module - `dash/frieghtmemo`
**Status: FUNCTIONAL**
- Full CRUD operations

### 6. Truck Driver Module - `dash/truckdriver`
**Status: FUNCTIONAL**
- Full CRUD with status field (active/inactive)

### 7. Branch Management - `dash/branch`
**Status: FUNCTIONAL**
- Full CRUD for branch records

### 8. Customer Management
- **Customers** (`/dash/customer`) - Full CRUD
- **Consignors** (`/dash/consignor`) - Full CRUD
- **Consignees** (`/dash/consignee`) - Full CRUD

### 9. Vehicle Management - `dash/vehicle`
**Status: FUNCTIONAL**
- Full CRUD for vehicle records

### 10. Route Management - `dash/route`
**Status: FUNCTIONAL**
- Full CRUD for route records

### 11. Station Management - `dash/station`
**Status: FUNCTIONAL**
- Full CRUD for station records

### 12. User Management
- Standard Laravel user management via `UserController`
- Role-based access via Spatie Permission

---

## 🗄️ Database Structure

### Core Tables
| Table | Purpose | Status |
|-------|---------|--------|
| `users` | User accounts with office assignment | ✅ Working |
| `branches` | Branch master data | ✅ Working |
| `grs` | Goods Receipt records | ✅ Working |
| `gatepasses` | Gate pass records | ✅ Working |
| `challans` | Challan headers | ✅ Working |
| `challan_items` | Challan line items | ✅ Working |
| `frieghts` | Freight memos | ✅ Working |
| `truckdrivers` | Truck driver records | ✅ Working |
| `vehicles` | Vehicle master | ✅ Working |
| `routes` | Route definitions | ✅ Working |
| `stations` | Station definitions | ✅ Working |
| `customers` | Customer records | ✅ Working |
| `consignors` | Consignor records | ✅ Working |
| `consignees` | Consignee records | ✅ Working |

### Column Rename Migration (Applied)
Legacy columns renamed in `grs` table:
- `nor_adress` → `consignor_address`
- `nee_adress` → `consignee_address`
- `nor_gst_no` → `consignor_gst_no`
- `nee_gst_no` → `consignee_gst_no`

---

## 🎨 UI/UX Structure

### Layout
- **Auth Pages** (login/register): Full-screen split layout, no navbar, dark gradient background
- **Dashboard Pages**: Standard admin layout with navbar, sidebar navigation
- **Views Location**: `resources/views/admin/category/`

### View Naming Convention
- `{module}.blade.php` - Create form
- `{module}_list.blade.php` - List view
- `{module}_edit.blade.php` - Edit form
- `{module}_view.blade.php` - Detail view
- `copies_print.blade.php` - Print layout for GR

### CSS/Assets
- Admin SCSS: `resources/admin/scss/`
- Compiled assets: `public/admin/`
- Bootstrap 5 + custom variables

---

## 🔧 Key Technical Details

### GR Number Generation Logic (in GrController@create)
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
// Finds last GR with matching prefix, increments numeric part
// Format: PREFIX-00001 (5-digit zero-padded)
```

### Model Fillable (Gr.php)
Includes both current and legacy column names for backward compatibility:
```php
protected $fillable = [
    'gr_no', 'from_dest', 'to_dest', 'copy_date',
    'consignor', 'consignor_address', 'consignor_gst_no',
    'consignee', 'consignee_address', 'consignee_gst_no',
    // Legacy (for form backward compat)
    'nor_adress', 'nee_adress', 'nor_gst_no', 'nee_gst_no',
    // ... other fields
];
```

### Authentication Flow
1. User visits `/login` or `/register`
2. `Auth::routes()` handles Laravel Breeze routes
3. `RegisterController` validates + stores `office` field
4. Dashboard at `/dash` shows filtered data by user's office

---

## 📁 Important File Paths

### Controllers
- `app/Http/Controllers/dash/GrController.php` - GR operations
- `app/Http/Controllers/dash/GatepassController.php` - Gatepass operations
- `app/Http/Controllers/dash/ChallanController.php` - Challan operations
- `app/Http/Controllers/dash/FreightController.php` - Freight operations
- `app/Http/Controllers/Auth/RegisterController.php` - Registration with office

### Models
- `app/Models/Gr.php` - GR model
- `app/Models/User.php` - User with office field
- `app/Models/Branch.php`, `Customer.php`, etc.

### Views (Auth)
- `resources/views/auth/login.blade.php` - Modern login UI
- `resources/views/auth/register.blade.php` - Modern register UI
- `resources/views/layouts/app.blade.php` - Main layout

### Routes
- `routes/web.php` - All web routes under `/dash` prefix

### Migrations
- `database/migrations/` - Contains both legacy and new structure migrations

---

## 🔴 Known Issues / Pending Work

1. **Challan Items Deletion** - Route exists `challan/edit/{id}` but deletion logic needs verification
2. **Gatepass GR Linking** - Gatepasses should link to specific GRs, verify relationship
3. **Print Views** - Some print views may need styling updates to match modern UI
4. **Role/Permission UI** - Admin role management views exist but UI is basic
5. **Dashboard Charts** - DashboardController exists but no charts/widgets implemented
6. **File Uploads** - POD (Proof of Delivery) uploads mentioned in table but may not be fully implemented

---

## 📝 Recent Fixes Applied (June 9, 2026)

1. **GR Update Error** - Fixed SQL error caused by column name mismatch after migration renamed `nor_adress` → `consignor_address` etc. Updated `GrController@update` and added legacy names to `Gr::$fillable`

2. **GR Number Per Branch** - Completely rewrote `GrController@create` to:
   - Generate GR numbers per branch (not shared sequence)
   - Use correct branch prefixes
   - Properly increment within each branch

3. **Login/Register UI** - Replaced basic Bootstrap card UI with modern split-screen design featuring:
   - Dark gradient background
   - Floating labels
   - Bootstrap Icons
   - Responsive design

4. **RegisterController** - Added `office` field validation and creation

---

## 🚀 Quick Reference for Claude Code

When working on this codebase:

1. **GR Module** is the main focus - all GR operations go through `GrController`
2. **User's office** determines data filtering - use `Auth::user()->office`
3. **GR numbers** are prefix-based per branch, format: `XX-00001`
4. **Column names** - After migration, use new names (`consignor_address`, etc.) but `Gr::$fillable` accepts both for form compatibility
5. **Auth pages** have no navbar (handled in `layouts/app.blade.php` conditionally)
6. **All dashboard routes** are under `/dash` prefix
7. **Branch prefixes**: Rajkot=AA, Kashmore Gate=CG, Navagam=NV, others as defined

---

*This document should be updated after each significant change or new feature implementation.*