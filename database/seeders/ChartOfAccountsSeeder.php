<?php

namespace Database\Seeders;

use App\Models\Accounting\Account;
use Illuminate\Database\Seeder;

/**
 * Seeds the standard Chart of Accounts for an Indian transport company.
 * Based on Miracle Accounting / Tally structure adapted for SXpress.
 */
class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Chart of Accounts...');

        // ═══════════ ASSETS ═══════════
        $assets = $this->createGroup('1000', 'Assets', 'asset');

        $cashBank = $this->createGroup('1100', 'Cash & Bank', 'asset', $assets);
        $this->create('1101', 'Cash In Hand', 'asset', $cashBank);
        $this->create('1102', 'Petty Cash', 'asset', $cashBank);
        $this->create('1110', 'Bank Account - Main', 'asset', $cashBank);
        $this->create('1111', 'Bank Account - Branch', 'asset', $cashBank);

        $receivables = $this->createGroup('1200', 'Accounts Receivable', 'asset', $assets);
        $this->create('1201', 'Customer Receivables', 'asset', $receivables);
        $this->create('1202', 'To-Pay Receivables', 'asset', $receivables);
        $this->create('1203', 'Consignee Receivables', 'asset', $receivables);
        $this->create('1204', 'Branch Receivables', 'asset', $receivables);

        $otherAssets = $this->createGroup('1300', 'Other Assets', 'asset', $assets);
        $this->create('1301', 'Advance to Drivers', 'asset', $otherAssets);
        $this->create('1302', 'Advance to Truck Owners', 'asset', $otherAssets);
        $this->create('1303', 'Security Deposits', 'asset', $otherAssets);
        $this->create('1304', 'TDS Receivable', 'asset', $otherAssets);

        // ═══════════ LIABILITIES ═══════════
        $liabilities = $this->createGroup('2000', 'Liabilities', 'liability');

        $payables = $this->createGroup('2100', 'Accounts Payable', 'liability', $liabilities);
        $this->create('2101', 'Truck Owner Payables', 'liability', $payables);
        $this->create('2102', 'Broker Payables', 'liability', $payables);
        $this->create('2103', 'Branch Payables', 'liability', $payables);
        $this->create('2104', 'Driver Salary Payable', 'liability', $payables);

        $otherLiab = $this->createGroup('2200', 'Other Liabilities', 'liability', $liabilities);
        $this->create('2201', 'GST Payable', 'liability', $otherLiab);
        $this->create('2202', 'TDS Payable', 'liability', $otherLiab);
        $this->create('2203', 'Loans', 'liability', $otherLiab);

        // ═══════════ INCOME ═══════════
        $income = $this->createGroup('3000', 'Income', 'income');

        $this->create('3001', 'Freight Income', 'income', $income);
        $this->create('3002', 'Booking Charges', 'income', $income);
        $this->create('3003', 'Loading Charges', 'income', $income);
        $this->create('3004', 'Unloading Charges', 'income', $income);
        $this->create('3005', 'Surcharge Income', 'income', $income);
        $this->create('3006', 'Commission Income', 'income', $income);
        $this->create('3007', 'Detention Income', 'income', $income);
        $this->create('3008', 'Misc Income', 'income', $income);

        // ═══════════ EXPENSES ═══════════
        $expenses = $this->createGroup('4000', 'Expenses', 'expense');

        $vehicle = $this->createGroup('4100', 'Vehicle Expenses', 'expense', $expenses);
        $this->create('4101', 'Diesel Expense', 'expense', $vehicle);
        $this->create('4102', 'Vehicle Maintenance', 'expense', $vehicle);
        $this->create('4103', 'Tyre Expense', 'expense', $vehicle);
        $this->create('4104', 'Toll Expense', 'expense', $vehicle);
        $this->create('4105', 'Permit & Tax', 'expense', $vehicle);
        $this->create('4106', 'Insurance Expense', 'expense', $vehicle);

        $lorryHire = $this->createGroup('4200', 'Lorry Hire', 'expense', $expenses);
        $this->create('4201', 'Lorry Hire - Freight Paid', 'expense', $lorryHire);
        $this->create('4202', 'Hamali Expense', 'expense', $lorryHire);
        $this->create('4203', 'Detention Paid', 'expense', $lorryHire);

        $staff = $this->createGroup('4300', 'Staff Expenses', 'expense', $expenses);
        $this->create('4301', 'Driver Salary', 'expense', $staff);
        $this->create('4302', 'Staff Salary', 'expense', $staff);
        $this->create('4303', 'Bonus & Incentives', 'expense', $staff);

        $office = $this->createGroup('4400', 'Office & Admin', 'expense', $expenses);
        $this->create('4401', 'Office Rent', 'expense', $office);
        $this->create('4402', 'Electricity', 'expense', $office);
        $this->create('4403', 'Telephone & Internet', 'expense', $office);
        $this->create('4404', 'Stationery', 'expense', $office);
        $this->create('4405', 'Printing', 'expense', $office);
        $this->create('4406', 'Branch Expenses', 'expense', $office);
        $this->create('4407', 'Misc Expense', 'expense', $office);

        // ═══════════ EQUITY ═══════════
        $equity = $this->createGroup('5000', 'Equity', 'equity');
        $this->create('5001', 'Capital Account', 'equity', $equity);
        $this->create('5002', 'Retained Earnings', 'equity', $equity);
        $this->create('5003', 'Drawings', 'equity', $equity);

        $this->command->info('✓ Chart of Accounts seeded — ' . Account::count() . ' accounts created.');
    }

    private function createGroup(string $code, string $name, string $type, ?Account $parent = null): Account
    {
        return Account::create([
            'code'       => $code,
            'name'       => $name,
            'type'       => $type,
            'parent_id'  => $parent?->id,
            'is_group'   => true,
            'is_system'  => true,
            'is_active'  => true,
        ]);
    }

    private function create(string $code, string $name, string $type, ?Account $parent = null): Account
    {
        return Account::create([
            'code'       => $code,
            'name'       => $name,
            'type'       => $type,
            'parent_id'  => $parent?->id,
            'is_group'   => false,
            'is_system'  => true,
            'is_active'  => true,
        ]);
    }
}
