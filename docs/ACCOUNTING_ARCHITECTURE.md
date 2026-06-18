# SXpress Accounting Module — Architecture Document

**Phase 1 Deliverable**
**Date:** June 2026

---

## 1. Existing System Review

### Current Modules (operational — NOT to be modified)

| Module | Table | Key Financial Fields |
|--------|-------|---------------------|
| GR | `grs` | `frieght_amount`, `sur_ch`, `c_r`, `other`, `bc_amount`, `total_amount`, `paid`, `to_pay`, `topay_collected` |
| Gatepass | `gatepasses` | `frieght_amount`, `total_amount` |
| Challan | `challans` + `challan_items` | `total_weight` (no direct financial) |
| Freight Memo | `frieghts` | `truck_freight`, `commission`, `entry_1_amount`–`entry_4_amount`, `other_charges`, `balance_due` |
| TO-PAY | `grs` | `topay_collected`, `topay_collected_date`, `topay_collected_by` |

### Branch Structure
- 7 offices: Rajkot, Navagam, Shapar (1), Shapar (2), Dayabasti, Kashmore Gate, Swarup Nagar
- Each branch is an independent profit center
- `office` column on every operational record

### Role Hierarchy
```
SuperAdmin → all branches, full access
Admin      → branch-level control
Manager    → branch operations + reports
Staff      → data entry
Viewer     → read only
```

---

## 2. Accounting Integration Points

These are the existing events that should trigger automatic accounting entries:

| Event | Debit | Credit | When |
|-------|-------|--------|------|
| GR Created (Paid) | Cash / Bank | Freight Income | GR store with `paid=1` |
| GR Created (To-Pay) | Accounts Receivable | Freight Income | GR store with `to_pay=1` |
| TO-PAY Collected | Cash / Bank | Accounts Receivable | `markTopayCollected()` |
| Freight Memo Created | Lorry Hire Expense | Accounts Payable (Truck Owner) | FM store |
| Freight Memo Advance | Advance to Driver | Cash | FM with advance > 0 |
| Freight Memo Balance Paid | Accounts Payable | Cash / Bank | When owner is paid |
| Expense Created | Expense Account | Cash / Bank | Expense voucher |
| Cash Deposit to Bank | Bank Account | Cash | Contra voucher |

---

## 3. Database Design

### New Tables Required

```
accounts                    — Chart of Accounts (master)
├── id
├── code (unique, e.g. "1001")
├── name ("Cash In Hand")
├── type (asset / liability / income / expense / equity)
├── parent_id (FK → accounts.id, nullable for root)
├── is_group (boolean — group account vs transactional)
├── branch_id (nullable — null = company-wide, set = branch-specific)
├── opening_balance (decimal)
├── is_system (boolean — cannot delete)
├── is_active
├── timestamps

ledger_entries              — All financial movements (double-entry)
├── id
├── voucher_id (FK → vouchers.id)
├── account_id (FK → accounts.id)
├── date
├── debit (decimal 12,2)
├── credit (decimal 12,2)
├── narration (description)
├── branch (office name)
├── reference_type (gr / freight_memo / expense / manual)
├── reference_id (polymorphic FK)
├── timestamps

vouchers                    — Financial documents
├── id
├── voucher_no (unique per type per branch)
├── voucher_type (receipt / payment / contra / journal)
├── voucher_date
├── narration
├── total_amount
├── branch (office)
├── status (draft / approved / cancelled)
├── approved_by
├── approved_at
├── created_by_id
├── timestamps
├── soft_deletes

expenses                    — Expense tracking
├── id
├── expense_no
├── expense_date
├── expense_type (diesel / salary / repair / tyre / office / branch / misc)
├── description
├── amount
├── paid_to
├── account_id (FK → accounts.id for categorization)
├── voucher_id (FK → vouchers.id)
├── vehicle_id (nullable)
├── driver_id (nullable)
├── branch (office)
├── status (pending / approved / paid)
├── approved_by
├── created_by_id
├── timestamps
├── soft_deletes

bank_accounts               — Bank master
├── id
├── bank_name
├── account_number
├── ifsc_code
├── branch_name
├── account_id (FK → accounts.id)
├── opening_balance
├── is_active
├── timestamps

outstanding                 — Receivable/Payable tracking
├── id
├── party_type (customer / consignor / consignee / truck_owner)
├── party_id
├── party_name
├── type (receivable / payable)
├── invoice_ref (GR no / FM no)
├── invoice_date
├── total_amount
├── paid_amount
├── pending_amount (computed)
├── due_date
├── status (pending / partial / paid / overdue)
├── branch (office)
├── timestamps
```

### Relationships Diagram

```
accounts (1) ──── (N) ledger_entries
vouchers (1) ──── (N) ledger_entries
vouchers (1) ──── (1) expenses
grs ──── (triggers) ──── ledger_entries (via reference_type='gr')
frieghts ──── (triggers) ──── ledger_entries (via reference_type='freight_memo')
```

---

## 4. Modules Required

| Module | Controller | Views | Priority |
|--------|-----------|-------|----------|
| Chart of Accounts | `AccountController` | list, create, edit | Phase 2 |
| Ledger | `LedgerController` | list, detail, filters | Phase 3 |
| Vouchers | `VoucherController` | create, edit, print, approve | Phase 4 |
| Cash Book | `CashBookController` | daily, monthly | Phase 5 |
| Bank Book | `BankBookController` | daily, reconciliation | Phase 6 |
| Outstanding | `OutstandingController` | list, aging, recovery | Phase 7 |
| Auto-Posting | `AccountingService` | (background service) | Phase 8 |
| Expenses | `ExpenseController` | list, create, approve | Phase 9 |
| Financial Reports | `FinancialReportController` | trial balance, P&L, balance sheet | Phase 12 |

---

## 5. Integration Strategy (Non-Breaking)

The accounting module will use **event-driven integration**:

```php
// Existing modules fire events → Accounting listeners create entries

// In EventServiceProvider:
GRCreated::class      → CreateGrAccountingEntry::class
TopayCollected::class → CreateTopayCollectionEntry::class
FreightMemoCreated    → CreateFreightMemoEntry::class
ExpenseApproved       → CreateExpenseEntry::class
```

**No existing controller, model, or view is modified.**
Accounting entries are created by listeners that observe existing events.

For events that don't exist yet (e.g. TopayCollected), we add new events without changing existing logic — the existing code simply fires the event at the end.

---

## 6. Routes Structure

```
/accounting/accounts          — Chart of Accounts CRUD
/accounting/ledger            — Ledger view with filters
/accounting/vouchers          — Voucher CRUD
/accounting/cash-book         — Cash book reports
/accounting/bank-book         — Bank book reports
/accounting/outstanding       — Outstanding management
/accounting/expenses          — Expense module
/accounting/reports           — Financial reports (TB, P&L, BS)
```

All under `middleware(['auth', 'role:SuperAdmin|Admin|Manager'])` with office isolation via `OfficeScopeTrait`.

---

## 7. Permissions Required

| Permission | Roles |
|-----------|-------|
| `view-accounting` | SuperAdmin, Admin, Manager |
| `create-voucher` | SuperAdmin, Admin, Manager |
| `approve-voucher` | SuperAdmin, Admin |
| `manage-accounts` | SuperAdmin |
| `view-financial-reports` | SuperAdmin, Admin, Manager |
| `manage-expenses` | SuperAdmin, Admin, Manager |
| `approve-expenses` | SuperAdmin, Admin |

---

## 8. Key Design Decisions

1. **Double-entry bookkeeping** — every transaction has equal debit and credit
2. **Voucher-based** — every entry must have a voucher (auto-generated or manual)
3. **Branch-isolated** — managers see only their branch books
4. **Event-driven integration** — no modification to existing modules
5. **Soft deletes everywhere** — audit trail preserved
6. **System accounts** — certain accounts (Cash, Freight Income, etc.) cannot be deleted
7. **Opening balances** — support migration from existing manual books

---

## 9. File Structure

```
app/
├── Http/Controllers/Accounting/
│   ├── AccountController.php
│   ├── LedgerController.php
│   ├── VoucherController.php
│   ├── CashBookController.php
│   ├── BankBookController.php
│   ├── OutstandingController.php
│   ├── ExpenseController.php
│   └── FinancialReportController.php
├── Models/Accounting/
│   ├── Account.php
│   ├── LedgerEntry.php
│   ├── Voucher.php
│   ├── Expense.php
│   ├── BankAccount.php
│   └── Outstanding.php
├── Services/
│   └── AccountingService.php  (auto-posting logic)
├── Listeners/
│   ├── CreateGrAccountingEntry.php
│   ├── CreateTopayCollectionEntry.php
│   └── CreateFreightMemoEntry.php

resources/views/accounting/
├── accounts/
├── ledger/
├── vouchers/
├── cash-book/
├── bank-book/
├── outstanding/
├── expenses/
└── reports/

database/migrations/
├── create_accounts_table.php
├── create_ledger_entries_table.php
├── create_vouchers_table.php
├── create_expenses_table.php
├── create_bank_accounts_table.php
└── create_outstanding_table.php
```

---

## 10. Implementation Order (per spec)

1. ✅ Phase 1 — Architecture (this document)
2. Phase 2 — Chart of Accounts (CRUD + seeder)
3. Phase 3 — Ledger System (entries + display)
4. Phase 4 — Voucher System (receipt/payment/contra/journal)
5. Phase 5 — Cash Book
6. Phase 6 — Bank Book
7. Phase 7 — Outstanding Management
8. Phase 8 — Freight Memo Integration (auto-posting)
9. Phase 9 — Expense Module
10. Phase 10–12 — Reports (TB, P&L, Balance Sheet, GST)

---

## Phase 1 Status: COMPLETE

**Deliverables:**
- ✅ Existing system reviewed
- ✅ Integration points identified
- ✅ Database design documented
- ✅ Module list defined
- ✅ Non-breaking integration strategy
- ✅ Route structure planned
- ✅ Permissions mapped
- ✅ File structure defined

**Awaiting approval to proceed with Phase 2: Chart of Accounts.**
