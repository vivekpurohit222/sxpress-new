<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    use HasFactory;

    protected $table = 'ledger_entries';

    protected $fillable = [
        'voucher_id', 'account_id', 'date', 'debit', 'credit',
        'narration', 'cheque_no', 'cheque_date', 'reconciliation_status', 'cleared_date',
        'branch', 'reference_type', 'reference_id',
    ];

    protected $casts = [
        'date'         => 'date',
        'cheque_date'  => 'date',
        'cleared_date' => 'date',
        'debit'        => 'decimal:2',
        'credit'       => 'decimal:2',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeForBranch($query, string $branch)
    {
        return $query->where('branch', $branch);
    }

    public function scopeDateRange($query, $from, $to)
    {
        if ($from) $query->whereDate('date', '>=', $from);
        if ($to) $query->whereDate('date', '<=', $to);
        return $query;
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function getAmountAttribute(): float
    {
        return $this->debit > 0 ? $this->debit : -$this->credit;
    }

    /**
     * Calculate running balance for an account up to a given date.
     */
    public static function getBalance(int $accountId, ?string $upToDate = null, ?string $branch = null): float
    {
        $account = Account::find($accountId);
        if (!$account) return 0;

        $query = self::where('account_id', $accountId);

        if ($upToDate) {
            $query->whereDate('date', '<=', $upToDate);
        }
        if ($branch) {
            $query->where('branch', $branch);
        }

        $totals = $query->selectRaw('COALESCE(SUM(debit),0) as total_debit, COALESCE(SUM(credit),0) as total_credit')->first();

        $debit = (float) $totals->total_debit;
        $credit = (float) $totals->total_credit;

        // Asset & Expense accounts: balance = debit - credit + opening
        // Liability, Income, Equity: balance = credit - debit + opening
        $opening = (float) $account->opening_balance;

        if (in_array($account->type, ['asset', 'expense'])) {
            return $opening + $debit - $credit;
        } else {
            return $opening + $credit - $debit;
        }
    }
}
