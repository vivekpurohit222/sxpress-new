# Report Inventory — Saurashtra Express

> Static scan of every controller, view, and SQL query for any report-generation logic.
> Result: there are **no dedicated reports** in this application. The "reports" are simply the table listings.

---

## 1. Reports currently available

| "Report" | URI | Controller | Tables touched | Filters | Export |
|---|---|---|---|---|---|
| GR Listing | `GET /dash/gr` | `dash\GrController@index` | `grs` joined to `users` (via `from_dest = office`) | Implicit: `from_dest = Auth::user()->office` (tenancy) | None |
| Gatepass Listing | `GET /dash/gatepass` | `dash\GatepassController@index` | `gatepasses` (all rows) | None | None |
| Challan Listing | `GET /dash/challan` | `dash\ChallanController@index` | `challans` (all rows) | None | None |
| Freight Memo Listing | `GET /dash/frieghtmemo` | `dash\FreightController@index` | `frieghts` (all rows) | None | None |
| Truck Listing | `GET /dash/truckdriver` | `TruckdriverController@index` | `truckdrivers` (all rows) | None | None |
| User Listing | `GET /dash/users` | `UserController@index` | `users` | None | None |
| Role Listing | `GET /dash/roles` | `RoleController@index` | `roles` | None | None |
| Permission Listing | `GET /dash/permissions` | `PermissionController@index` | `permissions` | None | None |

---

## 2. Print outputs

| Print | URI | Controller | View | Style |
|---|---|---|---|---|
| GR Print | `GET /dash/gr/{id}/print` | `dash\GrController@show` | `admin/category/copies_print.blade.php` | `public/css/copies_print.css` |
| Gatepass Print | `GET /dash/gatepass/{id}/print` | `dash\GatepassController@show` | **reuses** `copies_print.blade.php` | same CSS |

There is **no PDF library** (DomPDF, Snappy, Browsershot) in `composer.json`. The "print" is browser-driven. The print has a hard-coded Windows path `<img src="G:\revan\img1.jpg" />` which is broken in production (`ghost-field-audit.md §1.3`).

---

## 3. Reports MISSING (recommended for modernization)

### 3.1 Critical financial reports
| Report | Why | Difficulty |
|---|---|---|
| **Daily GR Register** — GRs created on a date, with from/to, consignor, weight, freight, total | Daily ops | Low (date filter on existing `grs` index) |
| **Branch-wise GR count** — per-office GR count for a date range | Management dashboard | Low |
| **Outstanding freight** — sum of `grs.total_amount` minus any payments | Finance | High (needs `payments` table) |
| **Truck-wise revenue** — sum of freight by `truck_no` | Finance | Medium |
| **Customer-wise revenue** — sum of freight by consignor/consignee | Finance | High (needs `customers` table) |
| **GST summary** — sum of `other` (which is the GST amount) by month, branch | Tax filing | Medium |
| **Truck utilization** — count of challans per truck per period | Ops | Low |

### 3.2 Operational reports
| Report | Why | Difficulty |
|---|---|---|
| **Pending deliveries** — GRs that have no Gatepass and no Challan | Exception list | Medium (LEFT JOIN) |
| **In-transit list** — Challans created but no POD received | Exception list | Medium (needs `pod_uploads`) |
| **Unsettled freight memos** — Freight memos where `balance_to_sn` > 0 | Settlement tracking | Low |
| **E-way bill expiry** — GRs whose `eway_bill_number` is older than X days | Compliance | Low (need date column, currently string) |

### 3.3 Auditing reports
| Report | Why | Difficulty |
|---|---|---|
| **User activity** — last login, GRs created per user | Audit | Medium (need `last_login_at` column) |
| **Audit log of changes** — who edited which GR | Audit | High (need `audit_logs` table) |
| **Branch transfer log** — GRs whose `from_dest` ≠ `Auth::user()->office` (i.e., reassigned) | Audit | Low |

### 3.4 Customer-facing reports
| Report | Why | Difficulty |
|---|---|---|
| **Customer copy reprint** — same as the print, but searchable by `gr_no` | Customer service | Low |
| **E-way bill lookup** — search by `eway_bill_number` | Customer service | Low |

---

## 4. SQL queries observed (for reference)

| File | Line(s) | Query | Purpose |
|---|---|---|---|
| `GrController::index` | 25–29 | `DB::table('users')->leftjoin('grs', 'grs.from_dest', '=', 'office')->select('grs.*', 'users.office')->where('grs.from_dest', '=', $ci)->get()` | Tenancy-filtered GR list |
| `GrController::create` | 46–49, 190–193, etc. | `DB::table('grs')->join('users', 'users.office', '=', 'from_dest')->select('grs.gr_no')->latest('grs.created_at')->first()->gr_no` | Next GR number (4 copies — one per office branch) |
| `GatepassController::create` | 44 | `DB::table('grs')->where('gr_no', $gr_no)->first()` | Pre-fill from GR |
| `GatepassController::index` | 20 | `gr::latest()->first()->gr_no` | Display most recent GR number (unrelated to the gatepass listing — odd) |
| `GatepassController::create` | 35 | `gatepass::latest()->first()->gp_no` | Next GP number |
| `User::officeall` | 53–55 | `DB::table('users')->where('id', $id)->pluck('office')` | Get current user's office |
| `ChallanController::create` | 41 | `DB::table('truckdrivers')->where('truck_no', $truck_no)->first()` | Look up truck (result unused) |
| `ChallanController::create` | 44 | `challan::latest()->first()->challan_no` | Next challan number |
| `ChallanController::getData` | 84 | `gr::where('gr_no', $gr_no)->first()` | AJAX: fetch GR for line item |
| `ChallanController::challanfetchdata` | 119 | `challan_iteam::where('challan_no', $challan_no)->get()` | AJAX: fetch challan lines |

No raw SQL (`DB::statement`, `DB::select` with `?` placeholders, etc.) was observed.

---

## 5. N+1 risk

| Location | N+1 risk? | Notes |
|---|---|---|
| `dash\GrController@index` | ✅ Safe | Single query (LEFT JOIN) |
| `dash\GatepassController@index` | ✅ Safe | `gatepass::all()` is one query |
| `dash\ChallanController@index` | ⚠️ | `challan::all()` is one query, but the view shows `count` of items per challan — that would be N+1 if rendered per-row. Verify view. |
| `dash\FreightController@index` | ✅ Safe | `Freight::all()` is one query |
| `TruckdriverController@index` | ✅ Safe | `truckdriver::all()` is one query |
| `TruckdriverController@create` | ✅ Safe | No query |
| `UserController@index` | ⚠️ | `User::all()` is one query, but the view shows roles per user — verify view, likely N+1. |
| `RoleController@index` | ⚠️ | `Role::all()` plus permissions per role — N+1. |
| `PermissionController@index` | ✅ Safe | One query |

**Total N+1 candidates to investigate in views:** 3 (challan items per challan, user roles per user, role permissions per role).

---

## 6. Critical reports (priority order)

| # | Report | Impact | Effort |
|---|---|---|---|
| 1 | Pending deliveries (GRs without Gatepass/Challan) | High | Low |
| 2 | Daily GR register with date range | High | Low |
| 3 | Branch-wise revenue (sum of `total_amount`) | High | Low |
| 4 | Truck utilization | Medium | Low |
| 5 | Outstanding freight (after payments) | High | High (needs payments) |
| 6 | GST summary | High | Medium |
| 7 | E-way bill expiry | Medium | Low |
| 8 | Customer-wise revenue | High | High (needs customers) |

---

## 7. Report framework recommendation

For the modernization, add one of:
- **Server-side:** `spatie/laravel-query-builder` for filtering/sorting + a CSV/PDF export endpoint.
- **Client-side:** a JS DataTables wrapper around the existing listings, with `excel` + `pdf` buttons.
- **Server-side PDF:** `barryvdh/laravel-dompdf` for printable views.

Recommend **DataTables** (server-side) for the listings + **DomPDF** for printable documents.
