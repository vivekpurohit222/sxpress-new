# Form Field Map — Saurashtra Express

> Source: scan of every `*.blade.php` containing a `<form>` or an AJAX POST.
> Companion: `ghost-field-audit.md`, `codebase-inventory.md §4.1`.

Legend: ✅ field exists in DB and `$fillable`; 🟡 field exists in form but not in `$fillable` (will be silently dropped on `Model::create()`); ❌ field is a ghost (typo or no matching column at all).

---

## 1. GR — Create (`admin/category/copies.blade.php`)

| Field | Type (HTML) | Required | Validation rule (controller) | DB column | Status |
|---|---|---|---|---|---|
| `gr_no` | text | yes | required | `grs.gr_no` | ✅ |
| `from_dest` | select | yes | required | `grs.from_dest` | ✅ (but the form renders this `<select>` twice — once disabled, once hidden; the second value is what actually posts) |
| `to_dest` | select | yes | required | `grs.to_dest` | ✅ |
| `copy_date` | text | (none) | — | `grs.copy_date` | ✅ — but type is **string**, not `date` (see `database-reconstruction-report.md §4.2`) |
| `consignor` | text | yes | required | `grs.consignor` | ✅ |
| `nor_adress` | text | yes | required | `grs.nor_adress` | ✅ (typo: should be `consignor_address`; see `ghost-field-audit.md §2.1`) |
| `nor_gst_no` | text | (none) | min:15, max:15 | `grs.nor_gst_no` | ✅ (typo: should be `consignor_gst_no`) |
| `consignee` | text | yes | required | `grs.consignee` | ✅ |
| `nee_adress` | text | yes | required | `grs.nee_adress` | ✅ (typo: should be `consignee_address`) |
| `nee_gst_no` | text | (none) | min:15, max:15 | `grs.nee_gst_no` | ✅ (typo) |
| `nugs` | number | yes | required | `grs.nugs` | ✅ |
| `meth` | select (C_R / C_B / Bags) | yes | required | `grs.meth` | ✅ |
| `description` | textarea | yes | required | `grs.description` | ✅ |
| `pm` | text | yes | required | `grs.pm` | ✅ |
| `eway_bill_number` | text | yes | required | `grs.eway_bill_number` | ✅ |
| `bill_amount` | number | yes | numeric, required | `grs.bill_amount` | ✅ |
| `weight` | number | yes | numeric, required | `grs.weight` | ✅ |
| `paid` | checkbox | (none) | — | `grs.paid` | ✅ |
| `to_pay` | checkbox | (none) | — | `grs.to_pay` | ✅ |
| `frieght_amount` | number | yes | numeric, required | `grs.frieght_amount` | ✅ |
| `sur_ch` | number | yes | numeric, required | `grs.sur_ch` | ✅ |
| `c_r` | number | yes | numeric, required | `grs.c_r` | ✅ |
| `other` | number | yes | numeric, required | `grs.other` | ✅ (in form this is "GST"; stored as `other`) |
| `bc_amount` | number | yes | numeric, required | `grs.bc_amount` | ✅ |
| `total_amount` | number | yes | numeric, required | `grs.total_amount` | ✅ |

**Related controller:** `dash\GrController@store`
**Related table:** `grs`
**Purpose:** Capture the booking of goods from consignor to consignee at a given office. The document of record.

---

## 2. GR — Edit (`admin/category/copies_edit.blade.php`)

Same 25 fields as create. Plus: an `id` hidden field is required by `update($request, $id)`.

---

## 3. GR — Print (`admin/category/copies_print.blade.php`)

Read-only view. References these `$copy->...` accesses:

| View reference | Column | Status |
|---|---|---|
| `$copy->from_dest` | `grs.from_dest` | ✅ |
| `$copy->to_dest` | `grs.to_dest` | ✅ |
| `$copy->copy_date` | `grs.copy_date` | ✅ |
| `$copy->consignor` | `grs.consignor` | ✅ |
| `$copy->nor_adress` | `grs.nor_adress` | ✅ |
| `$copy->nor_gst_no` | `grs.nor_gst_no` | ✅ |
| `$copy->consignee` | `grs.consignee` | ✅ |
| `$copy->nee_adress` | `grs.nee_adress` | ✅ |
| `$copy->nee_gst_no` | `grs.nee_gst_no` | ✅ |
| **`$copy->packeges`** | — | **❌ GHOST** — typo of `nugs`; see `ghost-field-audit.md §1.1` |
| `$copy->description` | `grs.description` | ✅ |
| (manual inputs: freight, sur_ch, cr, gst, bc, pm, weight, paid-at, tbb-at, total) | — | 🟡 these are **manual print-time inputs**, not bound to a model; they print blank |

**Related controller:** `dash\GrController@show`
**Related table:** `grs`

---

## 4. Gatepass — Create (`admin/category/Gatepass/gate_pass.blade.php`)

| Field | Required | Validation | Column | Status |
|---|---|---|---|---|
| `gp_no` | yes | required | `gatepasses.gp_no` | ✅ |
| `gp_date` | yes | required | `gatepasses.gp_date` | ✅ (string, should be `date`) |
| `gr_no` | yes | required | `gatepasses.gr_no` | ✅ (UNIQUE — would block one-to-many) |
| `m_s` | yes | required | `gatepasses.m_s` | ✅ (typo; should be `consignor`) |
| `from_dest` | yes | required | `gatepasses.from_dest` | ✅ |
| `to_dest` | yes | required | `gatepasses.to_dest` | ✅ |
| `weight` | yes | required | `gatepasses.weight` | ✅ (int — should be decimal(10,3)) |
| `nugs` | yes | required | `gatepasses.nugs` | ✅ |
| `pm` | yes | required | `gatepasses.pm` | ✅ |
| `frieght_amount` | yes | required | `gatepasses.frieght_amount` | ✅ (int — should be decimal(12,2)) |
| `labour_amount` | yes | required | `gatepasses.labour_amount` | ✅ (int — should be decimal(12,2)) |
| `other` | yes | required | `gatepasses.other` | ✅ (int — should be decimal(12,2)) |
| `dc_amount` | yes | required | `gatepasses.dc_amount` | ✅ (int — should be decimal(12,2)) |
| `total_amount` | yes | required | `gatepasses.total_amount` | ✅ (int — should be decimal(12,2)) |
| `note` | no | — | `gatepasses.note` | ✅ |

**Related controller:** `dash\GatepassController@store`
**Related table:** `gatepasses`
**Purpose:** Record the release of goods at the destination office.

---

## 5. Gatepass — Edit (`admin/category/Gatepass/gate_pass_edit.blade.php`)

Same 16 fields. Plus:

| View reference | Column | Status |
|---|---|---|
| **`gst_amount`** (form input) | — | **❌ GHOST** — column doesn't exist; controller assigns to `$gp->gst_amount` which is silently dropped; see `ghost-field-audit.md §1.2` |

---

## 6. Challan — Create (`admin/category/challan/challan.blade.php`)

AJAX-driven form. Top-level fields:

| Field | Required | Column | Status |
|---|---|---|---|
| `challan_no` | auto-generated | `challans.challan_no` (PK) | ✅ |
| `challan_date` | (date string) | `challans.challan_date` | ✅ (string) |
| `from_dest` | yes | `challans.from_dest` | ✅ |
| `to_dest` | yes | `challans.to_dest` | ✅ |
| `truck_no` | yes | `challans.truck_no` | ✅ |
| `driver_name` | yes | `challans.driver_name` | ✅ (denormalized snapshot) |
| `license` | yes | `challans.license` | ✅ (denormalized) |
| `owner_name` | yes | `challans.owner_name` | ✅ |
| `note` | no | `challans.note` | ✅ |
| `challan_total` | computed | `challans.challan_total` | ✅ |

Lines (per AJAX POST to `/dash/challan/challanIteams`):

| Field | Column | Status |
|---|---|---|
| `gr_no` | `challan_iteams.gr_no` | ✅ (UNIQUE — see ghost-field-audit §1.4) |
| `challan_no` | `challan_iteams.challan_no` | ✅ |
| `nugs` | `challan_iteams.nugs` | ✅ |
| `meth` | `challan_iteams.meth` | ✅ |
| `description` | `challan_iteams.description` | ✅ |
| `weight` | `challan_iteams.weight` | ✅ |
| `paid` | `challan_iteams.paid` | ✅ |
| `to_pay` | `challan_iteams.to_pay` | ✅ |
| `sur_ch` | `challan_iteams.sur_ch` | ✅ |
| `c_r` | `challan_iteams.c_r` | ✅ |
| `other` | `challan_iteams.other` | ✅ |

**Related controller:** `dash\ChallanController@store` (parent) + `dash\ChallanController@challanIteamStore` (lines)
**Related tables:** `challans`, `challan_iteams`
**Purpose:** Assign a truck to a set of GRs and create a delivery document.

---

## 7. Freight Memo — Create (`admin/category/FrieghtMemo/Frieght_memo.blade.php`)

⚠️ The form's `<input>` elements **lack `name` attributes** — the form posts an empty payload even when fields are filled. The controller's `store()` is a stub anyway.

| Field (visual) | Intended column | Status |
|---|---|---|
| `fm_no` | `frieghts.fm_no` | ❌ form has no `name` |
| `fm_date` | `frieghts.fm_date` | ❌ form has no `name` |
| `truck_no` | `frieghts.truck_no` | ❌ form has no `name` |
| `from_dest` | `frieghts.from_dest` | ❌ form has no `name` |
| `to_dest` | `frieghts.to_dest` | ❌ form has no `name` |
| `entry_1` (desc) + `entry_1_amount` | `frieghts.entry_1`, `entry_1_amount` | ❌ form has no `name` |
| `entry_2` + `entry_2_amount` | same | ❌ |
| `entry_3` + `entry_3_amount` | same | ❌ |
| `entry_4` + `entry_4_amount` | same | ❌ |
| `total_amount` | `frieghts.total_amount` | ❌ |
| `truck_freight` | `frieghts.truck_freight` | ❌ |
| `commission` | `frieghts.commission` | ❌ |
| `other_charges` | `frieghts.other_charges` | ❌ |
| `extra` | `frieghts.extra` | ❌ |
| `balance_to_sn` | `frieghts.balance_to_sn` | ❌ |
| `note` | `frieghts.note` | ❌ |

**Related controller:** `dash\FreightController@store` *(stub)*
**Related table:** `frieghts`
**Purpose (intended):** Settlement document for a truck — what the truck owner is owed, less commission and other charges, leaves the balance to be paid.

---

## 8. Truck/Driver — Create (`admin/category/TruckDriver/truck_driver.blade.php`)

| Field | Required | Validation | Column | Status |
|---|---|---|---|---|
| `driver_name` | yes | required | `truckdrivers.driver_name` | ✅ |
| `truck_no` | yes | required, regex `/^[A-Za-z]{2}[ -][0-9]{1,2}(?: [A-Za-z])?(?: [A-Za-z]*)? [0-9]{4}$/` | `truckdrivers.truck_no` | ✅ (UNIQUE) |
| `license` | yes | required, regex `/^(([A-Za-z]{2}[0-9]{2})( )|([A-Za-z]{2}-[0-9]{2}))((19|20)[0-9][0-9])[0-9]{7}$/` | `truckdrivers.license` | ✅ (UNIQUE) |
| `mobile_no1` | no | min:0, max:10 | `truckdrivers.mobile_no1` | ✅ (UNIQUE) |
| `mobile_no2` | no | min:0, max:10 | `truckdrivers.mobile_no2` | ✅ (UNIQUE, nullable) |
| `driver_address` | yes | required | `truckdrivers.driver_address` | ✅ (string — should be `text`) |

**Related controller:** `TruckdriverController@store`
**Related table:** `truckdrivers`
**Purpose:** Master for trucks and their assigned drivers.

---

## 9. Users — Create (`users/create.blade.php`)

| Field | Required | Column | Status |
|---|---|---|---|
| `name` | yes | `users.name` | ✅ |
| `email` | yes | `users.email` | ✅ (UNIQUE) |
| `password` | yes | `users.password` | ✅ (bcrypt via mutator) |
| `office` | yes | `users.office` | ✅ |
| `roles[]` | no | `model_has_roles` (Spatie) | ✅ |

**Related controller:** `UserController@store`
**Related table:** `users` + `model_has_roles`
**Purpose:** Admin creates a new user with role assignment.

---

## 10. Roles — Create (`roles/create.blade.php`)

| Field | Required | Column | Status |
|---|---|---|---|
| `name` | yes | `roles.name` | ✅ (UNIQUE per guard) |
| `permissions[]` | no | `role_has_permissions` (Spatie) | ✅ |

**Related controller:** `RoleController@store`
**Related table:** `roles` + `role_has_permissions`

---

## 11. Permissions — Create (`permissions/create.blade.php`)

| Field | Required | Column | Status |
|---|---|---|---|
| `name` | yes | `permissions.name` | ✅ (UNIQUE per guard) |

**Related controller:** `PermissionController@store`
**Related table:** `permissions`

---

## 12. Auth — Login (`auth/login.blade.php`)

| Field | Required | Column | Status |
|---|---|---|---|
| `email` | yes | `users.email` | ✅ |
| `password` | yes | `users.password` | ✅ |
| `remember` | no | `users.remember_token` | ✅ |

---

## 13. Summary

- **Total form fields surveyed:** ~120
- **Healthy (✅):** 119
- **Ghost (❌):** 2 — `$copy->packeges` (typo), `gst_amount` (column never created)
- **Module functionally broken:** Freight Memo (form has no `name` attributes; `store()` is a stub)
- **Module functionally broken:** Challan (controller imports non-existent `challan_iteam` class)
- **Module with hidden bug:** Gatepass (gst_amount form input is silently dropped; `gp_no` counter resets at 1000)
- **Module with hidden bug:** GR (duplicate disabled+hidden `from_dest` `<select>`)
