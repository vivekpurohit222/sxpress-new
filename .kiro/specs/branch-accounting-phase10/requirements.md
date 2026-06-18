# Requirements Document

## Introduction

Phase 10 of the SXpress Accounting Module introduces Branch Accounting with branch-level financial reports. The system already maintains branch-tagged data across ledger entries, vouchers, expenses, and outstanding records. This phase builds consolidated and per-branch reporting capabilities exclusively for SuperAdmin, and tightens access control across all existing accounting routes to SuperAdmin-only.

## Glossary

- **Branch_Accounting_System**: The set of controllers, views, and routes that provide branch-level financial reports within the SXpress Accounting Module.
- **SuperAdmin**: The only role permitted to access accounting features. Has visibility across all branches and consolidated views.
- **Branch**: One of the seven SXpress offices — Rajkot, Navagam, Shapar (1), Shapar (2), Dayabasti, Kashmore Gate, Swarup Nagar.
- **Revenue**: Freight income recorded via GR creation (ledger credits to Freight Income accounts) attributed to a branch.
- **Expense**: All expenses recorded in the expenses table attributed to a branch.
- **Profitability**: The calculated difference between branch revenue and branch expenses for a given period.
- **Cash_Position**: The net balance of cash accounts (Cash In Hand, Petty Cash) for a branch at a point in time.
- **Outstanding**: Receivable and payable amounts tracked in the outstanding table attributed to a branch.
- **Date_Range_Filter**: A from_date and to_date pair used to scope report data to a specific period.
- **Consolidated_View**: An aggregate view showing data across all branches simultaneously.
- **BranchAccountingController**: The Laravel controller responsible for serving branch-level financial reports.
- **Existing_Accounting_Controllers**: AccountController, LedgerController, VoucherController, CashBookController, BankBookController, OutstandingController, ExpenseController.
- **OfficeScopeTrait**: An existing trait that applies branch-level query scoping; SuperAdmin sees all branches unless impersonating.

## Requirements

### Requirement 1: Restrict Accounting Access to SuperAdmin Only

**User Story:** As the system owner, I want all accounting features restricted exclusively to the SuperAdmin role, so that sensitive financial data is protected from unauthorized access.

#### Acceptance Criteria

1. THE Branch_Accounting_System SHALL enforce `role:SuperAdmin` middleware on all accounting routes including routes for AccountController, LedgerController, VoucherController, CashBookController, BankBookController, OutstandingController, and ExpenseController.
2. WHEN a user with role Admin, Manager, Staff, or Viewer attempts to access any accounting route, THE Branch_Accounting_System SHALL return an HTTP 403 Forbidden response and display an error message indicating insufficient permissions.
3. THE Branch_Accounting_System SHALL apply `role:SuperAdmin` middleware in both the route definition (routes/web.php) and the controller constructor for each of the 7 accounting controllers: AccountController, LedgerController, VoucherController, CashBookController, BankBookController, OutstandingController, and ExpenseController.
4. WHEN an unauthenticated user attempts to access any accounting route, THE Branch_Accounting_System SHALL redirect the user to the login page without revealing the existence of the accounting route.
5. IF a user holds the SuperAdmin role in combination with any other role, THEN THE Branch_Accounting_System SHALL grant access to accounting routes based solely on the presence of the SuperAdmin role.

### Requirement 2: Branch Accounting Dashboard

**User Story:** As a SuperAdmin, I want a dashboard showing summary financial cards for each branch, so that I can quickly assess the financial health of all branches at a glance.

#### Acceptance Criteria

1. WHEN the SuperAdmin navigates to `/accounting/branch`, THE BranchAccountingController SHALL display a dashboard with summary cards for each of the seven branches (Rajkot, Navagam, Shapar (1), Shapar (2), Dayabasti, Kashmore Gate, Swarup Nagar).
2. THE BranchAccountingController SHALL display the following metrics per branch on the dashboard: total revenue (sum of credit amounts from income-type account ledger entries), total expenses (sum of amounts from expenses table where status is not 'rejected'), net profitability (revenue minus expenses), current cash position (net balance of accounts 1101 and 1102), and total outstanding amount (sum of pending_amount from outstanding table where status is not 'paid').
3. WHEN a Date_Range_Filter is applied, THE BranchAccountingController SHALL recalculate all dashboard metrics using only transactions within the specified from_date and to_date range (inclusive).
4. WHEN no Date_Range_Filter is applied, THE BranchAccountingController SHALL default to the current financial year (April 1 to March 31).
5. THE BranchAccountingController SHALL display a consolidated total row summing all branch metrics across revenue, expenses, profitability, cash position, and outstanding.
6. IF a branch has no financial transactions within the selected date range, THEN THE BranchAccountingController SHALL display zero values for all metrics for that branch.

### Requirement 3: Branch Revenue Report

**User Story:** As a SuperAdmin, I want to view freight income broken down by branch with date filtering, so that I can analyze revenue generation per office.

#### Acceptance Criteria

1. WHEN the SuperAdmin navigates to `/accounting/branch/revenue`, THE BranchAccountingController SHALL display revenue data for all branches that have at least one ledger entry with a credit amount greater than zero in a freight-related income account.
2. THE BranchAccountingController SHALL calculate revenue by summing credit amounts from ledger_entries where the associated account has type "income" (all income-type accounts in the chart of accounts).
3. WHEN a specific branch is selected, THE BranchAccountingController SHALL display detailed revenue line items filtered to that branch only, showing each GR-linked entry (reference_type='gr') with columns for GR number (reference_id), date, account name, and credit amount.
4. WHEN a Date_Range_Filter is applied with both a start date and an end date, THE BranchAccountingController SHALL filter revenue data to include only ledger entries where the date column falls within the specified range (inclusive of both start and end dates).
5. IF no Date_Range_Filter is applied, THEN THE BranchAccountingController SHALL default to displaying revenue data for the current financial year (April 1 of the current fiscal year through today's date).
6. THE BranchAccountingController SHALL display revenue comparison across all branches in a tabular format with columns for branch name, total revenue (sum of credit amounts), and percentage of total (calculated as branch revenue divided by sum of all branches' revenue, displayed to two decimal places).
7. IF no revenue data exists for the applied filters, THEN THE BranchAccountingController SHALL display the table structure with a message indicating that no revenue records were found for the selected criteria.

### Requirement 4: Branch Expense Report

**User Story:** As a SuperAdmin, I want to view expenses broken down by branch and expense type, so that I can monitor spending patterns across offices.

#### Acceptance Criteria

1. WHEN the SuperAdmin navigates to `/accounting/branch/expenses`, THE BranchAccountingController SHALL display expense data for all branches, including only expenses where status is not "rejected".
2. THE BranchAccountingController SHALL retrieve expense data from the expenses table grouped by branch and expense_type (diesel, driver_salary, repair, tyre, office, branch, misc), displaying the count of expenses and total amount per group.
3. WHEN a specific branch is selected, THE BranchAccountingController SHALL display detailed expense line items for that branch showing: expense_no, expense_date, expense_type, description, amount, paid_to, and status.
4. WHEN a Date_Range_Filter is applied, THE BranchAccountingController SHALL filter expense data to include only entries where expense_date falls within the specified from_date and to_date (inclusive).
5. WHEN no Date_Range_Filter is applied, THE BranchAccountingController SHALL default to the current financial year (April 1 to March 31).
6. THE BranchAccountingController SHALL display expense comparison across all branches in a tabular format with columns for each expense_type, a total column per branch, and a consolidated total row summing all branches.

### Requirement 5: Branch Profitability Report

**User Story:** As a SuperAdmin, I want to compare profitability (revenue minus expenses) across all branches, so that I can identify high-performing and underperforming offices.

#### Acceptance Criteria

1. WHEN the SuperAdmin navigates to `/accounting/branch/profitability`, THE BranchAccountingController SHALL display a profitability comparison for all branches, with the date range defaulting to the current financial year (April 1 of the current year through March 31 of the following year).
2. THE BranchAccountingController SHALL calculate revenue for each branch as the sum of credit amounts from ledger_entries associated with income-type accounts for that branch within the selected date range, and calculate expenses as the sum of amounts from the expenses table for that branch within the selected date range excluding records with status 'rejected'.
3. THE BranchAccountingController SHALL display for each branch: branch name, total revenue, total expenses, net profit or loss amount (revenue minus expenses), and profit margin percentage calculated as ((revenue minus expenses) divided by revenue) multiplied by 100, rounded to two decimal places.
4. IF a branch has zero revenue within the selected date range, THEN THE BranchAccountingController SHALL display the profit margin as 0% for that branch.
5. WHEN a Date_Range_Filter is applied, THE BranchAccountingController SHALL recalculate all profitability metrics (revenue, expenses, net profit/loss, profit margin) using only transactions whose date falls within the specified start and end dates inclusive.
6. THE BranchAccountingController SHALL apply a green Bootstrap CSS class to rows where net profit is greater than zero, and a red Bootstrap CSS class to rows where net profit is less than or equal to zero.
7. IF no branches exist or no financial data is available for any branch within the selected date range, THEN THE BranchAccountingController SHALL display a message indicating that no profitability data is available for the selected period.

### Requirement 6: Branch Cash Position Report

**User Story:** As a SuperAdmin, I want to view the current cash balance at each branch, so that I can manage cash distribution across offices.

#### Acceptance Criteria

1. WHEN the SuperAdmin navigates to `/accounting/branch/cash-position`, THE BranchAccountingController SHALL display a table with one row per active branch showing: branch name, opening cash balance, total cash receipts, total cash payments, and closing cash balance.
2. THE BranchAccountingController SHALL calculate cash position using cash-type accounts (account codes 1101 and 1102) per branch, where opening balance equals the sum of account opening_balance fields plus sum of ledger_entry debits minus sum of ledger_entry credits dated before the from_date, total cash receipts equals sum of debit entries within the date range, total cash payments equals sum of credit entries within the date range, and closing balance equals opening balance plus total cash receipts minus total cash payments.
3. WHEN a Date_Range_Filter is applied with from_date and to_date, THE BranchAccountingController SHALL compute opening balance as of the from_date and closing balance as of the to_date for each branch.
4. IF no Date_Range_Filter is applied, THEN THE BranchAccountingController SHALL default the date range to the current financial year (April 1 of the current financial year to March 31 of the following year).
5. THE BranchAccountingController SHALL display a consolidated totals row summing opening cash balance, total cash receipts, total cash payments, and closing cash balance across all active branches.
6. IF a branch has no cash-type accounts or no ledger entries within the date range, THEN THE BranchAccountingController SHALL display that branch row with zero values for opening balance, receipts, payments, and closing balance.

### Requirement 7: Branch Outstanding Report

**User Story:** As a SuperAdmin, I want to see receivable and payable outstanding amounts per branch, so that I can track collection and payment obligations across offices.

#### Acceptance Criteria

1. WHEN the SuperAdmin navigates to `/accounting/branch/outstanding`, THE BranchAccountingController SHALL display a summary table listing each branch with its total receivable pending amount, total payable pending amount, net outstanding position (total receivable pending minus total payable pending), and count of non-paid entries (status in pending, partial, overdue).
2. THE BranchAccountingController SHALL retrieve outstanding data from the outstanding table filtering only non-paid entries (status != 'paid') and grouping results by branch and type (receivable, payable).
3. WHEN no non-paid outstanding entries exist for any branch, THE BranchAccountingController SHALL display the summary table with a message indicating no outstanding records are available.
4. WHEN a specific branch is selected, THE BranchAccountingController SHALL display the detailed outstanding line items for that branch grouped by party_name, showing for each entry: party_type, party_name, invoice_ref, invoice_date, total_amount, paid_amount, pending_amount, due_date, and status, paginated at a maximum of 30 entries per page.
5. WHEN a Date_Range_Filter is applied, THE BranchAccountingController SHALL filter outstanding records where invoice_date falls within the specified start date and end date (inclusive).
6. IF the Date_Range_Filter end date is earlier than the start date, THEN THE BranchAccountingController SHALL reject the filter and display an error message indicating the date range is invalid.
7. IF no Date_Range_Filter is applied, THEN THE BranchAccountingController SHALL display all non-paid outstanding records regardless of invoice_date.

### Requirement 8: Branch Report Routing and Navigation

**User Story:** As a SuperAdmin, I want all branch reports accessible under a consistent URL structure with navigation links, so that I can move between reports without confusion.

#### Acceptance Criteria

1. THE Branch_Accounting_System SHALL register all branch accounting routes under the `/accounting/branch` URL prefix with the route name prefix `accounting.branch.`, providing routes for: Dashboard (`accounting.branch.dashboard`), Revenue (`accounting.branch.revenue`), Expenses (`accounting.branch.expenses`), Profitability (`accounting.branch.profitability`), Cash Position (`accounting.branch.cash-position`), and Outstanding (`accounting.branch.outstanding`).
2. THE Branch_Accounting_System SHALL apply `role:SuperAdmin` middleware to all branch accounting routes.
3. IF a non-SuperAdmin user requests any route under the `/accounting/branch` prefix, THEN THE Branch_Accounting_System SHALL deny access and redirect to the application's default unauthorized page without displaying branch report content.
4. THE Branch_Accounting_System SHALL display a navigation component containing links to all 6 branch reports (Dashboard, Revenue, Expenses, Profitability, Cash Position, Outstanding) and SHALL visually distinguish the currently active page link by applying an `active` CSS class to the corresponding navigation item.
5. THE Branch_Accounting_System SHALL render all branch accounting views using Blade templates located in `resources/views/accounting/branch/` directory.
6. WHEN the SuperAdmin loads any branch report page without specifying date parameters, THE Branch_Accounting_System SHALL display the Date_Range_Filter controls with `from_date` defaulting to the first day of the current month and `to_date` defaulting to today's date, both rendered as HTML5 `type="date"` inputs accompanied by a filter submit button.
7. WHEN the SuperAdmin submits the Date_Range_Filter on any branch report page, THE Branch_Accounting_System SHALL reload the current report page with the selected `from_date` and `to_date` applied as query parameters and the Date_Range_Filter inputs SHALL retain the submitted values.
