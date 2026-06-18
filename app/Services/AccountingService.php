<?php

namespace App\Services;

use App\Models\Accounting\Account;
use App\Models\Accounting\LedgerEntry;
use App\Models\Accounting\Voucher;
use Illuminate\Support\Facades\DB;

/**
 * AccountingService — Central service for double-entry bookkeeping.
 *
 * All financial postings go through this service to ensure:
 * - Every entry is balanced (total debit = total credit)
 * - Voucher is created automatically
 * - Ledger entries reference the voucher
 * - Branch isolation is maintained
 */
class AccountingService
{
    /**
     * Create a voucher with ledger entries (double-entry).
     *
     * @param string $type       receipt|payment|contra|journal
     * @param string $date       Y-m-d
     * @param string $narration  Description of the transaction
     * @param array  $entries    [['account_id'=>X, 'debit'=>100, 'credit'=>0], ...]
     * @param string $branch     Office name
     * @param array  $meta       Optional: reference_type, reference_id, created_by_id
     * @return Voucher
     * @throws \Exception if entries don't balance
     */
    public function createVoucher(
        string $type,
        string $date,
        string $narration,
        array $entries,
        string $branch,
        array $meta = []
    ): Voucher {
        // Validate entries balance
        $totalDebit = array_sum(array_column($entries, 'debit'));
        $totalCredit = array_sum(array_column($entries, 'credit'));

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new \Exception(
                "Voucher is not balanced. Debit: {$totalDebit}, Credit: {$totalCredit}. " .
                "Difference: " . abs($totalDebit - $totalCredit)
            );
        }

        if (empty($entries)) {
            throw new \Exception("Voucher must have at least one entry.");
        }

        return DB::transaction(function () use ($type, $date, $narration, $entries, $branch, $meta, $totalDebit) {
            // Generate voucher number
            $voucherNo = $this->generateVoucherNo($type, $branch);

            // Create voucher
            $voucher = Voucher::create([
                'voucher_no'     => $voucherNo,
                'voucher_type'   => $type,
                'voucher_date'   => $date,
                'narration'      => $narration,
                'total_amount'   => $totalDebit,
                'branch'         => $branch,
                'status'         => 'approved', // auto-generated vouchers are pre-approved
                'created_by_id'  => $meta['created_by_id'] ?? auth()->id(),
                'reference_type' => $meta['reference_type'] ?? null,
                'reference_id'   => $meta['reference_id'] ?? null,
                'approved_by'    => $meta['created_by_id'] ?? auth()->id(),
                'approved_at'    => now(),
            ]);

            // Create ledger entries
            foreach ($entries as $entry) {
                if (($entry['debit'] ?? 0) == 0 && ($entry['credit'] ?? 0) == 0) {
                    continue; // Skip zero entries
                }

                LedgerEntry::create([
                    'voucher_id'     => $voucher->id,
                    'account_id'     => $entry['account_id'],
                    'date'           => $date,
                    'debit'          => $entry['debit'] ?? 0,
                    'credit'         => $entry['credit'] ?? 0,
                    'narration'      => $entry['narration'] ?? $narration,
                    'branch'         => $branch,
                    'reference_type' => $meta['reference_type'] ?? null,
                    'reference_id'   => $meta['reference_id'] ?? null,
                ]);
            }

            return $voucher;
        });
    }

    /**
     * Get ledger statement for an account with running balance.
     */
    public function getLedgerStatement(
        int $accountId,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $branch = null
    ): array {
        $account = Account::findOrFail($accountId);

        // Calculate opening balance (before fromDate)
        $openingBalance = $account->opening_balance;
        if ($fromDate) {
            $priorQuery = LedgerEntry::where('account_id', $accountId)
                ->whereDate('date', '<', $fromDate);
            if ($branch) $priorQuery->where('branch', $branch);

            $prior = $priorQuery->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')->first();

            if (in_array($account->type, ['asset', 'expense'])) {
                $openingBalance += (float)$prior->d - (float)$prior->c;
            } else {
                $openingBalance += (float)$prior->c - (float)$prior->d;
            }
        }

        // Get entries in date range
        $query = LedgerEntry::with(['voucher', 'account'])
            ->where('account_id', $accountId)
            ->orderBy('date')
            ->orderBy('id');

        if ($fromDate) $query->whereDate('date', '>=', $fromDate);
        if ($toDate) $query->whereDate('date', '<=', $toDate);
        if ($branch) $query->where('branch', $branch);

        $entries = $query->get();

        // Calculate running balance
        $runningBalance = $openingBalance;
        $entriesWithBalance = [];

        foreach ($entries as $entry) {
            if (in_array($account->type, ['asset', 'expense'])) {
                $runningBalance += (float)$entry->debit - (float)$entry->credit;
            } else {
                $runningBalance += (float)$entry->credit - (float)$entry->debit;
            }

            $entriesWithBalance[] = [
                'entry'   => $entry,
                'balance' => round($runningBalance, 2),
            ];
        }

        return [
            'account'         => $account,
            'opening_balance' => round($openingBalance, 2),
            'entries'         => $entriesWithBalance,
            'closing_balance' => round($runningBalance, 2),
            'total_debit'     => $entries->sum('debit'),
            'total_credit'    => $entries->sum('credit'),
        ];
    }

    /**
     * Generate voucher number: TYPE-BRANCH_PREFIX-YYMMDD-SEQ
     * e.g. RV-RJ-260611-001
     */
    private function generateVoucherNo(string $type, string $branch): string
    {
        $prefix = Voucher::getTypePrefix($type);
        $branchCode = strtoupper(substr($branch, 0, 2));
        $dateCode = now()->format('ymd');

        $base = "{$prefix}-{$branchCode}-{$dateCode}";

        $last = Voucher::where('voucher_no', 'like', "{$base}-%")
            ->orderByDesc('voucher_no')
            ->value('voucher_no');

        if ($last) {
            $seq = (int) substr($last, -3) + 1;
        } else {
            $seq = 1;
        }

        return $base . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
}
