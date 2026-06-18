<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Outstanding extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'outstanding';

    protected $fillable = [
        'party_type', 'party_name', 'type', 'invoice_ref', 'invoice_date',
        'total_amount', 'paid_amount', 'pending_amount', 'due_date', 'status',
        'branch', 'reference_type', 'reference_id', 'notes', 'created_by_id',
    ];

    protected $casts = [
        'invoice_date'   => 'date',
        'due_date'       => 'date',
        'total_amount'   => 'decimal:2',
        'paid_amount'    => 'decimal:2',
        'pending_amount' => 'decimal:2',
    ];

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeReceivable($query) { return $query->where('type', 'receivable'); }
    public function scopePayable($query) { return $query->where('type', 'payable'); }
    public function scopePending($query) { return $query->whereIn('status', ['pending', 'partial', 'overdue']); }
    public function scopeForBranch($query, $branch) { return $query->where('branch', $branch); }

    public function scopeOfParty($query, $type) { return $query->where('party_type', $type); }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Record a payment against this outstanding.
     */
    public function recordPayment(float $amount): void
    {
        $this->paid_amount += $amount;
        $this->pending_amount = max(0, $this->total_amount - $this->paid_amount);

        if ($this->pending_amount <= 0) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        }

        $this->save();
    }

    /**
     * Get age in days from invoice date.
     */
    public function getAgeDaysAttribute(): int
    {
        return Carbon::parse($this->invoice_date)->diffInDays(now());
    }

    /**
     * Get aging bucket label.
     */
    public function getAgingBucketAttribute(): string
    {
        $days = $this->age_days;
        if ($days <= 7) return '0-7 days';
        if ($days <= 15) return '8-15 days';
        if ($days <= 30) return '16-30 days';
        if ($days <= 60) return '31-60 days';
        if ($days <= 90) return '61-90 days';
        return '90+ days';
    }

    /**
     * Check and update overdue status.
     */
    public function checkOverdue(): void
    {
        if ($this->due_date && $this->due_date < now() && $this->status !== 'paid') {
            $this->update(['status' => 'overdue']);
        }
    }
}
