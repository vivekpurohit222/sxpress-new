# Requirements Document

## Introduction

Phase 12 adds a formal Financial Reporting System to the SXpress Accounting Module. This phase builds upon the existing double-entry bookkeeping infrastructure (Phases 1–11) to generate standard Indian accounting reports: Day Book, Trial Balance, Profit & Loss Statement, Balance Sheet, Vehicle Profitability Report, and Driver Expense Report. All reports follow the Indian Financial Year (April 1 – March 31) convention and are accessible only by the SuperAdmin role.

## Glossary

- **Financial_Report_System**: The Phase 12 reporting module comprising the FinancialReportController and FinancialReportService responsible for generating formal financial statements.
- **Day_Book**: A chronological record of all vouchers and their debit/credit entries for a selected date.
- **Trial_Balance**: A report listing all accounts with their total debit and total credit balances for a period, where total debits must equal total credits.
- **Profit_And_Loss_Statement**: An income statement showing Income accounts (credit balances) versus Expense accounts (debit balances) for a period, calculating net profit or net loss.
- **Balance_Sheet**: A statement showing Assets on the left side and Liabilities plus Equity on the right side at a specific point in time, where Assets must equal Liabilities plus Equity.
- **Vehicle_Profitability_Report**: A report showing revenue (from Freight Memos linked via truck_id) and expenses (from Expenses linked via vehicle_id) per vehicle, calculating net profit or loss per vehicle.
- **Driver_Expense_Report**: A report showing expenses grouped by driver (linked via driver_id on expenses), with breakdowns by expense type.
- **Indian_Financial_Year**: The accounting period from April 1 of one year to March 31 of the next year.
- **SuperAdmin**: The only user role with access to the accounting module and financial reports.
- **Account**: A record in the Chart of Accounts with type (asset, liability, income, expense, equity), parent_id hierarchy, is_group flag, and opening_balance.
- **LedgerEntry**: A financial movement record containing voucher_id, account_id, date, debit, credit, narration, branch, reference_type, and reference_id.
- **Voucher**: A financial document (receipt, payment, contra, journal) that groups one or more LedgerEntry records.
- **FinancialReportService**: The service class encapsulating all report calculation and aggregation logic.
- **Branch**: One of the 7 SXpress offices (Rajkot, Navagam, Shapar (1), Shapar (2), Dayabasti, Kashmore Gate, Swarup Nagar).

## Requirements

### Requirement 1: Day Book Report

**User Story:** As a SuperAdmin, I want to view all vouchers and their debit/credit entries for a selected date, so that I can review the day's complete financial activity at a glance.

#### Acceptance Criteria

1. WHEN the SuperAdmin navigates to the Day Book page, THE Financial_Report_System SHALL display all vouchers for the current date by default, showing voucher number, voucher type, narration, and total amount for each voucher.
2. WHEN the SuperAdmin selects a specific date, THE Financial_Report_System SHALL display all approved vouchers for that date with their associated LedgerEntry records showing account name, debit amount, and credit amount.
3. WHEN the SuperAdmin selects a specific branch filter, THE Financial_Report_System SHALL display only vouchers belonging to that branch.
4. WHEN no vouchers exist for the selected date, THE Financial_Report_System SHALL display a message indicating no transactions were recorded on that date.
5. THE Financial_Report_System SHALL display the total debit amount and total credit amount at the bottom of the Day Book report.
6. THE Financial_Report_System SHALL group Day Book entries by voucher, showing all debit and credit line items under each voucher.

### Requirement 2: Trial Balance Report

**User Story:** As a SuperAdmin, I want to generate a Trial Balance for a selected period, so that I can verify that total debits equal total credits across all accounts.

#### Acceptance Criteria

1. WHEN the SuperAdmin requests a Trial Balance for a date range, THE Financial_Report_System SHALL list all transactional accounts (is_group = false) that have a non-zero balance within the period, showing account code, account name, debit total, and credit total.
2. THE Financial_Report_System SHALL calculate account balances using the opening_balance of each account plus the sum of LedgerEntry debits and credits within the specified period.
3. THE Financial_Report_System SHALL display Asset and Expense account balances in the debit column when the net balance is positive (debit-normal accounts).
4. THE Financial_Report_System SHALL display Liability, Income, and Equity account balances in the credit column when the net balance is positive (credit-normal accounts).
5. THE Financial_Report_System SHALL display the total of the debit column and the total of the credit column at the bottom of the Trial Balance.
6. FOR ALL valid date ranges, the total debit column SHALL equal the total credit column in the Trial Balance (the balancing property).
7. WHEN the SuperAdmin selects a specific branch filter, THE Financial_Report_System SHALL generate a branch-specific Trial Balance using only LedgerEntry records for that branch.
8. WHEN the SuperAdmin selects "All Branches", THE Financial_Report_System SHALL generate a consolidated Trial Balance using LedgerEntry records from all branches.
9. THE Financial_Report_System SHALL group Trial Balance accounts by account type (Asset, Liability, Income, Expense, Equity) for readability.

### Requirement 3: Profit & Loss Statement

**User Story:** As a SuperAdmin, I want to generate a Profit & Loss Statement for a selected period, so that I can determine the net profit or loss of the business.

#### Acceptance Criteria

1. WHEN the SuperAdmin requests a Profit & Loss Statement for a date range, THE Financial_Report_System SHALL display all Income accounts with their credit balances for the period.
2. WHEN the SuperAdmin requests a Profit & Loss Statement for a date range, THE Financial_Report_System SHALL display all Expense accounts with their debit balances for the period.
3. THE Financial_Report_System SHALL calculate Total Income as the sum of all credit balances from accounts where type equals "income" for the period.
4. THE Financial_Report_System SHALL calculate Total Expenses as the sum of all debit balances from accounts where type equals "expense" for the period.
5. THE Financial_Report_System SHALL calculate Net Profit as Total Income minus Total Expenses, displaying it as Net Profit when positive and Net Loss when negative.
6. THE Financial_Report_System SHALL present the Profit & Loss Statement in Indian accounting format with Income section at the top and Expenses section below.
7. WHEN the SuperAdmin selects a specific branch filter, THE Financial_Report_System SHALL generate a branch-specific Profit & Loss Statement.
8. THE Financial_Report_System SHALL display Income and Expense accounts grouped by their parent account hierarchy for readability.
9. THE Financial_Report_System SHALL use the Indian Financial Year (April 1 – March 31) as the default date range when no dates are specified.

### Requirement 4: Balance Sheet

**User Story:** As a SuperAdmin, I want to generate a Balance Sheet as of a specific date, so that I can view the financial position showing assets equal to liabilities plus equity.

#### Acceptance Criteria

1. WHEN the SuperAdmin requests a Balance Sheet as of a specific date, THE Financial_Report_System SHALL display all Asset accounts with their balances (opening_balance plus net debit minus net credit from LedgerEntry records up to that date).
2. WHEN the SuperAdmin requests a Balance Sheet as of a specific date, THE Financial_Report_System SHALL display all Liability accounts with their balances (opening_balance plus net credit minus net debit from LedgerEntry records up to that date).
3. WHEN the SuperAdmin requests a Balance Sheet as of a specific date, THE Financial_Report_System SHALL display all Equity accounts with their balances (opening_balance plus net credit minus net debit from LedgerEntry records up to that date).
4. THE Financial_Report_System SHALL calculate the current period Net Profit (or Net Loss) and include it in the Equity section of the Balance Sheet.
5. THE Financial_Report_System SHALL present the Balance Sheet in T-format with Assets on the left side and Liabilities plus Equity on the right side.
6. FOR ALL valid dates, the total of Assets SHALL equal the total of Liabilities plus Equity plus current period Net Profit in the Balance Sheet (the accounting equation).
7. WHEN the SuperAdmin selects a specific branch filter, THE Financial_Report_System SHALL generate a branch-specific Balance Sheet.
8. THE Financial_Report_System SHALL display accounts grouped by their parent account hierarchy under each section (Assets, Liabilities, Equity).
9. THE Financial_Report_System SHALL default the Balance Sheet date to the current date when no date is specified.

### Requirement 5: Vehicle Profitability Report

**User Story:** As a SuperAdmin, I want to view revenue and expenses per vehicle, so that I can identify which vehicles are profitable and which are operating at a loss.

#### Acceptance Criteria

1. WHEN the SuperAdmin requests the Vehicle Profitability Report for a date range, THE Financial_Report_System SHALL display each vehicle with its vehicle number, total revenue, total expenses, and net profit or loss.
2. THE Financial_Report_System SHALL calculate vehicle revenue from Freight Memo records (frieghts table) linked via the truck_id field, summing the truck_freight amount for the period.
3. THE Financial_Report_System SHALL calculate vehicle expenses from Expense records linked via the vehicle_id field, summing the amount for approved/paid expenses for the period.
4. THE Financial_Report_System SHALL break down vehicle expenses by expense type (diesel, repair, tyre, driver_salary, misc) for each vehicle.
5. THE Financial_Report_System SHALL calculate Net Profit per vehicle as Total Revenue minus Total Expenses.
6. THE Financial_Report_System SHALL display a summary row showing total revenue, total expenses, and total net profit across all vehicles.
7. WHEN the SuperAdmin selects a specific branch filter, THE Financial_Report_System SHALL include only Freight Memos and Expenses belonging to that branch.
8. THE Financial_Report_System SHALL sort vehicles by net profit in descending order by default.
9. WHEN a vehicle has no revenue and no expenses in the selected period, THE Financial_Report_System SHALL exclude that vehicle from the report.

### Requirement 6: Driver Expense Report

**User Story:** As a SuperAdmin, I want to view expenses grouped by driver, so that I can monitor and control per-driver costs.

#### Acceptance Criteria

1. WHEN the SuperAdmin requests the Driver Expense Report for a date range, THE Financial_Report_System SHALL display each driver with their name, total expense amount, and expense count.
2. THE Financial_Report_System SHALL calculate driver expenses from Expense records linked via the driver_id field, summing the amount for approved/paid expenses for the period.
3. THE Financial_Report_System SHALL break down driver expenses by expense type (diesel, salary, repair, tyre, misc) for each driver.
4. THE Financial_Report_System SHALL display a summary row showing total expenses across all drivers.
5. WHEN the SuperAdmin selects a specific branch filter, THE Financial_Report_System SHALL include only Expenses belonging to that branch.
6. THE Financial_Report_System SHALL sort drivers by total expense amount in descending order by default.
7. WHEN a driver has no expenses in the selected period, THE Financial_Report_System SHALL exclude that driver from the report.
8. THE Financial_Report_System SHALL display the driver's associated truck number alongside the driver name.

### Requirement 7: Access Control and Navigation

**User Story:** As a SuperAdmin, I want all financial reports accessible from a single reports section in the accounting module, so that I can navigate between reports easily.

#### Acceptance Criteria

1. THE Financial_Report_System SHALL restrict access to all report routes to users with the SuperAdmin role only.
2. IF a non-SuperAdmin user attempts to access any financial report route, THEN THE Financial_Report_System SHALL return an HTTP 403 Forbidden response.
3. THE Financial_Report_System SHALL register all report routes under the /accounting/reports URL prefix with the name prefix accounting.reports.*.
4. THE Financial_Report_System SHALL provide a reports index page listing all available financial reports with navigation links.
5. THE Financial_Report_System SHALL include date range filters (from_date, to_date) on all reports that accept a period.
6. THE Financial_Report_System SHALL include a branch filter dropdown on all reports, defaulting to "All Branches".
7. THE Financial_Report_System SHALL display all monetary values formatted in Indian number format (e.g., 1,00,000.00) with two decimal places.

### Requirement 8: Print and Export Support

**User Story:** As a SuperAdmin, I want to print financial reports, so that I can produce physical copies for records and auditing.

#### Acceptance Criteria

1. THE Financial_Report_System SHALL provide a print-friendly view for each financial report accessible via a print button.
2. WHEN the SuperAdmin clicks the print button, THE Financial_Report_System SHALL render the report in a print-optimized layout with company header, report title, date range, and generation timestamp.
3. THE Financial_Report_System SHALL hide navigation elements, filters, and action buttons in the print view.
