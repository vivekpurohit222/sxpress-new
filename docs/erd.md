# Entity Relationship Diagram — Saurashtra Express

> Two diagrams:
> 1. **Current state** — exactly what the code describes today (with the bugs and typos preserved)
> 2. **Modernized state** — the target schema for the Laravel 11 + refactored architecture

---

## 1. Current ERD (as the code describes it)

```mermaid
erDiagram
    USERS ||--o{ GR : "office = from_dest (tenancy)"
    GR ||--o| GATEPASS : "gr_no (UNIQUE — wrong)"
    GR ||--o| CHALLAN_ITEM : "gr_no (UNIQUE — wrong)"
    CHALLAN ||--o{ CHALLAN_ITEM : "challan_no"
    TRUCKDRIVER ||--o{ CHALLAN : "truck_no (no FK)"
    TRUCKDRIVER ||--o{ FREIGHT : "truck_no (no FK, intended)"

    USERS {
        bigint id PK
        string name
        string email UK
        string password
        string office "tenancy key, free-text"
        timestamp email_verified_at
        timestamp created_at
        timestamp updated_at
    }

    GR {
        bigint id PK
        string gr_no UK "8 chars, e.g. AA-00001"
        string from_dest "free-text, e.g. 'Rajkot'"
        string to_dest "free-text"
        string copy_date "STRING, should be date"
        string consignor
        string nor_adress "TYPO: consignor_address"
        string nor_gst_no "TYPO"
        string consignee
        string nee_adress "TYPO"
        string nee_gst_no "TYPO"
        int nugs
        string meth "C_R / C_B / Bags"
        text description
        string pm
        string eway_bill_number
        decimal bill_amount
        decimal weight
        boolean paid
        boolean to_pay
        decimal frieght_amount
        decimal sur_ch
        decimal c_r
        decimal other "GST"
        decimal bc_amount
        decimal total_amount
        timestamp created_at
        timestamp updated_at
    }

    GATEPASS {
        bigint id PK
        int gp_no "fragile counter, resets at 1000"
        string m_s "TYPO: consignor"
        string gp_date "STRING, should be date"
        string from_dest
        string to_dest
        string gr_no UK "wrong — should be 1-to-many"
        int weight
        int nugs
        string pm
        int frieght_amount
        int labour_amount
        int other
        int dc_amount
        int total_amount
        text note
        timestamp created_at
        timestamp updated_at
    }

    CHALLAN {
        string challan_no PK "no id column"
        string from_dest
        string challan_date "STRING, should be date"
        string to_dest
        string truck_no
        string driver_name "denormalized snapshot"
        string license "denormalized snapshot"
        string owner_name
        text note
        decimal challan_total
        timestamp created_at
        timestamp updated_at
    }

    CHALLAN_ITEM {
        bigint id PK
        string gr_no UK "wrong — should be 1-to-many"
        string challan_no FK "string, no FK declared"
        int nugs
        string meth
        text description
        decimal weight
        decimal paid
        decimal to_pay
        decimal sur_ch
        decimal c_r
        decimal other
        timestamp created_at
        timestamp updated_at
    }

    FREIGHT {
        bigint id PK
        string fm_no "no unique"
        string fm_date "STRING, should be date"
        string from_dest
        string to_dest
        string truck_no
        string entry_1
        int entry_1_amount
        string entry_2
        int entry_2_amount
        string entry_3
        int entry_3_amount
        string entry_4
        int entry_4_amount
        int total_amount
        int truck_freight
        int commission
        int other_charges
        int extra
        int balance_to_sn
        text note
        timestamp created_at
        timestamp updated_at
    }

    TRUCKDRIVER {
        bigint id PK
        string driver_name
        string truck_no UK
        string license UK
        string driver_address "STRING, should be text"
        string mobile_no1 UK
        string mobile_no2 UK
        timestamp created_at
        timestamp updated_at
    }
```

### 1.1 Quick legend
- **PK** = primary key
- **UK** = unique key
- **FK** = foreign key (declared)
- *no FK* = relationship exists only via matching string values

### 1.2 Counts
- Tables: **8 application + 5 framework + 5 Spatie = 18**
- Declared FKs: **6** (all from Spatie)
- Implicit relationships: **7**
- String-typed date columns: **4** (all should be `date`)
- Misspelled columns: **8**

---

## 2. Modernized ERD (target)

```mermaid
erDiagram
    BRANCH ||--o{ USER : "branch_id"
    BRANCH ||--o{ GR : "from_branch_id"
    BRANCH ||--o{ GR : "to_branch_id"
    BRANCH ||--o{ GATEPASS : "from_branch_id"
    BRANCH ||--o{ GATEPASS : "to_branch_id"
    BRANCH ||--o{ CHALLAN : "from_branch_id"
    BRANCH ||--o{ CHALLAN : "to_branch_id"
    BRANCH ||--o{ FREIGHT_MEMO : "from_branch_id"
    BRANCH ||--o{ FREIGHT_MEMO : "to_branch_id"

    USER ||--o{ ROLE : "via model_has_roles (spatie)"
    ROLE ||--o{ PERMISSION : "via role_has_permissions (spatie)"

    GR ||--o{ GATEPASS : "gr_id"
    GR ||--o{ CHALLAN_LINE : "gr_id"
    CHALLAN ||--o{ CHALLAN_LINE : "challan_id"
    TRUCK ||--o{ CHALLAN : "truck_id"
    TRUCK ||--o{ FREIGHT_MEMO : "truck_id"
    DRIVER ||--o{ TRUCK_ASSIGNMENT : "driver_id"
    TRUCK ||--o{ TRUCK_ASSIGNMENT : "truck_id"

    CUSTOMER ||--o{ GR : "consignor_id"
    CUSTOMER ||--o{ GR : "consignee_id"
    VENDOR ||--o{ TRUCK : "owner_id"

    GR ||--o{ POD_UPLOAD : "gr_id"
    GR ||--o{ PAYMENT : "gr_id"
    FREIGHT_MEMO ||--o{ FREIGHT_PAYMENT : "freight_memo_id"

    BRANCH {
        bigint id PK
        string code UK
        string name
        string city
        string state
        string pincode
        string phone
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    USER {
        bigint id PK
        bigint branch_id FK
        string name
        string email UK
        timestamp email_verified_at
        string password
        string phone
        boolean is_active
        timestamp last_login_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    GR {
        bigint id PK
        string gr_no UK
        bigint from_branch_id FK
        bigint to_branch_id FK
        date copy_date
        string consignor
        text consignor_address
        string consignor_gst_no
        bigint consignor_id FK "nullable"
        string consignee
        text consignee_address
        string consignee_gst_no
        bigint consignee_id FK "nullable"
        int nugs
        string method "C_R / C_B / Bags"
        text description
        string payment_mode "PM"
        string eway_bill_number
        decimal bill_amount
        decimal weight
        boolean paid
        boolean to_pay
        decimal frieght_amount
        decimal surcharge
        decimal cartage_risk
        decimal gst_amount
        decimal bc_amount
        decimal total_amount
        bigint created_by_id FK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    GATEPASS {
        bigint id PK
        int gp_no
        date gp_date
        bigint gr_id FK
        bigint from_branch_id FK
        bigint to_branch_id FK
        string consignor
        int nugs
        decimal weight
        string payment_mode
        decimal frieght_amount
        decimal labour_amount
        decimal other_charges
        decimal delivery_charge
        decimal total_amount
        text note
        bigint created_by_id FK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    CHALLAN {
        bigint id PK
        string challan_no UK
        date challan_date
        bigint from_branch_id FK
        bigint to_branch_id FK
        bigint truck_id FK
        string driver_name
        string license
        string owner_name
        text note
        decimal challan_total "computed"
        bigint created_by_id FK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    CHALLAN_LINE {
        bigint id PK
        bigint challan_id FK
        bigint gr_id FK
        int nugs
        string method
        text description
        decimal weight
        decimal paid
        decimal to_pay
        decimal surcharge
        decimal cartage_risk
        decimal gst_amount
        timestamp created_at
        timestamp updated_at
    }

    FREIGHT_MEMO {
        bigint id PK
        string fm_no UK
        date fm_date
        bigint from_branch_id FK
        bigint to_branch_id FK
        bigint truck_id FK
        decimal total_amount
        decimal truck_freight
        decimal commission
        decimal other_charges
        decimal extra
        decimal balance_due
        text note
        bigint created_by_id FK
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    FREIGHT_LINE {
        bigint id PK
        bigint freight_memo_id FK
        int sequence
        string description
        decimal amount
    }

    FREIGHT_PAYMENT {
        bigint id PK
        bigint freight_memo_id FK
        date paid_on
        decimal amount
        string method "cash / cheque / NEFT / UPI"
        string reference
        text note
        timestamp created_at
        timestamp updated_at
    }

    TRUCK {
        bigint id PK
        string truck_no UK
        bigint owner_vendor_id FK
        bigint home_branch_id FK
        string make
        string model
        int year
        string fitness_certificate_no
        date fitness_expiry
        string insurance_no
        date insurance_expiry
        string permit_no
        date permit_expiry
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    DRIVER {
        bigint id PK
        string name
        string license UK
        text address
        string mobile1
        string mobile2
        date license_expiry
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    TRUCK_ASSIGNMENT {
        bigint id PK
        bigint truck_id FK
        bigint driver_id FK
        date assigned_from
        date assigned_to
        text note
    }

    CUSTOMER {
        bigint id PK
        string code UK
        string name
        text address
        string gst_no
        string pan_no
        string phone
        string email
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    VENDOR {
        bigint id PK
        string code UK
        string name
        text address
        string gst_no
        string pan_no
        string bank_account
        string ifsc
        string phone
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    POD_UPLOAD {
        bigint id PK
        bigint gr_id FK
        string file_path
        string signature_path
        string received_by_name
        datetime delivered_at
        timestamp created_at
    }

    PAYMENT {
        bigint id PK
        bigint gr_id FK
        date paid_on
        decimal amount
        string method
        string reference
        text note
        timestamp created_at
        timestamp updated_at
    }

    NUMBER_SEQUENCE {
        bigint id PK
        string scope UK "e.g. gr:rjkt, gp, challan, fm"
        bigint current_value
        string prefix
        int pad_length
        timestamp updated_at
    }

    AUDIT_LOG {
        bigint id PK
        bigint user_id FK
        string auditable_type
        bigint auditable_id
        string event
        json old_values
        json new_values
        string url
        string ip
        timestamp created_at
    }
```

---

## 3. Modernization diff summary

| Old | New | Type of change |
|---|---|---|
| `users.office` (string) | `users.branch_id` (FK) | FK + new table |
| `grs.from_dest` (string) | `grs.from_branch_id` (FK) | FK |
| `grs.to_dest` (string) | `grs.to_branch_id` (FK) | FK |
| `grs.nor_adress` (typo) | `grs.consignor_address` | rename |
| `grs.nee_adress` (typo) | `grs.consignee_address` | rename |
| `grs.nor_gst_no` (typo) | `grs.consignor_gst_no` | rename |
| `grs.nee_gst_no` (typo) | `grs.consignee_gst_no` | rename |
| `grs.copy_date` (string) | `grs.copy_date` (date) | type change |
| `gatepasses.m_s` (typo) | `gatepasses.consignor` | rename |
| `gatepasses.gp_date` (string) | `gatepasses.gp_date` (date) | type change |
| `gatepasses.dc_amount` (opaque) | `gatepasses.delivery_charge` | rename |
| `gatepasses.gr_no` (UNIQUE) | `gatepasses.gr_id` (FK, indexed) | proper FK |
| `gatepasses.weight/nugs/frieght_amount` (int) | `gatepasses.weight (decimal(10,3))` etc. | type precision |
| `challans` (no `id`) | `challans.id` (PK) + `challan_no` (UK) | add id |
| `challans.challan_date` (string) | `challans.challan_date` (date) | type change |
| `challan_iteams.gr_no` (UNIQUE) | `challan_iteams.gr_id` (FK, indexed) | proper FK |
| `challan_iteams.challan_no` (string) | `challan_iteams.challan_id` (FK) | proper FK |
| `frieghts` (table) | `freight_memos` (new) + alias view | rename + new |
| `frieghts.fm_date` (string) | `freight_memos.fm_date` (date) | type change |
| `frieghts.balance_to_sn` (opaque) | `freight_memos.balance_due` | rename |
| `frieghts.entry_1..4` (4 columns) | `freight_lines` (separate table) | normalize |
| `truckdrivers` (combined) | `trucks` + `drivers` + `truck_assignments` | split |
| `truckdrivers.truck_no` (UNIQUE) | `trucks.truck_no` (UNIQUE) | move |
| `truckdrivers.license` (UNIQUE) | `drivers.license` (UNIQUE) | move |
| `truckdrivers.driver_address` (string) | `drivers.address` (text) | move + type change |
| — | `customers` | new |
| — | `vendors` | new |
| — | `pod_uploads` | new |
| — | `payments` | new |
| — | `freight_payments` | new |
| — | `number_sequences` | new (replaces fragile per-controller counters) |
| — | `audit_logs` | new |
| — | `branches` | new |
| — | `personal_access_tokens` (sanctum) | new (for future API) |

---

## 4. Indexing plan (modernized)

### 4.1 Critical for scale (millions of GR records)
- `grs.from_branch_id` (filter by office on every list)
- `grs.to_branch_id` (filter by destination)
- `grs.copy_date` (date-range reports)
- `grs.gr_no` (UNIQUE — already)
- `grs.consignor` (search)
- `grs.consignee` (search)
- `grs.eway_bill_number` (lookup)
- COMPOSITE `(grs.from_branch_id, grs.copy_date)` (most common list query)
- COMPOSITE `(grs.to_branch_id, grs.copy_date)` (delivery reports)

### 4.2 Important
- `gatepasses.gr_id`
- `gatepasses.gp_date`
- COMPOSITE `(gatepasses.gp_no, gatepasses.gp_date)` — UNIQUE
- `challans.challan_no` (UNIQUE)
- `challans.challan_date`
- `challans.truck_id`
- `challan_lines.challan_id`
- `challan_lines.gr_id`
- COMPOSITE `(challan_lines.challan_id, challan_lines.gr_id)` — UNIQUE
- `freight_memos.fm_no` (UNIQUE)
- `freight_memos.fm_date`
- `freight_memos.truck_id`
- `trucks.truck_no` (UNIQUE)
- `drivers.license` (UNIQUE)
- `customers.code` (UNIQUE)
- `vendors.code` (UNIQUE)
- `users.email` (UNIQUE)
- `users.branch_id`

---

## 5. Soft-delete strategy

| Table | Soft delete? | Why |
|---|---|---|
| `users` | ✅ | audit, re-activation |
| `grs` | ✅ | regulatory (GR is a financial document) |
| `gatepasses` | ✅ | audit |
| `challans` | ✅ | audit |
| `challan_lines` | ❌ | lives with challan (cascade) |
| `freight_memos` | ✅ | financial |
| `freight_payments` | ❌ | append-only |
| `trucks` | ✅ | audit |
| `drivers` | ✅ | audit |
| `customers` | ✅ | audit |
| `vendors` | ✅ | audit |
| `pod_uploads` | ❌ | immutable |
| `payments` | ❌ | append-only |
| `audit_logs` | ❌ | append-only |
| `branches` | ❌ | never deleted; deactivated via `is_active` |
| `number_sequences` | ❌ | system table |

---

## 6. Audit columns (recommended)

On every business table, add:
- `created_by_id` BIGINT (FK to users, nullable for system-generated)
- `updated_by_id` BIGINT (FK to users, nullable)
- `created_at` TIMESTAMP
- `updated_at` TIMESTAMP
- `deleted_at` TIMESTAMP NULL (if soft delete)

Use Laravel's standard `Auditable` trait (or `spatie/laravel-activitylog`).

---

## 7. What this ERD does NOT cover (out of scope for first modernization pass)

- Multi-company (multi-tenant) isolation — currently single-company (Saurashtra Express). To support multiple companies, add a `companies` table and `branch.company_id`.
- Multi-currency — currently INR-only. To support, add a `currency` column on branch.
- Multi-language — currently English. To support, add JSON `translations` columns.
- API / external integrations — none currently. To support, add `Sanctum` personal access tokens.
