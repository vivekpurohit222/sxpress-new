<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GstEntry extends Model
{
    use HasFactory;

    protected $table = 'gst_entries';

    protected $fillable = [
        'taxable_type', 'taxable_id', 'transaction_date', 'party_name',
        'party_gst_number', 'gst_rate', 'taxable_value', 'cgst_amount',
        'sgst_amount', 'igst_amount', 'total_tax', 'tax_direction',
        'gst_type', 'branch', 'hsn_sac_code',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'gst_rate'         => 'decimal:2',
        'taxable_value'    => 'decimal:2',
        'cgst_amount'      => 'decimal:2',
        'sgst_amount'      => 'decimal:2',
        'igst_amount'      => 'decimal:2',
        'total_tax'        => 'decimal:2',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function taxable(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeOutput($query)
    {
        return $query->where('tax_direction', 'output');
    }

    public function scopeInput($query)
    {
        return $query->where('tax_direction', 'input');
    }

    public function scopeForBranch($query, string $branch)
    {
        return $query->where('branch', $branch);
    }

    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereDate('transaction_date', '>=', $from)
                     ->whereDate('transaction_date', '<=', $to);
    }

    public function scopeOfGstType($query, string $type)
    {
        return $query->where('gst_type', $type);
    }

    public function scopeForRate($query, $rate)
    {
        return $query->where('gst_rate', $rate);
    }
}
