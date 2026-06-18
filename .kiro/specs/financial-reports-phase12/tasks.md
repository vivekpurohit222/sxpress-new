# Implementation Plan: Financial Reporting System (Phase 12)

## Overview

This plan implements the Phase 12 Financial Reporting System for the SXpress Accounting Module. It adds six formal reports — Day Book, Trial Balance, Profit & Loss Statement, Balance Sheet, Vehicle Profitability Report, and Driver Expense Report — all accessible exclusively by SuperAdmin. No migrations are needed; the system reads from existing accounting tables (accounts, ledger_entries, vouchers, expenses, frieghts, truckdrivers). All code is PHP 8.1+ / Laravel 10.

## Tasks

- [x] 1. Indian Number Format Helper
  - [x] 1.1 Create NumberHelper with indianNumberFormat function
    - File: `app/Helpers/NumberHelper.php`
    - Implement `indianNumberFormat(float $number, int $decimals = 2): string`
    - Logic: last 3 digits grouped, preceding digits grouped in pairs, separated by commas
    - Handle negative numbers (prefix with `-`)
    - Handle zero and small numbers (< 1000) without leading commas
    - Register helper in `composer.json` autoload files array
    - _Requirements: 7.7_

- [x] 2. FinancialReportService
  - [x] 2.1 Create FinancialReportService with constructor and getDayBook method
    - File: `app/Services/FinancialReportService.php`
    - Namespace: `App\Services`
    - Constructor: inject `BranchAccountingService`
    - Method `getDayBook(string $date, ?string $branch = null): array`
    - Query vouchers for the given date with eager-loaded ledgerEntries and account names
    - Apply optional branch filter on ledger_entries.branch
    - Return: `['vouchers' => Collection, 'totals' => ['debit' => float, 'credit' => float]]`
    - Group entries by voucher, show voucher_no, type, narration, total amount
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6_

  - [x] 2.2 Add getTrialBalance method to FinancialReportService
    - Method `getTrialBalance(string $fromDate, string $toDate, ?string $branch = null): array`
    - Query all transactional accounts (is_group = 0) with period ledger movements
    - Calculate balance per account: opening_balance + debit - credit (for asset/expense) or opening_balance + credit - debit (for liability/income/equity)
    - Filter out zero-balance accounts
    - Place positive debit-normal balances in debit column, positive credit-normal in credit column
    - Negative balances go in opposite column as absolute values
    - Group by account type (asset, liability, income, expense, equity)
    - Return: `['accounts' => Collection, 'totals' => ['debit' => float, 'credit' => float]]`
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9_

  - [x] 2.3 Add getProfitAndLoss method to FinancialReportService
    - Method `getProfitAndLoss(string $fromDate, string $toDate, ?string $branch = null): array`
    - Query income accounts: sum credits - debits for period (no opening_balance)
    - Query expense accounts: sum debits - credits for period (no opening_balance)
    - Filter out zero-balance accounts
    - Group by parent account hierarchy
    - Calculate totals and net profit (total_income - total_expenses)
    - Return: `['income' => Collection, 'expenses' => Collection, 'total_income' => float, 'total_expenses' => float, 'net_profit' => float]`
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9_

  - [x] 2.4 Add getBalanceSheet method to FinancialReportService
    - Method `getBalanceSheet(string $asOfDate, ?string $branch = null): array`
    - Query asset accounts: opening_balance + total_debit - total_credit up to asOfDate
    - Query liability accounts: opening_balance + total_credit - total_debit up to asOfDate
    - Query equity accounts: opening_balance + total_credit - total_debit up to asOfDate
    - Calculate current period net profit (FY start to asOfDate) via private `calculateNetProfit()`
    - Include net profit in equity section
    - Group accounts by parent hierarchy
    - Return: `['assets' => Collection, 'liabilities' => Collection, 'equity' => Collection, 'net_profit' => float, 'total_assets' => float, 'total_liabilities_equity' => float]`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9_

  - [x] 2.5 Add getVehicleProfitability method to FinancialReportService
    - Method `getVehicleProfitability(string $fromDate, string $toDate, ?string $branch = null): array`
    - Query vehicle revenue from `frieghts` table: SUM(truck_freight) grouped by truck_id, filtered by fm_date range and optional branch (office field)
    - Query vehicle expenses from `expenses` table: SUM(amount) grouped by vehicle_id and expense_type, filtered by expense_date range, status IN ('approved','paid'), and optional branch
    - Join results in PHP per vehicle: vehicle_number, total_revenue, expense_breakdown (diesel, repair, tyre, driver_salary, misc), total_expenses, net_profit
    - Exclude vehicles with zero revenue AND zero expenses
    - Sort by net_profit descending
    - Calculate summary totals (total revenue, total expenses, total net profit)
    - Return: `['vehicles' => Collection, 'totals' => ['revenue' => float, 'expenses' => float, 'net_profit' => float]]`
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9_

  - [x] 2.6 Add getDriverExpenses method to FinancialReportService
    - Method `getDriverExpenses(string $fromDate, string $toDate, ?string $branch = null): array`
    - Query expenses grouped by driver_id and expense_type, joined with truckdrivers table
    - Filter: driver_id IS NOT NULL, status IN ('approved','paid'), expense_date range, optional branch
    - Return per driver: driver_name, truck_no, expense_count, expense_breakdown by type, total_expenses
    - Exclude drivers with zero expenses
    - Sort by total_expenses descending
    - Calculate summary total across all drivers
    - Return: `['drivers' => Collection, 'total_expenses' => float]`
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8_

- [x] 3. Checkpoint - Verify service layer
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. FinancialReportController
  - [x] 4.1 Create FinancialReportController with constructor and index action
    - File: `app/Http/Controllers/Accounting/FinancialReportController.php`
    - Namespace: `App\Http\Controllers\Accounting`
    - Constructor: inject FinancialReportService and BranchAccountingService, apply middleware `['auth', 'role:SuperAdmin']`
    - Method `index()`: render reports index page listing all 6 reports with navigation cards
    - _Requirements: 7.1, 7.3, 7.4_

  - [x] 4.2 Add dayBook action to FinancialReportController
    - Method `dayBook(Request $request)`: resolve date (default today), resolve branch filter, call `$reportService->getDayBook()`, pass data to view
    - Pass to view: vouchers, totals, date, selectedBranch, branches list
    - _Requirements: 1.1, 1.2, 1.3, 7.5, 7.6_

  - [x] 4.3 Add trialBalance action to FinancialReportController
    - Method `trialBalance(Request $request)`: resolve date range (default FY via BranchAccountingService), validate to_date >= from_date, resolve branch, call `$reportService->getTrialBalance()`, pass data to view
    - Pass to view: accounts, totals, fromDate, toDate, selectedBranch, branches list
    - _Requirements: 2.1, 2.7, 2.8, 7.5, 7.6_

  - [x] 4.4 Add profitAndLoss action to FinancialReportController
    - Method `profitAndLoss(Request $request)`: resolve date range (default FY), validate, resolve branch, call `$reportService->getProfitAndLoss()`, pass data to view
    - Pass to view: income, expenses, total_income, total_expenses, net_profit, fromDate, toDate, selectedBranch, branches list
    - _Requirements: 3.1, 3.7, 3.9, 7.5, 7.6_

  - [x] 4.5 Add balanceSheet action to FinancialReportController
    - Method `balanceSheet(Request $request)`: resolve as_of_date (default today), resolve branch, call `$reportService->getBalanceSheet()`, pass data to view
    - Pass to view: assets, liabilities, equity, net_profit, total_assets, total_liabilities_equity, asOfDate, selectedBranch, branches list
    - _Requirements: 4.1, 4.7, 4.9, 7.5, 7.6_

  - [x] 4.6 Add vehicleProfitability action to FinancialReportController
    - Method `vehicleProfitability(Request $request)`: resolve date range (default FY), validate, resolve branch, call `$reportService->getVehicleProfitability()`, pass data to view
    - Pass to view: vehicles, totals, fromDate, toDate, selectedBranch, branches list
    - _Requirements: 5.1, 5.7, 7.5, 7.6_

  - [x] 4.7 Add driverExpenses action to FinancialReportController
    - Method `driverExpenses(Request $request)`: resolve date range (default FY), validate, resolve branch, call `$reportService->getDriverExpenses()`, pass data to view
    - Pass to view: drivers, total_expenses, fromDate, toDate, selectedBranch, branches list
    - _Requirements: 6.1, 6.5, 7.5, 7.6_

- [x] 5. Routes
  - [x] 5.1 Register financial report routes in web.php
    - Add route group after existing accounting routes (after the GST report group)
    - Middleware: ['role:SuperAdmin'], prefix: 'accounting/reports', name: 'accounting.reports.'
    - GET / → index (name: index)
    - GET /day-book → dayBook (name: day-book)
    - GET /trial-balance → trialBalance (name: trial-balance)
    - GET /profit-and-loss → profitAndLoss (name: profit-and-loss)
    - GET /balance-sheet → balanceSheet (name: balance-sheet)
    - GET /vehicle-profitability → vehicleProfitability (name: vehicle-profitability)
    - GET /driver-expenses → driverExpenses (name: driver-expenses)
    - Wrap inside existing `Route::middleware(['auth'])->group()` block
    - _Requirements: 7.1, 7.2, 7.3_

- [x] 6. Blade Views
  - [x] 6.1 Create report filter and print-header partials
    - File: `resources/views/accounting/reports/_filters.blade.php`
    - Date range inputs (from_date, to_date or single date for Day Book/Balance Sheet) with current values preserved
    - Branch dropdown (all 7 branches + "All Branches" default)
    - Submit button to apply filters
    - File: `resources/views/accounting/reports/_print-header.blade.php`
    - Company name header, report title, date range, generation timestamp
    - Hidden in screen view, visible in print media
    - _Requirements: 7.5, 7.6, 8.1, 8.2, 8.3_

  - [x] 6.2 Create reports index view
    - File: `resources/views/accounting/reports/index.blade.php`
    - Extends `admin.layout.master`
    - Display 6 report cards with title, description, and link to each report
    - Cards: Day Book, Trial Balance, Profit & Loss, Balance Sheet, Vehicle Profitability, Driver Expenses
    - _Requirements: 7.4_

  - [x] 6.3 Create Day Book view
    - File: `resources/views/accounting/reports/day-book.blade.php`
    - Extends `admin.layout.master`, includes `_filters` partial (single date picker + branch)
    - Display vouchers grouped: voucher_no, type, narration, then nested debit/credit lines (account name, debit, credit)
    - Total debit and credit at bottom
    - "No transactions recorded" message when empty
    - All amounts formatted with `indianNumberFormat()`
    - Print button triggering `window.print()` with `_print-header` partial
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 7.7, 8.1_

  - [x] 6.4 Create Trial Balance view
    - File: `resources/views/accounting/reports/trial-balance.blade.php`
    - Extends `admin.layout.master`, includes `_filters` partial (date range + branch)
    - Table columns: Account Code, Account Name, Debit (₹), Credit (₹)
    - Grouped by account type with section headers (Assets, Liabilities, Income, Expenses, Equity)
    - Total row at bottom showing debit total = credit total
    - Indian number formatting, print button
    - _Requirements: 2.1, 2.5, 2.9, 7.7, 8.1_

  - [x] 6.5 Create Profit & Loss Statement view
    - File: `resources/views/accounting/reports/profit-and-loss.blade.php`
    - Extends `admin.layout.master`, includes `_filters` partial (date range + branch)
    - Indian accounting format: Income section at top, Expenses section below
    - Accounts grouped by parent hierarchy with subtotals
    - Total Income, Total Expenses, Net Profit (or Net Loss in red)
    - Indian number formatting, print button
    - _Requirements: 3.1, 3.2, 3.5, 3.6, 3.8, 7.7, 8.1_

  - [x] 6.6 Create Balance Sheet view
    - File: `resources/views/accounting/reports/balance-sheet.blade.php`
    - Extends `admin.layout.master`, includes `_filters` partial (single date + branch)
    - T-format layout: Assets on left side, Liabilities + Equity on right side
    - Equity section includes current period Net Profit line
    - Accounts grouped by parent hierarchy
    - Total Assets = Total Liabilities + Equity (displayed at bottom of each side)
    - Indian number formatting, print button
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.8, 7.7, 8.1_

  - [x] 6.7 Create Vehicle Profitability Report view
    - File: `resources/views/accounting/reports/vehicle-profitability.blade.php`
    - Extends `admin.layout.master`, includes `_filters` partial (date range + branch)
    - Table columns: Vehicle No, Total Revenue, Diesel, Repair, Tyre, Driver Salary, Misc, Total Expenses, Net Profit/Loss
    - Net loss values displayed in red
    - Summary row at bottom with totals
    - Sorted by net profit descending
    - Indian number formatting, print button
    - _Requirements: 5.1, 5.4, 5.5, 5.6, 5.8, 7.7, 8.1_

  - [x] 6.8 Create Driver Expense Report view
    - File: `resources/views/accounting/reports/driver-expenses.blade.php`
    - Extends `admin.layout.master`, includes `_filters` partial (date range + branch)
    - Table columns: Driver Name, Truck No, Diesel, Salary, Repair, Tyre, Misc, Total Expenses, Count
    - Summary row at bottom with total expenses
    - Sorted by total expenses descending
    - Indian number formatting, print button
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.6, 6.8, 7.7, 8.1_

- [x] 7. Navigation Integration
  - [x] 7.1 Add Financial Reports menu to accounting sidebar
    - Locate the accounting sidebar/navigation partial
    - Add "Financial Reports" section with icon (fa-chart-bar or similar)
    - Sub-links: Reports Index, Day Book, Trial Balance, P&L, Balance Sheet, Vehicle Profitability, Driver Expenses
    - Active state: highlight when any `accounting.reports.*` route is active
    - Position after GST Reports menu items
    - _Requirements: 7.3, 7.4_

- [x] 8. Checkpoint - Verify views and routing
  - Ensure all tests pass, ask the user if questions arise.

- [x] 9. Tests
  - [x] 9.1 Write FinancialReportService unit tests
    - File: `tests/Feature/FinancialReportServiceTest.php`
    - Test getDayBook returns vouchers for correct date only
    - Test getDayBook groups entries by voucher
    - Test getDayBook shows "no transactions" when empty date
    - Test getTrialBalance groups accounts by type
    - Test getTrialBalance with branch filter
    - Test getProfitAndLoss defaults to FY dates
    - Test getProfitAndLoss income before expenses layout
    - Test getBalanceSheet defaults to today
    - Test getVehicleProfitability excludes zero-activity vehicles
    - Test getDriverExpenses excludes zero-expense drivers
    - Test getDriverExpenses includes truck_no for each driver
    - Test indianNumberFormat: 100000 → "1,00,000.00"
    - Test indianNumberFormat: 1234567.89 → "12,34,567.89"
    - Test indianNumberFormat: 999 → "999.00"
    - Test indianNumberFormat: negative numbers
    - _Requirements: 1.1, 1.4, 1.6, 2.9, 3.9, 4.9, 5.9, 6.7, 6.8, 7.7_

  - [x] 9.2 Write FinancialReportController integration tests
    - File: `tests/Feature/FinancialReportTest.php`
    - Test SuperAdmin can access all 7 report routes (200 response)
    - Test non-SuperAdmin gets 403 on all routes
    - Test unauthenticated user gets redirected to login
    - Test reports index lists all 6 reports
    - Test date range validation: to_date < from_date redirects with error
    - Test branch filter dropdown populated with 7 branches
    - Test Day Book with specific date returns only that date's vouchers
    - Test Trial Balance totals: debit column = credit column
    - Test Balance Sheet: total assets = total liabilities + equity
    - Test print view renders without navigation elements
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 8.3_

  - [x] 9.3 Write property test: Trial Balance Balancing Invariant (Property 1)
    - **Property 1: Trial Balance Balancing Invariant**
    - Generate random balanced voucher sets, verify total debit column = total credit column for any date range
    - **Validates: Requirements 2.6**

  - [x] 9.4 Write property test: Balance Sheet Accounting Equation (Property 2)
    - **Property 2: Balance Sheet Accounting Equation**
    - Generate random accounts and balanced vouchers, verify Total Assets = Total Liabilities + Equity + Net Profit
    - **Validates: Requirements 4.6**

  - [x] 9.5 Write property test: Trial Balance Correct Balance Placement (Property 3)
    - **Property 3: Trial Balance Correct Balance Placement**
    - Generate random accounts with various types and balances, verify debit-normal positive balances in debit column, credit-normal in credit column
    - **Validates: Requirements 2.1, 2.2, 2.3, 2.4**

  - [x] 9.6 Write property test: Profit & Loss Net Profit Calculation (Property 4)
    - **Property 4: Profit & Loss Net Profit Calculation**
    - Generate random income and expense ledger entries, verify net_profit = sum(income credits) - sum(expense debits)
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**

  - [x] 9.7 Write property test: Vehicle Profitability Per-Vehicle Net Calculation (Property 5)
    - **Property 5: Vehicle Profitability Per-Vehicle Net Calculation**
    - Generate random freight records and expenses per vehicle, verify net = revenue - expenses for each vehicle
    - **Validates: Requirements 5.2, 5.3, 5.5**

  - [x] 9.8 Write property test: Summary Row Equals Sum of Parts (Property 6)
    - **Property 6: Summary Row Equals Sum of Parts**
    - Generate random report data, verify summary totals = sum of individual row values
    - **Validates: Requirements 5.4, 5.6, 6.3, 6.4**

  - [x] 9.9 Write property test: Branch Filter Isolation (Property 7)
    - **Property 7: Branch Filter Isolation**
    - Generate data across multiple branches, apply branch filter, verify no cross-branch data leaks
    - **Validates: Requirements 1.3, 2.7, 3.7, 4.7, 5.7, 6.5**

  - [x] 9.10 Write property test: Indian Number Format Correctness (Property 8)
    - **Property 8: Indian Number Format Correctness**
    - Generate random float values (small to large), verify output matches Indian format regex pattern
    - **Validates: Requirements 7.7**

  - [x] 9.11 Write property test: Descending Sort Order (Property 9)
    - **Property 9: Descending Sort Order**
    - Generate random vehicle/driver data, verify results sorted by net_profit/total_expenses descending
    - **Validates: Requirements 5.8, 6.6**

  - [x] 9.12 Write property test: Day Book Date Filter Completeness (Property 10)
    - **Property 10: Day Book Date Filter Completeness**
    - Generate vouchers across multiple dates, verify Day Book includes all and only matching-date vouchers
    - **Validates: Requirements 1.2**

- [x] 10. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- No database migrations needed — all data already exists in accounts, ledger_entries, vouchers, expenses, frieghts, and truckdrivers tables
- Views use Bootstrap 5 tables consistent with Phase 10/11 accounting views
- All monetary values use Indian number formatting (₹1,00,000.00)
- Indian Financial Year (April 1 – March 31) used as default date range
- Property test file: `tests/Feature/FinancialReportPropertyTest.php`

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["2.1", "2.2", "2.3", "2.4", "2.5", "2.6"] },
    { "id": 2, "tasks": ["4.1", "4.2", "4.3", "4.4", "4.5", "4.6", "4.7"] },
    { "id": 3, "tasks": ["5.1"] },
    { "id": 4, "tasks": ["6.1", "6.2", "6.3", "6.4", "6.5", "6.6", "6.7", "6.8"] },
    { "id": 5, "tasks": ["7.1"] },
    { "id": 6, "tasks": ["9.1", "9.2", "9.3", "9.4", "9.5", "9.6", "9.7", "9.8", "9.9", "9.10", "9.11", "9.12"] }
  ]
}
```
