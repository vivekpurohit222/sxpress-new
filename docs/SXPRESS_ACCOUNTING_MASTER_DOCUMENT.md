# SXPRESS ACCOUNTING MODULE MASTER SPECIFICATION

Version: 1.0

Purpose:
Build a professional transport accounting system for SXpress Logistics without affecting existing operational modules.

This accounting system must integrate with:

* GR
* Gatepass
* Challan
* POD
* Freight Memo

while remaining independent from operational workflows.

---

# IMPORTANT RULES

Before making any code changes:

Read:

* PROJECT_STATUS.md
* SXPRESS_MASTER_DOCUMENT.md
* SXPRESS_LOGIC_SKILL.md

Understand the complete transport workflow.

Do NOT replace existing modules.

Do NOT redesign working modules.

Do NOT create breaking database changes.

Do NOT modify business logic unless accounting integration requires it.

Accounting must extend SXpress, not interfere with it.

---

# BUSINESS GOAL

SXpress currently manages logistics operations.

The new Accounting Module must manage:

* Income
* Expenses
* Receivables
* Payables
* Cash
* Bank
* Ledgers
* Vouchers
* Reports

similar to accounting systems such as Miracle Accounting, but tailored specifically for transport companies.

---

# ACCOUNTING WORKFLOW

Transport Workflow:

GR
↓
Dispatch
↓
Delivery
↓
POD
↓
Freight Memo
↓
Accounting Entry
↓
Financial Reports

Every financial event should create accounting entries automatically.

Users should not need to enter the same information twice.

---

# PHASE 1

ACCOUNTING ARCHITECTURE

First inspect the existing SXpress codebase.

Review:

* GR Module
* Freight Memo Module
* To-Pay Collections
* Branch Structure
* User Roles
* Existing Reports

Create:

Accounting Architecture Document

Show:

* Database design
* Relationships
* Modules required
* Integration points

Do not implement yet.

---

# PHASE 2

CHART OF ACCOUNTS

Create master account structure.

Assets

* Cash In Hand
* Bank Accounts
* Accounts Receivable
* Branch Cash
* Security Deposits

Liabilities

* Accounts Payable
* Branch Payables
* Loans

Income

* Freight Income
* Booking Charges
* Loading Charges
* Unloading Charges
* Misc Income

Expenses

* Diesel
* Driver Salary
* Vehicle Maintenance
* Toll Expense
* Branch Expense
* Office Expense
* Misc Expense

Equity

* Capital Account
* Retained Earnings

Requirements:

* Parent accounts
* Child accounts
* Account codes
* Branch mapping

---

# PHASE 3

LEDGER SYSTEM

Build complete ledger system.

Features:

General Ledger

Customer Ledger

Consignor Ledger

Consignee Ledger

Vehicle Ledger

Branch Ledger

Expense Ledger

Driver Ledger

Ledger Entries:

Date

Voucher No

Description

Debit

Credit

Balance

Branch

Reference

---

# PHASE 4

VOUCHER SYSTEM

Build:

Receipt Voucher

Payment Voucher

Contra Voucher

Journal Voucher

Each voucher must:

Generate ledger entries automatically.

Support:

Print

Edit

Approval

Audit Trail

---

# PHASE 5

CASH BOOK

Implement:

Opening Balance

Receipts

Payments

Closing Balance

Daily Cash Report

Branch Cash Report

Cash Flow Report

---

# PHASE 6

BANK BOOK

Implement:

Bank Deposits

Bank Withdrawals

Transfers

Cheque Entries

Reconciliation Status

Bank Reports

---

# PHASE 7

OUTSTANDING MANAGEMENT

Track:

Customer Outstanding

Consignor Outstanding

Consignee Outstanding

To-Pay Outstanding

Fields:

Invoice Amount

Paid Amount

Pending Amount

Due Date

Status

Generate:

Outstanding Report

Ageing Report

Recovery Report

---

# PHASE 8

FREIGHT MEMO INTEGRATION

When Freight Memo is created:

Automatically create accounting entries.

Example:

Debit:
Accounts Receivable

Credit:
Freight Income

If payment received:

Debit:
Cash/Bank

Credit:
Accounts Receivable

Users should not manually create accounting entries.

System should generate them automatically.

---

# PHASE 9

EXPENSE MANAGEMENT

Create Expense Module.

Expense Types:

Diesel

Driver Salary

Repair

Tyre

Office Expense

Branch Expense

Misc Expense

Requirements:

Voucher Creation

Ledger Posting

Approvals

Reports

---

# PHASE 10

BRANCH ACCOUNTING

Every branch must maintain separate books.

Managers:

Only see their branch accounting.

SuperAdmin:

Can view all branches.

Branch Reports:

Revenue

Expense

Profitability

Cash Position

Outstanding

---

# PHASE 11

GST SUPPORT

Store:

GST Number

GST Type

GST Percentage

Generate:

GST Summary

GST Collection Report

GST Liability Report

Input Tax Report

Output Tax Report

Do not implement government filing.

Only reporting.

---

# PHASE 12

REPORTING SYSTEM

Generate:

Cash Book

Bank Book

Day Book

General Ledger

Customer Ledger

Branch Ledger

Trial Balance

Profit & Loss

Balance Sheet

Outstanding Report

Revenue Report

Expense Report

Vehicle Profitability Report

Branch Profitability Report

Driver Expense Report

---

# PHASE 13

AUDIT LOGGING

Track:

Created By

Updated By

Deleted By

Approved By

Approved At

Voucher History

Ledger History

---

# PHASE 14

ROLE PERMISSIONS

Viewer

* No Access

Staff

* No Access

Manager

* No Access

Admin

* No Access

SuperAdmin

* Full Access

Enforce office isolation.

Managers must never see other branch data.

---

# PHASE 15

DATABASE DESIGN

Use proper:

Foreign Keys

Indexes

Unique Constraints

Transactions

Soft Deletes

Audit Columns

Prevent:

Duplicate vouchers

Duplicate ledger entries

Duplicate posting

---

# PHASE 16

IMPLEMENTATION STRATEGY

DO NOT build everything at once.

Follow this order:

1. Chart Of Accounts
2. Ledger
3. Voucher System
4. Cash Book
5. Bank Book
6. Outstanding Management
7. Freight Memo Integration
8. Expense Module
9. Reports
10. GST Reports

Complete one phase before moving to the next.

---

# COMPLETION RULE

After every phase provide:

Files Created

Files Modified

Database Changes

Routes Added

Permissions Added

Tests Performed

Issues Found

Issues Fixed

Remaining Work

Stop after each phase and wait for approval.

Never implement the next phase automatically.

Verify every phase before continuing.
