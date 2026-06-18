<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'voucher_no', 'voucher_type', 'voucher_date', 'narration',
        'total_amount', 'branch', 'status', 'approved_by', 'approved_at',
        'created_by_id', 'reference_type', 'reference_id',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'approved_at'  => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeOfType($query, string $type)
    {
        return $query->where('voucher_type', $type);
    }

    public function scopeForBranch($query, string $branch)
    {
        return $query->where('branch', $branch);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeDateRange($query, $from, $to)
    {
        if ($from) $query->whereDate('voucher_date', '>=', $from);
        if ($to) $query->whereDate('voucher_date', '<=', $to);
        return $query;
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function isBalanced(): bool
    {
        $totals = $this->entries()->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')->first();
        return round((float)$totals->total_debit, 2) === round((float)$totals->total_credit, 2);
    }

    public function getTotalDebitAttribute(): float
    {
        return (float) $this->entries()->sum('debit');
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->entries()->sum('credit');
    }

    public static function getTypeLabel(string $type): string
    {
        return match($type) {
            'receipt' => 'Receipt Voucher',
            'payment' => 'Payment Voucher',
            'contra'  => 'Contra Voucher',
            'journal' => 'Journal Voucher',
            default   => ucfirst($type),
        };
    }

    public static function getTypePrefix(string $type): string
    {
        return match($type) {
            'receipt' => 'RV',
            'payment' => 'PV',
            'contra'  => 'CV',
            'journal' => 'JV',
            default   => 'VR',
        };
    }
}
