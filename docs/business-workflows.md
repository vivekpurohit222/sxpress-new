# Business Workflows — Saurashtra Express Transport ERP

> Domain analysis. Reverse-engineered from `app/Http/Controllers/`, `resources/views/`, and `app/Models/`.
> Companion: `erp-workflow-map.md` (controller-level), `database-reconstruction-report.md` (schema).

---

## 1. GR — Goods Receipt (Booking)

### Purpose
The **Goods Receipt (GR)** is the **source-of-truth document** for a shipment. It is created at the booking office the moment a customer brings goods. The GR is referenced by:
- The Gatepass (delivery release at the destination office)
- The Challan (loading list when the truck is filled)

A GR is the equivalent of a consignment note / lorry receipt in traditional transport.

### Business flow
1. Customer walks into the booking office (e.g., the Rajkot branch).
2. Counter staff opens the **GR Create** form.
3. Counter selects the **from office** (auto-filled with their own office) and the **to office** (the delivery branch).
4. Counter enters **consignor** (sender) name, address, GSTIN.
5. Counter enters **consignee** (receiver) name, address, GSTIN.
6. Counter enters packages (`nugs` count + `meth` method: C_R / C_B / Bags).
7. Counter enters `description` of goods.
8. Counter enters `weight` (kg), `eway_bill_number` (mandatory for GST compliance), `bill_amount` (invoice value).
9. Counter enters the freight computation:
   - `frieght_amount` — base freight
   - `sur_ch` — surcharge
   - `c_r` — cartage / risk
   - `other` — GST amount
   - `bc_amount` — booking / collection charge
   - `total_amount` — sum of the above
10. Counter checks `paid` (if the customer has already paid freight) or `to_pay` (freight to be collected from consignee at delivery).
11. Counter saves. The form generates a `gr_no` of the form `AA-NNNNN` (per-office number series).
12. **Three physical copies** of the GR are printed:
    - Consignor copy (given to the customer at booking)
    - Consignee copy (sent with the truck for delivery)
    - Office copy (retained at the booking office)
13. The hard-coded print template (`copies_print.blade.php`) currently shows "DRIVER COPY" instead of any of these — likely the print template was meant to be triplicated or templated, but is a single layout.

### Tables
- `grs` (primary)
- `users.office` (implicit tenancy)

### Related screens
- `GET /dash/gr` — list
- `GET /dash/gr/create` — new
- `GET /dash/gr/{id}/edit` — edit
- `GET /dash/gr/{id}/print` — print

### Related reports
- None dedicated. The listing page is the de-facto register.

### Related APIs
- None.

---

## 2. Gatepass — Delivery Release

### Purpose
A **Gatepass** is created at the **destination office** to record the **physical release of goods** to the consignee. It is the counterpart of the GR at the delivery end. It captures any additional charges that arose at the destination (labour, delivery, etc.).

### Business flow
1. Truck arrives at the destination office.
2. Dest-office staff opens the **Gatepass Create** form, keyed by the `gr_no` of the original booking.
3. The form pre-fills: consignor (`m_s`), `from_dest`, `to_dest`, `weight`, `nugs`, `frieght_amount`, `other`, `total_amount` from the GR.
4. Staff adjusts `weight` and `nugs` if anything was lost or offloaded.
5. Staff adds destination-specific charges:
   - `labour_amount` — loading/unloading
   - `dc_amount` — delivery charge
   - `note` — remarks
6. Staff saves. A new `gp_no` is generated (global counter, fragile).
7. The Gatepass is printed and given to the consignee as the proof of release.

### Tables
- `gatepasses` (primary)
- `grs` (lookup only — no FK)

### Related screens
- `GET /dash/gatepass` — list
- `GET /dash/gatepass/create?gr_no=AA-NNNNN` — new (pre-filled from GR)
- `GET /dash/gatepass/{id}/edit` — edit
- `GET /dash/gatepass/{id}/print` — print (reuses GR template)

### Issues
- `gr_no` UNIQUE — a single GR can only be released once. If a truck is split, this fails.
- `gst_amount` ghost in update form (`ghost-field-audit.md §1.2`).
- `gp_no` counter resets at 1000.

---

## 3. Challan — Loading List

### Purpose
A **Challan** is the **truck loading document** that groups one or more GRs onto a single truck for transport. It is created at the **booking office** when the truck is being loaded.

### Business flow
1. Truck arrives at the booking office to be loaded.
2. Booking-office staff opens the **Challan Create** form, picks a `truck_no` (from the truck-driver master).
3. The form pre-fills the driver name and license from the truck record.
4. Staff enters from/to offices, owner name, note.
5. Staff **adds GRs one at a time**:
   - Types the `gr_no` into a row.
   - AJAX loads the GR data (nugs, meth, description, weight, freight, sur_ch, etc.).
   - The data is **snapshotted** into `challan_iteams` (so future edits to the GR don't change the challan line).
6. Staff saves. A new `challan_no` is generated.
7. The challan is printed in triplicate (driver, consignee at dest, office).

### Tables
- `challans` (header)
- `challan_iteams` (lines, snapshot of GR data)

### Related screens
- `GET /dash/challan` — list
- `GET /dash/challan/create?truck_no=...` — new
- `GET /dash/challan/{challan_no}/edit` — edit (does not actually update; the controller `update()` is a stub)
- `GET /dash/challan/{challan_no}/view` — view (stub)

### Issues
- **CRITICAL:** Controller imports `challan_iteam` (typo). Will throw on any call.
- No validation on `store()` — arbitrary input is inserted.
- `challan_total` is a stored column but should be a computed attribute (`sum(items.frieght_amount)`).
- `gr_no` UNIQUE on `challan_iteams` prevents the same GR from being on two different challans.
- No dedicated challan print template.

---

## 4. Freight Memo — Settlement

### Purpose
A **Freight Memo (FM)** is the **settlement document** for a truck owner. After all GRs on a challan are delivered, the booking office prepares a memo for the truck owner, computing the total freight earned, less commission, less other charges, plus extras, to arrive at the balance to be paid.

### Business flow (intended — module is a stub)
1. After the truck completes its trip, the booking office opens the **Freight Memo Create** form.
2. Staff selects a `truck_no`.
3. Staff enters 4 charge entries (the typical charges: Loading, Unloading, Detention, etc.) — `entry_1`/`entry_1_amount` through `entry_4`/`entry_4_amount`.
4. Staff enters `truck_freight` (the gross freight for the trip).
5. Staff enters `commission` (the booking office's cut), `other_charges`, `extra`.
6. The `total_amount` and `balance_to_sn` are auto-computed (in a real implementation).
7. Staff saves. The memo is printed, the truck owner signs, and payment is processed.

### Current state
- **NOT FUNCTIONAL.** Form inputs lack `name` attributes; controller `store()` is empty; view is unbound.
- See `ghost-field-audit.md §3` and `form-field-map.md §7`.

### Tables
- `frieghts` (read-only listing, no controller write path)

---

## 5. Gatepass vs Challan — when to use which?

| Use Gatepass when… | Use Challan when… |
|---|---|
| Goods are released directly at the destination office. | Goods are loaded onto a truck for transit. |
| One GR → one release event. | Many GRs → one truck. |
| Local delivery (same city). | Inter-city / inter-state transport. |

In practice, a single shipment will have:
- One **GR** (at booking).
- One **Challan** (when the truck is loaded).
- One **Gatepass** (at the destination office, when goods are released).
- One **Freight Memo** (at settlement, when the truck owner is paid).

---

## 6. Vehicle / Driver operations

### Purpose
Maintain the master of trucks and their assigned drivers. The `truckdriver` table is the only vehicle/driver master in the system.

### Business flow
1. Admin adds a new truck + driver via `GET /dash/truckdriver/create`.
2. Indian truck number format is validated by regex: `GJ 05 AB 1234` (state, district, letters, digits).
3. Indian driving license format is validated by regex: `GJ05 20140001234` (state, district, year, sequence).
4. Mobile numbers are unique-constrained.
5. The truck is now available to be selected when creating a Challan or Freight Memo.

### Modernization opportunity
The current model treats the driver as **a property of the truck** (i.e., a truck owns a driver). A real transport ERP separates the two so a single driver can be reassigned to multiple trucks over time. This is a `vehicles` + `drivers` + `assignments` normalization. See `erd.md §3` for the modernized schema.

---

## 7. Settlement (proposed — for modernization)

The current Freight Memo module is a stub. A real settlement workflow would be:

```
1. Trip completes (challan is "closed")
   ↓
2. Freight Memo is generated for the trip
   - Computes: total = sum(challan.items.frieght_amount)
   - Less: commission
   - Less: other_charges
   - Plus: extra
   - = balance_to_pay
   ↓
3. Payment is recorded
   - cash / cheque / NEFT
   - partial or full
   - reference number
   ↓
4. Outstanding balance is computed
   - balance_to_pay - sum(payments) = outstanding
   ↓
5. Reports
   - Truck owner statement
   - Outstanding balance report
   - Payment register
```

### Tables needed (for modernization)
- `freight_memos` (replaces `frieghts` — but we keep the table for backward compat and add the new one)
- `freight_memo_payments`
- `freight_memo_challans` (junction — which challans are in the memo)
- `payment_methods` (enum / master: cash, cheque, NEFT, UPI)

---

## 8. POD (Proof of Delivery) — proposed

Not implemented today. A POD is a photo/scan of the consignee's signed delivery receipt.

### Proposed flow
1. Driver delivers the goods to the consignee.
2. Consignee signs the receipt.
3. Driver takes a photo with the mobile app (or office staff scans it).
4. Photo is uploaded and linked to the GR.
5. `pod_uploads` table: `gr_id, pod_image_path, signature_image_path, delivered_at, received_by_name`.
6. POD is required to close the GR ("delivered" status).

---

## 9. Customer / Vendor masters — proposed

Not implemented today. Consignors and consignees are free-text on the GR. A real ERP would master them.

### Tables needed
- `customers` — id, code, name, address, gst_no, phone, email, is_active
- `vendors` — id, code, name (truck owner), address, gst_no, pan_no, bank_account, is_active

### Migration path
1. Add the tables.
2. Backfill: `INSERT INTO customers (code, name) SELECT DISTINCT consignor, consignor FROM grs; UPDATE grs SET consignor_id = c.id WHERE grs.consignor = c.name;` (and same for consignee).
3. Add a `consignor_id` / `consignee_id` FK to `grs`.
4. Add autocomplete UI in the GR form (gradual rollout).

---

## 10. Reporting system (current)

The application has no dedicated reports. All "reports" are listings.

For a transport ERP, the following reports are typical:
- Daily GR register
- Branch revenue
- Truck utilization
- Customer revenue
- Outstanding freight
- GST summary (for tax filing)
- E-Way Bill expiry report
- Driver settlement statement
- Truck-owner statement

See `report-inventory.md §3` for the prioritized list.

---

## 11. Cross-module data movement

| From | To | Data | Trigger |
|---|---|---|---|
| GR | Gatepass | gr_no, from_dest, to_dest, consignor, weight, nugs, frieght_amount, other, total_amount | User creates Gatepass keyed by gr_no |
| GR | Challan | gr_no, nugs, meth, description, weight, paid, to_pay, sur_ch, c_r, other | User adds GR to challan via AJAX |
| Challan | Freight Memo | truck_no, total freight | (NOT implemented) |
| Gatepass | POD | gr_no (via gatepass.gr_no → grs.gr_no) | (NOT implemented) |

---

## 12. Business rules (extracted from code)

| Rule | Source | Implementation |
|---|---|---|
| Each user belongs to one office | `users.office` | string column, no FK |
| A GR's `from_dest` must be the user's office | `GrController@index` filter | controller code, not DB |
| GR number format `AA-NNNNN` (per-office) | `GrController@create` | controller code, fragile |
| GR number rolls over at per-office boundary | `GrController@create` | controller code, hard-coded per office |
| Gatepass number resets at 1000 | `GatepassController@create` | controller code, BUG |
| Challan number `AA-NNNN` | `ChallanController@create` | controller code |
| GSTIN is 15 chars | `GrController@store` validate | inline |
| Truck number format | `TruckdriverController@store` regex | inline |
| License number format | `TruckdriverController@store` regex | inline |
| Mobile number is 10 digits | `TruckdriverController@store` max:10 | inline (no format check) |
| E-Way bill is mandatory | `GrController@store` require | inline |
| `grs.paid` + `grs.to_pay` are mutually exclusive (intended) | form UI | UI only, not enforced in DB |

---

## 13. Open questions (deferred to user)

1. Do you want to introduce a `branches` table and FK? (See `erd.md`.)
2. Do you want to introduce `customers` and `vendors` tables?
3. Do you want POD uploads?
4. Do you want a real settlement workflow with payments?
5. Do you want to split `truckdriver` into `trucks` + `drivers` + `assignments`?
6. Do you want true reports (PDF/Excel) or are the listings enough for now?
7. Do you want a Customer/Vendor portal (separate login)?

These are tracked in `final-discovery-report.md §Open Questions`.
