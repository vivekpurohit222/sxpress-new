# Requirements Document

## Introduction

Phase 11 of the SXpress Accounting Module adds GST (Goods and Services Tax) data storage and reporting capabilities. The system will track GST numbers for the company and parties (customers, consignors, consignees), store GST calculations on freight transactions (GRs), and generate five GST reports: Summary, Collection, Liability, Input Tax, and Output Tax. This phase is reporting-only — no government filing integration is required.

The primary taxable service is goods transport. Standard GST rates for goods transport agencies (GTA) are 5% (without Input Tax Credit) or 12% (with ITC). Intra-state transactions attract CGST + SGST (split 50/50 of the rate), while inter-state transactions attract IGST (full rate).

## Glossary

- **GstEntry**: A record capturing the GST calculation for a single taxable transaction (GR or expense), storing tax type, rate, and computed amounts
- **GstReportController**: The controller responsible for rendering all five GST report views
- **GstService**: The service class encapsulating GST calculation logic, tax type determination, and report data aggregation
- **GST_Rate**: The tax percentage applied to a transaction (5% or 12% for transport services)
- **CGST**: Central Goods and Services Tax — levied on intra-state supply (half of GST_Rate)
- **SGST**: State Goods and Services Tax — levied on intra-state supply (half of GST_Rate)
- **IGST**: Integrated Goods and Services Tax — levied on inter-state supply (full GST_Rate)
- **Output_Tax**: GST collected on services rendered (freight charges on GRs)
- **Input_Tax**: GST paid on purchases and expenses (diesel, repairs, office supplies with GST)
- **Taxable_Value**: The base amount on which GST is calculated (freight_amount on GRs, amount on expenses)
- **SuperAdmin**: The only role with access to accounting and GST features
- **GR**: Goods Receipt — the primary freight booking document containing freight charges
- **Company_GST_Number**: The SXpress company's own GST registration number stored in application settings
- **Financial_Year**: Indian financial year running April 1 to March 31

## Requirements

### Requirement 1: GST Entry Data Storage

**User Story:** As a SuperAdmin, I want GST calculations stored for each taxable transaction, so that I can track tax obligations and generate reports.

#### Acceptance Criteria

1. THE GstEntry SHALL store the following fields: transaction reference (polymorphic to GR or Expense), transaction date, party name, party GST number, GST rate (decimal), taxable value (decimal), CGST amount (decimal), SGST amount (decimal), IGST amount (decimal), total tax amount (decimal), tax direction (output or input), branch, and HSN/SAC code
2. WHEN a GstEntry is created with tax direction "output", THE GstEntry SHALL reference a GR as the transaction source
3. WHEN a GstEntry is created with tax direction "input", THE GstEntry SHALL reference an Expense as the transaction source
4. THE GstEntry SHALL enforce that total tax amount equals CGST amount plus SGST amount plus IGST amount
5. WHEN a GstEntry has a non-zero IGST amount, THE GstEntry SHALL have zero values for both CGST amount and SGST amount
6. WHEN a GstEntry has non-zero CGST and SGST amounts, THE GstEntry SHALL have a zero value for IGST amount and CGST amount SHALL equal SGST amount

### Requirement 2: GST Rate and Type Determination

**User Story:** As a SuperAdmin, I want the system to determine the correct GST type and split based on origin and destination states, so that tax calculations are accurate.

#### Acceptance Criteria

1. WHEN the origin state and destination state of a GR are the same, THE GstService SHALL classify the transaction as intra-state and apply CGST plus SGST at half the GST_Rate each
2. WHEN the origin state and destination state of a GR are different, THE GstService SHALL classify the transaction as inter-state and apply IGST at the full GST_Rate
3. THE GstService SHALL support GST rates of 5 percent and 12 percent for transport services
4. WHEN calculating tax amounts, THE GstService SHALL compute taxable value multiplied by the applicable rate divided by 100, rounded to two decimal places
5. THE GstService SHALL use the branch state from the originating branch to determine the origin state of a GR

### Requirement 3: Company GST Configuration

**User Story:** As a SuperAdmin, I want to store and manage the company GST registration number and default settings, so that reports display correct company tax identity.

#### Acceptance Criteria

1. THE System SHALL store a Company_GST_Number in application settings accessible to SuperAdmin
2. THE System SHALL store a default GST rate (5 or 12) in application settings
3. THE System SHALL store the company state (for intra/inter-state determination) in application settings
4. WHEN the Company_GST_Number is not configured, THE GstReportController SHALL display a warning message prompting configuration

### Requirement 4: GST Fields on GR Table

**User Story:** As a SuperAdmin, I want GST calculation fields stored directly on each GR record, so that tax amounts are readily available without joining to the GST entries table.

#### Acceptance Criteria

1. THE System SHALL add the following nullable fields to the grs table: gst_rate (decimal 5,2), gst_type (enum: cgst_sgst, igst), cgst_amount (decimal 12,2), sgst_amount (decimal 12,2), igst_amount (decimal 12,2), gst_total (decimal 12,2)
2. WHEN a GR has gst_type of "cgst_sgst", THE GR SHALL have cgst_amount and sgst_amount each equal to half of gst_total
3. WHEN a GR has gst_type of "igst", THE GR SHALL have igst_amount equal to gst_total and cgst_amount and sgst_amount equal to zero
4. THE System SHALL preserve existing GR records with null GST fields (backward compatible — no data loss on migration)

### Requirement 5: GST Summary Report

**User Story:** As a SuperAdmin, I want a consolidated GST summary report showing total output tax, total input tax, and net liability for a date range, so that I can understand the overall GST position at a glance.

#### Acceptance Criteria

1. WHEN the SuperAdmin requests the GST Summary Report, THE GstReportController SHALL display total output tax (CGST, SGST, IGST separately), total input tax (CGST, SGST, IGST separately), and net liability (output minus input for each component) for the selected date range
2. THE GstReportController SHALL default the date range to the current Financial_Year (April 1 to March 31)
3. WHEN no date range is provided, THE GstReportController SHALL use the current Financial_Year dates
4. THE GstReportController SHALL allow filtering by branch
5. THE GstReportController SHALL display the Company_GST_Number in the report header

### Requirement 6: GST Collection Report (Output Tax Collected)

**User Story:** As a SuperAdmin, I want a report showing all GST collected on freight services, so that I can review output tax obligations.

#### Acceptance Criteria

1. WHEN the SuperAdmin requests the GST Collection Report, THE GstReportController SHALL display a list of GRs with GST charged, showing: GR number, date, consignee name, consignee GST number, taxable value, CGST amount, SGST amount, IGST amount, and total tax
2. THE GstReportController SHALL display totals for taxable value, CGST, SGST, IGST, and total tax at the bottom of the report
3. THE GstReportController SHALL allow filtering by date range and branch
4. THE GstReportController SHALL allow filtering by GST type (CGST+SGST only, IGST only, or all)
5. THE GstReportController SHALL support pagination at 50 records per page

### Requirement 7: GST Liability Report

**User Story:** As a SuperAdmin, I want a report showing the net GST payable to the government (output tax minus eligible input tax credit), so that I can understand monthly tax obligations.

#### Acceptance Criteria

1. WHEN the SuperAdmin requests the GST Liability Report, THE GstReportController SHALL display monthly totals of output tax, input tax credit, and net liability (output minus input) for CGST, SGST, and IGST separately
2. THE GstReportController SHALL group liability data by month within the selected date range
3. THE GstReportController SHALL display a grand total row showing cumulative figures for the entire date range
4. THE GstReportController SHALL allow filtering by Financial_Year (defaulting to current year)
5. IF net liability for a component is negative (input exceeds output), THEN THE GstReportController SHALL display the value as "Credit Available" with the absolute amount

### Requirement 8: Input Tax Report

**User Story:** As a SuperAdmin, I want a report showing all GST paid on purchases and expenses, so that I can track input tax credit eligibility.

#### Acceptance Criteria

1. WHEN the SuperAdmin requests the Input Tax Report, THE GstReportController SHALL display a list of expenses with GST paid, showing: expense number, date, expense type, vendor/paid to, taxable value, CGST amount, SGST amount, IGST amount, and total tax
2. THE GstReportController SHALL display totals for taxable value, CGST, SGST, IGST, and total tax at the bottom of the report
3. THE GstReportController SHALL allow filtering by date range, branch, and expense type
4. THE GstReportController SHALL support pagination at 50 records per page
5. THE GstReportController SHALL group input tax entries by expense type with subtotals when no specific type filter is applied

### Requirement 9: Output Tax Report

**User Story:** As a SuperAdmin, I want a detailed report of all GST charged on services rendered (freight), grouped by tax type and rate, so that I can reconcile output tax with returns.

#### Acceptance Criteria

1. WHEN the SuperAdmin requests the Output Tax Report, THE GstReportController SHALL display GR-wise output tax details grouped by GST type (CGST+SGST vs IGST)
2. THE GstReportController SHALL show subtotals for each GST type group
3. THE GstReportController SHALL allow filtering by date range, branch, and GST rate (5% or 12%)
4. THE GstReportController SHALL display a summary section at the top showing total taxable value and total tax broken down by rate
5. THE GstReportController SHALL support pagination at 50 records per page

### Requirement 10: GST Data on Expenses

**User Story:** As a SuperAdmin, I want to record GST paid on expenses, so that input tax credit can be tracked in reports.

#### Acceptance Criteria

1. THE System SHALL add the following nullable fields to the expenses table: gst_rate (decimal 5,2), gst_type (enum: cgst_sgst, igst), cgst_amount (decimal 12,2), sgst_amount (decimal 12,2), igst_amount (decimal 12,2), gst_total (decimal 12,2), vendor_gst_number (varchar 20)
2. WHEN an expense has a non-null gst_rate, THE System SHALL compute and store the tax amounts based on the gst_type and rate
3. THE System SHALL preserve existing expense records with null GST fields (backward compatible)

### Requirement 11: Access Control

**User Story:** As a SuperAdmin, I want GST reports accessible only to the SuperAdmin role, so that sensitive tax data remains restricted.

#### Acceptance Criteria

1. THE GstReportController SHALL enforce the SuperAdmin role check via middleware on all route actions
2. WHEN a non-SuperAdmin user attempts to access any GST report route, THE System SHALL return a 403 Forbidden response
3. THE System SHALL register GST report routes under the /accounting/gst prefix with the name prefix accounting.gst

### Requirement 12: Navigation Integration

**User Story:** As a SuperAdmin, I want GST reports accessible from the accounting navigation menu, so that I can find them alongside other accounting features.

#### Acceptance Criteria

1. THE System SHALL add a "GST Reports" menu group to the accounting sidebar navigation
2. THE System SHALL display sub-links for Summary, Collection, Liability, Input Tax, and Output Tax reports within the GST Reports menu group
3. WHEN the current route matches any accounting.gst route, THE System SHALL highlight the GST Reports menu item as active
