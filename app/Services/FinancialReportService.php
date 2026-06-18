<?php

namespace App\Services;

use App\Models\Accounting\Account;
use App\Models\Accounting\Expense;
use App\Models\Accounting\LedgerEntry;
use App\Models\Accounting\Voucher;
use App\Models\Freight;
use App\Models\Vehicle;
use App\Models\truckdriver;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    private BranchAccountingService $branchService;

    public function __construct(BranchAccountingService $branchService)
    {
        $this->branchService = $branchService;
    }

    /**
     * Day Book: All approved vouchers and their entries for a specific date.
     *
     * @param string      $date   Y-m-d
     * @param string|null $branch Filter by branch (null = all)
     * @return array{vouchers: Collection, totals: array{debit: float, credit: float}}
     */
    public function getDayBook(string $date, ?string $branch = null): array
    {
        $query = Voucher::with(['entries.account'])
            ->whereDate('voucher_date', $date)
            ->where('status', 'approved');

        if ($branch) {
            $query->whereHas('entries', function ($q) use ($branch) {
                $q->where('branch', $branch);
            });
        }

        $vouchers = $query->orderBy('voucher_no')->get();

        // If branch filter is applied, filter entries within each voucher
        if ($branch) {
            $vouchers->each(function ($voucher) use ($branch) {
                $voucher->setRelation(
                    'entries',
                    $voucher->entries->where('branch', $branch)->values()
                );
            });
        }

        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($vouchers as $voucher) {
            foreach ($voucher->entries as $entry) {
                $totalDebit += (float) $entry->debit;
                $totalCredit += (float) $entry->credit;
            }
        }

        return [
            'vouchers' => $vouchers,
            'totals' => [
                'debit' => round($totalDebit, 2),
                'credit' => round($totalCredit, 2),
            ],
        ];
    }

    /**
     * Trial Balance: All accounts with debit/credit balances for a period.
     *
     * @param string      $fromDate Y-m-d
     * @param string      $toDate   Y-m-d
     * @param string|null $branch   Filter by branch (null = all)
     * @return array{accounts: Collection, totals: array{debit: float, credit: float}}
     */
    public function getTrialBalance(string $fromDate, string $toDate, ?string $branch = null): array
    {
        $query = Account::query()
            ->select([
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                'accounts.opening_balance',
                DB::raw('COALESCE(SUM(ledger_entries.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(ledger_entries.credit), 0) as total_credit'),
            ])
            ->leftJoin('ledger_entries', function ($join) use ($fromDate, $toDate, $branch) {
                $join->on('ledger_entries.account_id', '=', 'accounts.id')
                    ->whereDate('ledger_entries.date', '>=', $fromDate)
                    ->whereDate('ledger_entries.date', '<=', $toDate);

                if ($branch) {
                    $join->where('ledger_entries.branch', $branch);
                }
            })
            ->where('accounts.is_group', 0)
            ->where('accounts.is_active', 1)
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type', 'accounts.opening_balance')
            ->orderBy('accounts.type')
            ->orderBy('accounts.code');

        $accounts = $query->get();

        $totalDebit = 0.0;
        $totalCredit = 0.0;

        $result = $accounts->map(function ($account) {
            $opening = (float) ($account->opening_balance ?? 0);
            $debit = (float) $account->total_debit;
            $credit = (float) $account->total_credit;

            // Calculate balance based on account type
            if (in_array($account->type, ['asset', 'expense'])) {
                // Debit-normal: balance = opening + debit - credit
                $balance = $opening + $debit - $credit;
            } else {
                // Credit-normal (liability, income, equity): balance = opening + credit - debit
                $balance = $opening + $credit - $debit;
            }

            $balance = round($balance, 2);

            if ($balance == 0) {
                return null;
            }

            // Place in correct column
            $debitColumn = 0.0;
            $creditColumn = 0.0;

            if (in_array($account->type, ['asset', 'expense'])) {
                // Debit-normal account
                if ($balance > 0) {
                    $debitColumn = $balance;
                } else {
                    $creditColumn = abs($balance);
                }
            } else {
                // Credit-normal account
                if ($balance > 0) {
                    $creditColumn = $balance;
                } else {
                    $debitColumn = abs($balance);
                }
            }

            return (object) [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'debit' => round($debitColumn, 2),
                'credit' => round($creditColumn, 2),
            ];
        })->filter()->values();

        // Calculate totals
        foreach ($result as $item) {
            $totalDebit += $item->debit;
            $totalCredit += $item->credit;
        }

        // Group by account type
        $grouped = $result->groupBy('type');

        return [
            'accounts' => $grouped,
            'totals' => [
                'debit' => round($totalDebit, 2),
                'credit' => round($totalCredit, 2),
            ],
        ];
    }

    /**
     * Profit & Loss Statement: Income vs Expense for a period.
     * Uses only period movements (no opening_balance).
     *
     * @param string      $fromDate Y-m-d
     * @param string      $toDate   Y-m-d
     * @param string|null $branch   Filter by branch (null = all)
     * @return array{income: Collection, expenses: Collection, total_income: float, total_expenses: float, net_profit: float}
     */
    public function getProfitAndLoss(string $fromDate, string $toDate, ?string $branch = null): array
    {
        // Income accounts: credit - debit for the period
        $income = $this->getPeriodBalances('income', $fromDate, $toDate, $branch);

        // Expense accounts: debit - credit for the period
        $expenses = $this->getPeriodBalances('expense', $fromDate, $toDate, $branch);

        $totalIncome = round($income->sum('balance'), 2);
        $totalExpenses = round($expenses->sum('balance'), 2);
        $netProfit = round($totalIncome - $totalExpenses, 2);

        // Group by parent_id for hierarchy
        $incomeGrouped = $income->groupBy('parent_id');
        $expensesGrouped = $expenses->groupBy('parent_id');

        return [
            'income' => $incomeGrouped,
            'expenses' => $expensesGrouped,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Balance Sheet: Assets = Liabilities + Equity + Net Profit as of a date.
     *
     * @param string      $asOfDate Y-m-d
     * @param string|null $branch   Filter by branch (null = all)
     * @return array{assets: Collection, liabilities: Collection, equity: Collection, net_profit: float, total_assets: float, total_liabilities_equity: float}
     */
    public function getBalanceSheet(string $asOfDate, ?string $branch = null): array
    {
        $assets = $this->getCumulativeBalances('asset', $asOfDate, $branch);
        $liabilities = $this->getCumulativeBalances('liability', $asOfDate, $branch);
        $equity = $this->getCumulativeBalances('equity', $asOfDate, $branch);

        // Calculate current FY net profit
        $fyStart = $this->getFYStart($asOfDate);
        $netProfit = $this->calculateNetProfit($fyStart, $asOfDate, $branch);

        $totalAssets = round($assets->sum('balance'), 2);
        $totalLiabilities = round($liabilities->sum('balance'), 2);
        $totalEquity = round($equity->sum('balance'), 2);
        $totalLiabilitiesEquity = round($totalLiabilities + $totalEquity + $netProfit, 2);

        // Group by parent_id for hierarchy
        $assetsGrouped = $assets->groupBy('parent_id');
        $liabilitiesGrouped = $liabilities->groupBy('parent_id');
        $equityGrouped = $equity->groupBy('parent_id');

        return [
            'assets' => $assetsGrouped,
            'liabilities' => $liabilitiesGrouped,
            'equity' => $equityGrouped,
            'net_profit' => round($netProfit, 2),
            'total_assets' => $totalAssets,
            'total_liabilities_equity' => $totalLiabilitiesEquity,
        ];
    }

    /**
     * Vehicle Profitability: Revenue vs expenses per vehicle.
     *
     * @param string      $fromDate Y-m-d
     * @param string      $toDate   Y-m-d
     * @param string|null $branch   Filter by branch (null = all)
     * @return array{vehicles: Collection, totals: array{revenue: float, expenses: float, net_profit: float}}
     */
    public function getVehicleProfitability(string $fromDate, string $toDate, ?string $branch = null): array
    {
        // Revenue from frieghts table grouped by truck_id
        $revenueQuery = DB::table('frieghts')
            ->join('vehicles', 'frieghts.truck_id', '=', 'vehicles.id')
            ->whereNotNull('frieghts.truck_id')
            ->where(function ($q) use ($fromDate, $toDate) {
                $q->whereBetween('frieghts.fm_date', [$fromDate, $toDate]);
            })
            ->whereNull('frieghts.deleted_at');

        if ($branch) {
            $revenueQuery->where('frieghts.office', $branch);
        }

        $revenue = $revenueQuery
            ->groupBy('frieghts.truck_id', 'vehicles.vehicle_number')
            ->select(
                'frieghts.truck_id as vehicle_id',
                'vehicles.vehicle_number',
                DB::raw('COALESCE(SUM(frieghts.truck_freight), 0) as total_revenue')
            )
            ->get()
            ->keyBy('vehicle_id');

        // Expenses from expenses table grouped by vehicle_id and expense_type
        $expenseQuery = Expense::query()
            ->whereNotNull('vehicle_id')
            ->whereIn('status', ['approved', 'paid'])
            ->whereBetween('expense_date', [$fromDate, $toDate]);

        if ($branch) {
            $expenseQuery->where('branch', $branch);
        }

        $expenses = $expenseQuery
            ->groupBy('vehicle_id', 'expense_type')
            ->select(
                'vehicle_id',
                'expense_type',
                DB::raw('COALESCE(SUM(amount), 0) as type_total')
            )
            ->get()
            ->groupBy('vehicle_id');

        // Combine all vehicle IDs
        $allVehicleIds = $revenue->keys()
            ->merge($expenses->keys())
            ->unique();

        // Build per vehicle data
        $vehicles = collect();

        foreach ($allVehicleIds as $vehicleId) {
            $revenueRow = $revenue->get($vehicleId);
            $vehicleExpenses = $expenses->get($vehicleId, collect());

            $totalRevenue = $revenueRow ? (float) $revenueRow->total_revenue : 0;

            // Get vehicle number
            $vehicleNumber = $revenueRow
                ? $revenueRow->vehicle_number
                : (Vehicle::find($vehicleId)?->vehicle_number ?? 'Unknown');

            // Build expense breakdown
            $diesel = 0.0;
            $repair = 0.0;
            $tyre = 0.0;
            $driverSalary = 0.0;
            $misc = 0.0;

            foreach ($vehicleExpenses as $exp) {
                $amount = (float) $exp->type_total;
                match ($exp->expense_type) {
                    'diesel' => $diesel += $amount,
                    'repair' => $repair += $amount,
                    'tyre' => $tyre += $amount,
                    'driver_salary' => $driverSalary += $amount,
                    default => $misc += $amount,
                };
            }

            $totalExpenses = $diesel + $repair + $tyre + $driverSalary + $misc;

            // Exclude vehicles with zero revenue AND zero expenses
            if ($totalRevenue == 0 && $totalExpenses == 0) {
                continue;
            }

            $netProfit = $totalRevenue - $totalExpenses;

            $vehicles->push((object) [
                'vehicle_id' => $vehicleId,
                'vehicle_number' => $vehicleNumber,
                'total_revenue' => round($totalRevenue, 2),
                'diesel' => round($diesel, 2),
                'repair' => round($repair, 2),
                'tyre' => round($tyre, 2),
                'driver_salary' => round($driverSalary, 2),
                'misc' => round($misc, 2),
                'total_expenses' => round($totalExpenses, 2),
                'net_profit' => round($netProfit, 2),
            ]);
        }

        // Sort by net_profit descending
        $vehicles = $vehicles->sortByDesc('net_profit')->values();

        // Calculate totals
        $totals = [
            'revenue' => round($vehicles->sum('total_revenue'), 2),
            'expenses' => round($vehicles->sum('total_expenses'), 2),
            'net_profit' => round($vehicles->sum('net_profit'), 2),
        ];

        return [
            'vehicles' => $vehicles,
            'totals' => $totals,
        ];
    }

    /**
     * Driver Expense Report: Expenses grouped by driver.
     *
     * @param string      $fromDate Y-m-d
     * @param string      $toDate   Y-m-d
     * @param string|null $branch   Filter by branch (null = all)
     * @return array{drivers: Collection, total_expenses: float}
     */
    public function getDriverExpenses(string $fromDate, string $toDate, ?string $branch = null): array
    {
        $query = Expense::query()
            ->join('truckdrivers', 'expenses.driver_id', '=', 'truckdrivers.id')
            ->whereNotNull('expenses.driver_id')
            ->whereIn('expenses.status', ['approved', 'paid'])
            ->whereBetween('expenses.expense_date', [$fromDate, $toDate])
            ->whereNull('expenses.deleted_at');

        if ($branch) {
            $query->where('expenses.branch', $branch);
        }

        $rows = $query
            ->groupBy('truckdrivers.id', 'truckdrivers.driver_name', 'truckdrivers.truck_no', 'expenses.expense_type')
            ->select(
                'truckdrivers.id as driver_id',
                'truckdrivers.driver_name',
                'truckdrivers.truck_no',
                'expenses.expense_type',
                DB::raw('COUNT(*) as expense_count'),
                DB::raw('COALESCE(SUM(expenses.amount), 0) as type_total')
            )
            ->get()
            ->groupBy('driver_id');

        $drivers = collect();

        foreach ($rows as $driverId => $driverExpenses) {
            $firstRow = $driverExpenses->first();

            // Build expense breakdown
            $diesel = 0.0;
            $repair = 0.0;
            $tyre = 0.0;
            $driverSalary = 0.0;
            $misc = 0.0;
            $totalCount = 0;

            foreach ($driverExpenses as $exp) {
                $amount = (float) $exp->type_total;
                $totalCount += (int) $exp->expense_count;

                match ($exp->expense_type) {
                    'diesel' => $diesel += $amount,
                    'repair' => $repair += $amount,
                    'tyre' => $tyre += $amount,
                    'driver_salary' => $driverSalary += $amount,
                    default => $misc += $amount,
                };
            }

            $totalExpenses = $diesel + $repair + $tyre + $driverSalary + $misc;

            // Exclude drivers with zero expenses
            if ($totalExpenses == 0) {
                continue;
            }

            $drivers->push((object) [
                'driver_id' => $driverId,
                'driver_name' => $firstRow->driver_name,
                'truck_no' => $firstRow->truck_no,
                'diesel' => round($diesel, 2),
                'repair' => round($repair, 2),
                'tyre' => round($tyre, 2),
                'driver_salary' => round($driverSalary, 2),
                'misc' => round($misc, 2),
                'total_expenses' => round($totalExpenses, 2),
                'expense_count' => $totalCount,
            ]);
        }

        // Sort by total_expenses descending
        $drivers = $drivers->sortByDesc('total_expenses')->values();

        $totalExpenses = round($drivers->sum('total_expenses'), 2);

        return [
            'drivers' => $drivers,
            'total_expenses' => $totalExpenses,
        ];
    }

    // ─── Private Helpers ─────────────────────────────────────────

    /**
     * Calculate net profit for a period (used by Balance Sheet).
     * Net profit = total income - total expenses for the period.
     */
    private function calculateNetProfit(string $fromDate, string $toDate, ?string $branch = null): float
    {
        // Income: sum(credit) - sum(debit) for income accounts
        $incomeQuery = LedgerEntry::query()
            ->join('accounts', 'ledger_entries.account_id', '=', 'accounts.id')
            ->where('accounts.type', 'income')
            ->where('accounts.is_group', 0)
            ->whereDate('ledger_entries.date', '>=', $fromDate)
            ->whereDate('ledger_entries.date', '<=', $toDate);

        if ($branch) {
            $incomeQuery->where('ledger_entries.branch', $branch);
        }

        $incomeResult = $incomeQuery->selectRaw(
            'COALESCE(SUM(ledger_entries.credit), 0) - COALESCE(SUM(ledger_entries.debit), 0) as net_income'
        )->first();

        $totalIncome = (float) ($incomeResult->net_income ?? 0);

        // Expenses: sum(debit) - sum(credit) for expense accounts
        $expenseQuery = LedgerEntry::query()
            ->join('accounts', 'ledger_entries.account_id', '=', 'accounts.id')
            ->where('accounts.type', 'expense')
            ->where('accounts.is_group', 0)
            ->whereDate('ledger_entries.date', '>=', $fromDate)
            ->whereDate('ledger_entries.date', '<=', $toDate);

        if ($branch) {
            $expenseQuery->where('ledger_entries.branch', $branch);
        }

        $expenseResult = $expenseQuery->selectRaw(
            'COALESCE(SUM(ledger_entries.debit), 0) - COALESCE(SUM(ledger_entries.credit), 0) as net_expense'
        )->first();

        $totalExpenses = (float) ($expenseResult->net_expense ?? 0);

        return round($totalIncome - $totalExpenses, 2);
    }

    /**
     * Get period balances for P&L (no opening_balance).
     * Income: credit - debit; Expense: debit - credit.
     */
    private function getPeriodBalances(string $type, string $fromDate, string $toDate, ?string $branch = null): Collection
    {
        $query = Account::query()
            ->select([
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                'accounts.parent_id',
                DB::raw('COALESCE(SUM(ledger_entries.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(ledger_entries.credit), 0) as total_credit'),
            ])
            ->leftJoin('ledger_entries', function ($join) use ($fromDate, $toDate, $branch) {
                $join->on('ledger_entries.account_id', '=', 'accounts.id')
                    ->whereDate('ledger_entries.date', '>=', $fromDate)
                    ->whereDate('ledger_entries.date', '<=', $toDate);

                if ($branch) {
                    $join->where('ledger_entries.branch', $branch);
                }
            })
            ->where('accounts.type', $type)
            ->where('accounts.is_group', 0)
            ->where('accounts.is_active', 1)
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type', 'accounts.parent_id')
            ->orderBy('accounts.code');

        return $query->get()->map(function ($account) use ($type) {
            $debit = (float) $account->total_debit;
            $credit = (float) $account->total_credit;

            // Income: credit - debit; Expense: debit - credit
            $balance = $type === 'income'
                ? round($credit - $debit, 2)
                : round($debit - $credit, 2);

            if ($balance == 0) {
                return null;
            }

            return (object) [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'parent_id' => $account->parent_id,
                'balance' => $balance,
            ];
        })->filter()->values();
    }

    /**
     * Get cumulative balances for Balance Sheet (opening + all movements up to date).
     * Asset: opening + debit - credit; Liability/Equity: opening + credit - debit.
     */
    private function getCumulativeBalances(string $type, string $asOfDate, ?string $branch = null): Collection
    {
        $query = Account::query()
            ->select([
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                'accounts.parent_id',
                'accounts.opening_balance',
                DB::raw('COALESCE(SUM(ledger_entries.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(ledger_entries.credit), 0) as total_credit'),
            ])
            ->leftJoin('ledger_entries', function ($join) use ($asOfDate, $branch) {
                $join->on('ledger_entries.account_id', '=', 'accounts.id')
                    ->whereDate('ledger_entries.date', '<=', $asOfDate);

                if ($branch) {
                    $join->where('ledger_entries.branch', $branch);
                }
            })
            ->where('accounts.type', $type)
            ->where('accounts.is_group', 0)
            ->where('accounts.is_active', 1)
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type', 'accounts.parent_id', 'accounts.opening_balance')
            ->orderBy('accounts.code');

        return $query->get()->map(function ($account) use ($type) {
            $opening = (float) ($account->opening_balance ?? 0);
            $debit = (float) $account->total_debit;
            $credit = (float) $account->total_credit;

            // Asset: opening + debit - credit
            // Liability/Equity: opening + credit - debit
            $balance = $type === 'asset'
                ? round($opening + $debit - $credit, 2)
                : round($opening + $credit - $debit, 2);

            if ($balance == 0) {
                return null;
            }

            return (object) [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'parent_id' => $account->parent_id,
                'balance' => $balance,
            ];
        })->filter()->values();
    }

    /**
     * Determine the start of the Indian Financial Year (April 1) for a given date.
     */
    private function getFYStart(string $date): string
    {
        $carbon = Carbon::parse($date);

        if ($carbon->month >= 4) {
            return Carbon::create($carbon->year, 4, 1)->format('Y-m-d');
        }

        return Carbon::create($carbon->year - 1, 4, 1)->format('Y-m-d');
    }
}
