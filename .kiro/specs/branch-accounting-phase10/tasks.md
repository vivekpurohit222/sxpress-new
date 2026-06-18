# Implementation Plan: Branch Accounting (Phase 10)

## Overview

This plan implements branch-level financial reporting for SXpress in two parts: (A) tightening access control on all existing accounting routes to SuperAdmin-only, and (B) building a new BranchAccountingService, controller, routes, and Blade views for consolidated and per-branch financial reports. All code is PHP 8.1+ / Laravel 10.

## Tasks

- [x] 1. Restrict accounting access to SuperAdmin only
  - [x] 1.1 Update routes/web.php middleware for existing accounting routes
    - Change `role:SuperAdmin|Admin|Manager` to `role:SuperAdmin` on lines 52–89 (the second route group containing Ledger, Vouchers, CashBook, BankBook, Outstanding, Expenses)
    - The first group (AccountController) already uses `role:SuperAdmin` — no change needed
    - _Requirements: 1.1, 1.3_

  - [x] 1.2 Update controller constructors to enforce SuperAdmin middleware
    - Modify `$this->middleware(['auth', 'role:SuperAdmin|Admin|Manager'])` to `$this->middleware(['auth', 'role:SuperAdmin'])` in the constructors of:
      - `app/Http/Controllers/Accounting/LedgerController.php`
      - `app/Http/Controllers/Accounting/VoucherController.php`
      - `app/Http/Controllers/Accounting/CashBookController.php`
      - `app/Http/Controllers/Accounting/BankBookController.php`
      - `app/Http/Controllers/Accounting/OutstandingController.php`
      - `app/Http/Controllers/Accounting/ExpenseController.php`
    - _Requirements: 1.1, 1.3_

  - [x]* 1.3 Write feature tests for access control enforcement
    - Test that Admin, Manager, Staff, Viewer users receive HTTP 403 on all accounting routes
    - Test that unauthenticated users are redirected to `/login`
    - Test that SuperAdmin (even with additional roles) is granted access
    - _Requirements: 1.2, 1.4, 1.5_

- [x] 2. Implement BranchAccountingService
  - [x] 2.1 Create `app/Services/BranchAccountingService.php` with constants and date resolution
    - Define `BRANCHES` constant with the 7 office names
    - Implement `resolveFinancialYearDates()` returning Indian FY boundaries (April 1 – March 31)
    - _Requirements: 2.4, 3.5, 4.5, 5.1, 6.4_

  - [x] 2.2 Implement dashboard metrics method
    - Implement `getDashboardMetrics(string $fromDate, string $toDate): array`
    - For each branch calculate: revenue (sum of credit from income-type account ledger entries), expenses (sum of amount from non-rejected expenses), profitability (revenue − expenses), cash position (net balance of accounts 1101 and 1102), outstanding (sum of pending_amount where status != 'paid')
    - Return consolidated totals row summing all branches
    - Handle branches with no data → return zero values
    - _Requirements: 2.1, 2.2, 2.5, 2.6_

  - [x] 2.3 Implement revenue methods
    - Implement `getRevenueByBranch(string $fromDate, string $toDate): Collection` — aggregated revenue per branch with percentage of total
    - Implement `getRevenueDetailForBranch(string $branch, string $fromDate, string $toDate): Collection` — line items for GR-linked entries showing reference_id, date, account name, credit amount
    - _Requirements: 3.1, 3.2, 3.3, 3.6_

  - [x] 2.4 Implement expense methods
    - Implement `getExpensesByBranch(string $fromDate, string $toDate): Collection` — grouped by branch and expense_type with count and total
    - Implement `getExpenseDetailForBranch(string $branch, string $fromDate, string $toDate): Collection` — line items showing expense_no, expense_date, expense_type, description, amount, paid_to, status
    - Exclude records where status is 'rejected'
    - _Requirements: 4.1, 4.2, 4.3, 4.6_

  - [x] 2.5 Implement profitability method
    - Implement `getProfitabilityByBranch(string $fromDate, string $toDate): Collection`
    - For each branch: revenue, expenses, net profit (revenue − expenses), profit margin (guarded against division by zero → 0% if revenue is zero)
    - _Requirements: 5.2, 5.3, 5.4_

  - [x] 2.6 Implement cash position method
    - Implement `getCashPositionByBranch(string $fromDate, string $toDate): Collection`
    - Calculate per branch: opening balance (account opening_balance + debits − credits before from_date for accounts 1101, 1102), receipts (sum debit in range), payments (sum credit in range), closing (opening + receipts − payments)
    - Include consolidated totals row
    - _Requirements: 6.1, 6.2, 6.5, 6.6_

  - [x] 2.7 Implement outstanding methods
    - Implement `getOutstandingByBranch(?string $fromDate, ?string $toDate): Collection` — per branch: total receivable pending, total payable pending, net position, entry count
    - Implement `getOutstandingDetailForBranch(string $branch, ?string $fromDate, ?string $toDate): LengthAwarePaginator` — paginated at 30 per page, grouped by party_name
    - Filter only non-paid entries (status != 'paid')
    - When no date filter: show all non-paid records regardless of invoice_date
    - _Requirements: 7.1, 7.2, 7.4, 7.7_

  - [x]* 2.8 Write property test: Profitability Calculation Correctness (Property 2)
    - **Property 2: Profitability Calculation Correctness**
    - Seed random ledger entries (income-type accounts) and expenses, call `getProfitabilityByBranch`, assert net = revenue − expenses and margin = ((rev − exp) / rev) × 100 rounded to 2dp
    - **Validates: Requirements 2.2, 3.2, 5.2, 5.3**

  - [x]* 2.9 Write property test: Date Range Filtering (Property 3)
    - **Property 3: Date Range Filtering**
    - Seed entries across a wide date spectrum, call service methods with a sub-range, verify all returned records have dates within [from_date, to_date] and none outside
    - **Validates: Requirements 2.3, 3.4, 4.4, 5.5, 6.3, 7.5**

  - [x]* 2.10 Write property test: Consolidated Totals Invariant (Property 4)
    - **Property 4: Consolidated Totals Invariant**
    - Seed multi-branch data, call dashboard/revenue/expense/cash methods, verify the consolidated row equals the sum of individual branch rows
    - **Validates: Requirements 2.5, 3.6, 4.6, 6.5**

  - [x]* 2.11 Write property test: Branch Filtering Isolation (Property 5)
    - **Property 5: Branch Filtering Isolation**
    - Seed data across multiple branches, call detail methods for a specific branch, assert every returned record has branch == selected branch
    - **Validates: Requirements 3.3, 4.3, 7.4**

  - [x]* 2.12 Write property test: Status Exclusion (Property 6)
    - **Property 6: Status Exclusion**
    - Seed expense records with various statuses including 'rejected', and outstanding records with 'paid', verify excluded records never appear in results or aggregations
    - **Validates: Requirements 4.1, 7.2**

  - [x]* 2.13 Write property test: Cash Position Balance Equation (Property 7)
    - **Property 7: Cash Position Balance Equation**
    - Seed random cash transactions (debit/credit on accounts 1101/1102), call `getCashPositionByBranch`, assert closing = opening + receipts − payments for every branch
    - **Validates: Requirements 6.2**

- [x] 3. Checkpoint - Verify service layer
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Create BranchAccountingController and register routes
  - [x] 4.1 Create `app/Http/Controllers/Accounting/BranchAccountingController.php`
    - Constructor applies `['auth', 'role:SuperAdmin']` middleware
    - Inject `BranchAccountingService` via constructor
    - Implement 6 action methods: `dashboard`, `revenue`, `expenses`, `profitability`, `cashPosition`, `outstanding`
    - Each method resolves date range from request (fallback to FY defaults), calls service, returns Blade view
    - Validate date range: if to_date < from_date → redirect back with error
    - _Requirements: 2.1, 2.3, 3.4, 4.4, 5.5, 6.3, 7.5, 7.6, 8.6, 8.7_

  - [x] 4.2 Register branch accounting routes in `routes/web.php`
    - Append new route group after existing accounting routes
    - Prefix: `accounting/branch`, name prefix: `accounting.branch.`
    - Middleware: `role:SuperAdmin`
    - Routes: `/` → dashboard, `/revenue`, `/expenses`, `/profitability`, `/cash-position`, `/outstanding`
    - _Requirements: 8.1, 8.2_

  - [x]* 4.3 Write property test: Non-SuperAdmin Access Denial (Property 1)
    - **Property 1: Non-SuperAdmin Access Denial**
    - Generate random non-SuperAdmin role combinations, assert all accounting routes return 403
    - **Validates: Requirements 1.2, 8.3**

- [x] 5. Create Blade view partials
  - [x] 5.1 Create `resources/views/accounting/branch/_nav.blade.php`
    - Bootstrap 5 nav-pills navigation with links to all 6 branch reports
    - Use `request()->routeIs()` to apply `active` class to current page link
    - _Requirements: 8.4_

  - [x] 5.2 Create `resources/views/accounting/branch/_filter.blade.php`
    - Date range filter form with `from_date` and `to_date` HTML5 date inputs
    - Default: from_date = first day of current month, to_date = today
    - Retain submitted values on reload via `old()` or request query params
    - Submit button triggers GET reload with query parameters
    - _Requirements: 8.6, 8.7_

- [x] 6. Create Blade view pages
  - [x] 6.1 Create `resources/views/accounting/branch/dashboard.blade.php`
    - Include `_nav` and `_filter` partials
    - Display summary cards/table for each branch with: revenue, expenses, profitability, cash position, outstanding
    - Display consolidated totals row
    - Show zero values for branches with no data
    - _Requirements: 2.1, 2.2, 2.5, 2.6_

  - [x] 6.2 Create `resources/views/accounting/branch/revenue.blade.php`
    - Include `_nav` and `_filter` partials
    - Tabular comparison: branch name, total revenue, percentage of total (2 decimal places)
    - Drill-down section when branch selected: GR number, date, account name, credit amount
    - "No revenue records found" message when empty
    - _Requirements: 3.1, 3.3, 3.6, 3.7_

  - [x] 6.3 Create `resources/views/accounting/branch/expenses.blade.php`
    - Include `_nav` and `_filter` partials
    - Summary table: branches × expense_types with counts and totals, consolidated row
    - Drill-down section for selected branch: expense_no, expense_date, expense_type, description, amount, paid_to, status
    - _Requirements: 4.2, 4.3, 4.6_

  - [x] 6.4 Create `resources/views/accounting/branch/profitability.blade.php`
    - Include `_nav` and `_filter` partials
    - Table: branch name, revenue, expenses, net profit/loss, profit margin %
    - Green row class for profit > 0, red row class for profit ≤ 0
    - "No profitability data available" message when empty
    - _Requirements: 5.2, 5.3, 5.6, 5.7_

  - [x] 6.5 Create `resources/views/accounting/branch/cash-position.blade.php`
    - Include `_nav` and `_filter` partials
    - Table: branch name, opening balance, receipts, payments, closing balance
    - Consolidated totals row
    - Zero values for branches with no cash accounts/entries
    - _Requirements: 6.1, 6.2, 6.5, 6.6_

  - [x] 6.6 Create `resources/views/accounting/branch/outstanding.blade.php`
    - Include `_nav` and `_filter` partials
    - Summary table: branch, total receivable, total payable, net position, entry count
    - "No outstanding records" message when empty
    - Detail view for selected branch: party_type, party_name, invoice_ref, invoice_date, total_amount, paid_amount, pending_amount, due_date, status — paginated at 30 per page
    - Invalid date range → error message
    - _Requirements: 7.1, 7.3, 7.4, 7.6_

- [x] 7. Checkpoint - Verify views and routing
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Integration tests
  - [x]* 8.1 Write feature tests for BranchAccountingController
    - Test all 6 routes return 200 for SuperAdmin
    - Test date filter query parameters are applied correctly
    - Test correct Blade views are rendered
    - Test invalid date range shows error
    - Test pagination on outstanding detail (30 per page)
    - _Requirements: 2.3, 3.4, 4.4, 7.4, 7.6, 8.1, 8.6, 8.7_

- [x] 9. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- No new migrations required — all data is already branch-tagged
- The existing `OfficeScopeTrait` is available but the BranchAccountingService queries directly by branch for aggregation clarity

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2"] },
    { "id": 1, "tasks": ["1.3", "2.1"] },
    { "id": 2, "tasks": ["2.2", "2.3", "2.4", "2.5", "2.6", "2.7"] },
    { "id": 3, "tasks": ["2.8", "2.9", "2.10", "2.11", "2.12", "2.13"] },
    { "id": 4, "tasks": ["4.1", "4.2"] },
    { "id": 5, "tasks": ["4.3", "5.1", "5.2"] },
    { "id": 6, "tasks": ["6.1", "6.2", "6.3", "6.4", "6.5", "6.6"] },
    { "id": 7, "tasks": ["8.1"] }
  ]
}
```
