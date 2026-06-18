# Implementation Plan: GST Support (Phase 11)

## Overview

This plan implements GST (Goods and Services Tax) data storage and reporting for the SXpress Accounting Module. It adds GST fields to existing tables, creates a dedicated `gst_entries` table, builds a GstService for calculation and report aggregation, a GstReportController with five report views, GST settings management, and corresponding Blade views. All code is PHP 8.1+ / Laravel 10.

## Tasks

- [x] 1. Database Migrations
  - [x] 1.1 Create migration to add GST fields to `grs` table
    - File: `database/migrations/xxxx_xx_xx_add_gst_fields_to_grs_table.php`
    - Add columns after `total_amount`: gst_rate decimal(5,2) nullable, gst_type enum('cgst_sgst','igst') nullable, cgst_amount decimal(12,2) nullable default(0), sgst_amount decimal(12,2) nullable default(0), igst_amount decimal(12,2) nullable default(0), gst_total decimal(12,2) nullable default(0)
    - Down method drops the added columns
    - Existing GR records remain unaffected (all null)
    - _Requirements: 1.1, 1.2, 1.3_

  - [x] 1.2 Create migration to add GST fields to `expenses` table
    - File: `database/migrations/xxxx_xx_xx_add_gst_fields_to_expenses_table.php`
    - Add columns after `amount`: gst_rate decimal(5,2) nullable, gst_type enum('cgst_sgst','igst') nullable, cgst_amount decimal(12,2) nullable default(0), sgst_amount decimal(12,2) nullable default(0), igst_amount decimal(12,2) nullable default(0), gst_total decimal(12,2) nullable default(0), vendor_gst_number varchar(20) nullable
    - Down method drops the added columns
    - _Requirements: 1.1, 1.2, 1.3_

  - [x] 1.3 Create migration for `gst_entries` table
    - File: `database/migrations/xxxx_xx_xx_create_gst_entries_table.php`
    - Columns: id, taxable_type, taxable_id (morphs), transaction_date, party_name, party_gst_number(20) nullable, gst_rate decimal(5,2), taxable_value decimal(12,2), cgst_amount decimal(12,2) default(0), sgst_amount decimal(12,2) default(0), igst_amount decimal(12,2) default(0), total_tax decimal(12,2), tax_direction enum('output','input'), gst_type enum('cgst_sgst','igst'), branch varchar, hsn_sac_code varchar(10) nullable default('996511'), timestamps
    - Add indexes: [transaction_date, tax_direction], [branch, transaction_date], [tax_direction]
    - Down method drops table
    - _Requirements: 1.4, 1.5, 1.6_

  - [x] 1.4 Create migration for `gst_settings` table with seed data
    - File: `database/migrations/xxxx_xx_xx_create_gst_settings_table.php`
    - Columns: id, key (unique), value (nullable), timestamps
    - Seed 3 rows: company_gst_number (null), default_gst_rate ('5'), company_state ('Gujarat')
    - Down method drops table
    - _Requirements: 10.1, 10.2, 10.3_

  - [x] 1.5 Run migrations to verify they execute without errors
    - Run `php artisan migrate` and verify all 4 migrations pass
    - Verify existing GR and expense data is preserved (no data loss)
    - _Requirements: 1.1_

- [x] 2. Models
  - [x] 2.1 Create GstEntry model
    - File: `app/Models/Accounting/GstEntry.php`
    - Namespace: `App\Models\Accounting`
    - Table: `gst_entries`
    - Fillable: taxable_type, taxable_id, transaction_date, party_name, party_gst_number, gst_rate, taxable_value, cgst_amount, sgst_amount, igst_amount, total_tax, tax_direction, gst_type, branch, hsn_sac_code
    - Casts: transaction_date → date, gst_rate/taxable_value/cgst_amount/sgst_amount/igst_amount/total_tax → decimal:2
    - Relationship: `taxable()` → morphTo()
    - Scopes: `output()`, `input()`, `forBranch($branch)`, `dateRange($from, $to)`, `ofGstType($type)`, `forRate($rate)`
    - _Requirements: 1.4, 1.5, 1.6_

  - [x] 2.2 Create GstSetting model
    - File: `app/Models/Accounting/GstSetting.php`
    - Namespace: `App\Models\Accounting`
    - Table: `gst_settings`
    - Fillable: key, value
    - Static helper: `get(string $key, $default = null): ?string`
    - Static helper: `set(string $key, ?string $value): void`
    - _Requirements: 10.1, 10.2, 10.3_

  - [x] 2.3 Update Gr model to include GST fields in fillable and casts
    - File: `app/Models/Gr.php`
    - Add to $fillable: gst_rate, gst_type, cgst_amount, sgst_amount, igst_amount, gst_total
    - Add to $casts: gst_rate → decimal:2, cgst_amount → decimal:2, sgst_amount → decimal:2, igst_amount → decimal:2, gst_total → decimal:2
    - Add relationship: `gstEntry()` → morphOne(GstEntry::class, 'taxable')
    - _Requirements: 1.1, 1.2_

  - [x] 2.4 Update Expense model to include GST fields in fillable and casts
    - File: `app/Models/Accounting/Expense.php`
    - Add to $fillable: gst_rate, gst_type, cgst_amount, sgst_amount, igst_amount, gst_total, vendor_gst_number
    - Add to $casts: gst_rate → decimal:2, cgst_amount → decimal:2, sgst_amount → decimal:2, igst_amount → decimal:2, gst_total → decimal:2
    - Add relationship: `gstEntry()` → morphOne(GstEntry::class, 'taxable')
    - _Requirements: 1.1, 1.2_

- [x] 3. GstService
  - [x] 3.1 Create GstService with calculation methods
    - File: `app/Services/GstService.php`
    - Constants: VALID_RATES = [5, 12], DEFAULT_HSN_SAC = '996511'
    - Method `determineGstType(string $originState, string $destinationState): string` — returns 'cgst_sgst' if same, 'igst' if different (case-insensitive comparison)
    - Method `calculateGst(float $taxableValue, float $rate, string $gstType): array` — returns [cgst_amount, sgst_amount, igst_amount, total_tax, gst_type]
    - For IGST: total_tax = round(value * rate / 100, 2), igst = total_tax, cgst = 0, sgst = 0
    - For CGST+SGST: total_tax = round(value * rate / 100, 2), sgst = round(total_tax / 2, 2), cgst = total_tax - sgst
    - Method `resolveFinancialYearDates(): array` — same logic as BranchAccountingService
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [x] 3.2 Add summary report method to GstService
    - Method `getSummaryData(string $fromDate, string $toDate, ?string $branch = null): array`
    - Query gst_entries grouped by tax_direction, summing cgst_amount, sgst_amount, igst_amount, total_tax
    - Filter by date range and optional branch
    - Return: ['output' => [cgst, sgst, igst, total], 'input' => [cgst, sgst, igst, total], 'net' => [cgst, sgst, igst, total]]
    - Net = output - input for each component
    - _Requirements: 5.1, 5.2, 5.3, 5.4_

  - [x] 3.3 Add collection report method to GstService
    - Method `getCollectionData(string $fromDate, string $toDate, ?string $branch = null, ?string $gstTypeFilter = null): LengthAwarePaginator`
    - Query gst_entries WHERE tax_direction = 'output' joined with grs (via polymorphic)
    - Select: gr_no, transaction_date, party_name (consignee), party_gst_number, taxable_value, cgst_amount, sgst_amount, igst_amount, total_tax
    - Apply date range, branch, gst_type filters
    - Paginate at 50 per page
    - Also return totals (separate query without pagination): sum of taxable_value, cgst, sgst, igst, total_tax
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

  - [x] 3.4 Add liability report method to GstService
    - Method `getLiabilityData(string $fromDate, string $toDate): Collection`
    - Query gst_entries grouped by YEAR(transaction_date), MONTH(transaction_date), tax_direction
    - Sum cgst_amount, sgst_amount, igst_amount, total_tax per group
    - Build monthly array: each month has output totals, input totals, net (output - input)
    - Include grand total row (sum of all months)
    - Mark negative net values with 'credit_available' flag
    - _Requirements: 7.1, 7.2, 7.3_

  - [x] 3.5 Add input tax report method to GstService
    - Method `getInputTaxData(string $fromDate, string $toDate, ?string $branch = null, ?string $expenseType = null): LengthAwarePaginator`
    - Query gst_entries WHERE tax_direction = 'input' joined with expenses (via polymorphic)
    - Select: expense_no, transaction_date, expense_type (from expense), party_name (paid_to/vendor), taxable_value, cgst, sgst, igst, total_tax
    - Apply filters: date range, branch, expense_type
    - Paginate at 50
    - Return totals separately
    - _Requirements: 8.1, 8.2, 8.3_

  - [x] 3.6 Add output tax report method to GstService
    - Method `getOutputTaxData(string $fromDate, string $toDate, ?string $branch = null, ?string $rateFilter = null): LengthAwarePaginator`
    - Query gst_entries WHERE tax_direction = 'output'
    - Apply filters: date range, branch, gst_rate
    - Order by gst_type, then transaction_date desc
    - Paginate at 50
    - Return summary section: totals by rate (group by gst_rate, sum taxable_value and total_tax)
    - _Requirements: 9.1, 9.2, 9.3_

- [x] 4. Checkpoint - Verify service layer
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. GstReportController
  - [x] 5.1 Create GstReportController with summary action
    - File: `app/Http/Controllers/Accounting/GstReportController.php`
    - Constructor: inject GstService, apply middleware ['auth', 'role:SuperAdmin']
    - Method `summary(Request $request)`: resolve dates (default FY), call $gstService->getSummaryData(), get company GST from GstSetting, render view
    - Pass to view: summaryData, companyGst, fromDate, toDate, selectedBranch, branches list
    - _Requirements: 5.1, 11.1, 11.2_

  - [x] 5.2 Add collection action to GstReportController
    - Method `collection(Request $request)`: resolve dates, get filters (branch, gst_type), call $gstService->getCollectionData(), render view
    - Pass to view: entries (paginated), totals, fromDate, toDate, selectedBranch, selectedGstType
    - _Requirements: 6.1, 6.2, 6.3_

  - [x] 5.3 Add liability action to GstReportController
    - Method `liability(Request $request)`: resolve dates, call $gstService->getLiabilityData(), render view
    - Pass to view: monthlyData, grandTotal, fromDate, toDate
    - _Requirements: 7.1, 7.2_

  - [x] 5.4 Add inputTax action to GstReportController
    - Method `inputTax(Request $request)`: resolve dates, get filters (branch, expense_type), call $gstService->getInputTaxData(), render view
    - Pass to view: entries (paginated), totals, fromDate, toDate, selectedBranch, selectedExpenseType, expenseTypes list
    - _Requirements: 8.1, 8.2_

  - [x] 5.5 Add outputTax action to GstReportController
    - Method `outputTax(Request $request)`: resolve dates, get filters (branch, rate), call $gstService->getOutputTaxData(), render view
    - Pass to view: entries (paginated), summary (by rate), fromDate, toDate, selectedBranch, selectedRate
    - _Requirements: 9.1, 9.2_

  - [x] 5.6 Add settings and updateSettings actions to GstReportController
    - Method `settings(Request $request)`: get all GST settings from GstSetting model, render settings view
    - Method `updateSettings(Request $request)`: validate (company_gst_number nullable|string|max:20, default_gst_rate required|in:5,12, company_state required|string), save via GstSetting::set(), redirect with success message
    - _Requirements: 10.1, 10.2, 10.3_

- [x] 6. Routes
  - [x] 6.1 Register GST report routes in web.php
    - Add route group after existing accounting routes (after the branch accounting group)
    - Middleware: ['role:SuperAdmin'], prefix: 'accounting/gst', name: 'accounting.gst.'
    - GET / → summary (name: summary)
    - GET /collection → collection (name: collection)
    - GET /liability → liability (name: liability)
    - GET /input-tax → inputTax (name: input-tax)
    - GET /output-tax → outputTax (name: output-tax)
    - GET /settings → settings (name: settings)
    - POST /settings → updateSettings (name: settings.update)
    - Wrap inside `Route::middleware(['auth'])->group()` (already within auth group)
    - _Requirements: 11.1, 11.2_

- [x] 7. Blade Views
  - [x] 7.1 Create GST navigation partial
    - File: `resources/views/accounting/gst/_nav.blade.php`
    - Bootstrap 5 nav-pills with links: Summary, Collection, Liability, Input Tax, Output Tax, Settings
    - Active state via `request()->routeIs('accounting.gst.summary')` etc.
    - Include branch filter dropdown (all 7 branches + "All")
    - _Requirements: 5.1, 6.1, 7.1, 8.1, 9.1_

  - [x] 7.2 Create GST filter partial
    - File: `resources/views/accounting/gst/_filter.blade.php`
    - Date range inputs (from_date, to_date) with current values preserved
    - Branch dropdown (optional, for reports that support it)
    - Submit button to apply filters
    - Consistent with Phase 10 filter partial style
    - _Requirements: 5.4, 6.3, 8.3, 9.3_

  - [x] 7.3 Create GST Summary view
    - File: `resources/views/accounting/gst/summary.blade.php`
    - Extends accounting layout, includes _nav and _filter
    - Display Company GST Number in header (or warning if not set)
    - Table with rows: CGST, SGST, IGST, Total | columns: Output Tax, Input Tax, Net Liability
    - Net negative values displayed as "Credit Available" in green
    - _Requirements: 5.1, 5.2, 5.3_

  - [x] 7.4 Create GST Collection Report view
    - File: `resources/views/accounting/gst/collection.blade.php`
    - Extends accounting layout, includes _nav and _filter
    - Additional filter: GST Type (All / CGST+SGST / IGST)
    - Table columns: GR No, Date, Consignee, Consignee GST No, Taxable Value, CGST, SGST, IGST, Total Tax
    - Totals row at bottom
    - Pagination links (50 per page)
    - _Requirements: 6.1, 6.2, 6.4_

  - [x] 7.5 Create GST Liability Report view
    - File: `resources/views/accounting/gst/liability.blade.php`
    - Extends accounting layout, includes _nav and _filter
    - Table grouped by month, columns: Month | Output (CGST, SGST, IGST, Total) | Input (CGST, SGST, IGST, Total) | Net (CGST, SGST, IGST, Total)
    - Grand Total row at bottom (bold)
    - Negative net values shown as "Credit: ₹X" in green
    - _Requirements: 7.1, 7.2, 7.3_

  - [x] 7.6 Create Input Tax Report view
    - File: `resources/views/accounting/gst/input-tax.blade.php`
    - Extends accounting layout, includes _nav and _filter
    - Additional filter: Expense Type dropdown
    - Table columns: Expense No, Date, Type, Vendor/Paid To, Taxable Value, CGST, SGST, IGST, Total Tax
    - Group by expense type with subtotals (when no type filter applied)
    - Totals row, pagination
    - _Requirements: 8.1, 8.2, 8.3_

  - [x] 7.7 Create Output Tax Report view
    - File: `resources/views/accounting/gst/output-tax.blade.php`
    - Extends accounting layout, includes _nav and _filter
    - Additional filter: GST Rate (All / 5% / 12%)
    - Summary section at top: totals by rate
    - Table grouped by GST type (CGST+SGST section, IGST section) with subtotals
    - Table columns: GR No, Date, Party, Taxable Value, CGST, SGST, IGST, Total Tax
    - Pagination
    - _Requirements: 9.1, 9.2, 9.3_

  - [x] 7.8 Create GST Settings view
    - File: `resources/views/accounting/gst/settings.blade.php`
    - Extends accounting layout, includes _nav
    - Form with fields: Company GST Number (text, max 20), Default GST Rate (select: 5% / 12%), Company State (text, default Gujarat)
    - Submit button, success/error flash messages
    - Display current values as form defaults
    - _Requirements: 10.1, 10.2, 10.3_

- [x] 8. Navigation Integration
  - [x] 8.1 Add GST Reports menu to accounting sidebar
    - Locate the accounting sidebar/navigation partial (likely in layouts or partials)
    - Add "GST Reports" section with icon
    - Sub-links: Summary, Collection, Liability, Input Tax, Output Tax, Settings
    - Active state: highlight when any `accounting.gst.*` route is active
    - Position after existing accounting menu items
    - _Requirements: 11.1_

- [x] 9. Checkpoint - Verify views and routing
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Tests
  - [x] 10.1 Write GstService unit tests
    - File: `tests/Feature/GstServiceTest.php`
    - Test calculateGst with 5% intra-state (₹1000 → CGST 25, SGST 25, total 50)
    - Test calculateGst with 12% inter-state (₹1000 → IGST 120, total 120)
    - Test calculateGst with odd amounts (₹999 at 5% → total 49.95, split 24.98 + 24.97)
    - Test determineGstType: 'Gujarat','Gujarat' → 'cgst_sgst'
    - Test determineGstType: 'Gujarat','Maharashtra' → 'igst'
    - Test determineGstType case-insensitive: 'gujarat','GUJARAT' → 'cgst_sgst'
    - Test resolveFinancialYearDates returns correct April-March boundaries
    - _Requirements: 2.1, 2.2, 2.4, 2.5_

  - [x] 10.2 Write GstReportController integration tests
    - File: `tests/Feature/GstReportTest.php`
    - Test SuperAdmin can access all 5 report routes (200 response)
    - Test non-SuperAdmin (Admin, Manager) gets 403 on all routes
    - Test unauthenticated user gets redirected to login
    - Test summary view renders with correct data structure
    - Test collection report pagination (create 60 entries, verify 50 on page 1)
    - Test date range filtering excludes out-of-range entries
    - Test branch filtering returns only matching branch entries
    - Test settings page renders and update saves correctly
    - _Requirements: 11.1, 11.2, 5.4, 6.3_

  - [x] 10.3 Write property test: Tax Amount Mutual Exclusivity (Property 1)
    - **Property 1: Tax Amount Mutual Exclusivity**
    - Generate random entries, verify IGST xor CGST+SGST — if gst_type is 'igst' then cgst=0 and sgst=0; if 'cgst_sgst' then igst=0
    - **Validates: Requirements 1.5, 1.6, 4.2, 4.3**

  - [x] 10.4 Write property test: Tax Calculation Accuracy (Property 2)
    - **Property 2: Tax Calculation Accuracy**
    - Generate random taxable values and rates, verify total_tax = round(value × rate / 100, 2)
    - **Validates: Requirements 2.1, 2.2, 2.4**

  - [x] 10.5 Write property test: CGST/SGST Symmetry (Property 3)
    - **Property 3: CGST/SGST Symmetry**
    - Generate random intra-state entries, verify |cgst - sgst| ≤ 0.01
    - **Validates: Requirements 1.6, 2.1**

  - [x] 10.6 Write property test: Total Tax Invariant (Property 4)
    - **Property 4: Total Tax Invariant**
    - Generate random entries, verify cgst + sgst + igst = total_tax always
    - **Validates: Requirement 1.4**

  - [x] 10.7 Write property test: State-Based Type Determination (Property 5)
    - **Property 5: State-Based Type Determination**
    - Generate random state pairs, verify same state → 'cgst_sgst', different → 'igst'
    - **Validates: Requirements 2.1, 2.2, 2.5**

- [x] 11. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- This is reporting-only — no government filing or GSTR form generation
- All GST fields are nullable to preserve backward compatibility with existing data
- Views use Bootstrap 5 tables consistent with Phase 10 branch accounting views

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "1.4"] },
    { "id": 1, "tasks": ["1.5", "2.1", "2.2"] },
    { "id": 2, "tasks": ["2.3", "2.4", "3.1"] },
    { "id": 3, "tasks": ["3.2", "3.3", "3.4", "3.5", "3.6"] },
    { "id": 4, "tasks": ["5.1", "5.2", "5.3", "5.4", "5.5", "5.6"] },
    { "id": 5, "tasks": ["6.1"] },
    { "id": 6, "tasks": ["7.1", "7.2", "7.3", "7.4", "7.5", "7.6", "7.7", "7.8", "8.1"] },
    { "id": 7, "tasks": ["10.1", "10.2", "10.3", "10.4", "10.5", "10.6", "10.7"] }
  ]
}
```
