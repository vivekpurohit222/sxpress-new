<?php

namespace App\Models\Accounting;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\truckdriver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'expense_no', 'expense_date', 'expense_type', 'description', 'amount',
        'paid_to', 'account_id', 'paid_from_account_id', 'voucher_id',
        'vehicle_id', 'driver_id', 'branch', 'status',
        'approved_by', 'approved_at', 'created_by_id', 'notes',
        'gst_rate', 'gst_type', 'cgst_amount', 'sgst_amount', 'igst_amount', 'gst_total', 'vendor_gst_number',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'approved_at'  => 'datetime',
        'amount'       => 'decimal:2',
        'gst_rate'     => 'decimal:2',
        'cgst_amount'  => 'decimal:2',
        'sgst_amount'  => 'decimal:2',
        'igst_amount'  => 'decimal:2',
        'gst_total'    => 'decimal:2',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function gstEntry() { return $this->morphOne(\App\Models\Accounting\GstEntry::class, 'taxable'); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function paidFromAccount(): BelongsTo { return $this->belongsTo(Account::class, 'paid_from_account_id'); }
    public function voucher(): BelongsTo { return $this->belongsTo(Voucher::class); }
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); }
    public function driver(): BelongsTo { return $this->belongsTo(truckdriver::class, 'driver_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopeForBranch($query, $branch) { return $query->where('branch', $branch); }

    // ─── Helpers ─────────────────────────────────────────────────

    public static function getTypeLabel(string $type): string
    {
        return match($type) {
            'diesel'        => 'Diesel',
            'driver_salary' => 'Driver Salary',
            'repair'        => 'Repair & Maintenance',
            'tyre'          => 'Tyre',
            'office'        => 'Office Expense',
            'branch'        => 'Branch Expense',
            'misc'          => 'Miscellaneous',
            default         => ucfirst($type),
        };
    }

    /**
     * Map expense type to default account code.
     */
    public static function getDefaultAccountCode(string $type): string
    {
        return match($type) {
            'diesel'        => '4101',
            'driver_salary' => '4301',
            'repair'        => '4102',
            'tyre'          => '4103',
            'office'        => '4407',
            'branch'        => '4406',
            'misc'          => '4407',
            default         => '4407',
        };
    }
}
