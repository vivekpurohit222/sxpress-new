<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gr extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    // Per master doc section 5 - grs table schema
    protected $fillable = [
        'gr_no',
        'from_dest',
        'to_dest',
        'copy_date',
        'consignor',
        'consignor_address',
        'consignor_gst_no',
        'consignee',
        'consignee_address',
        'consignee_gst_no',
        'nugs',
        'meth',
        'eway_bill_number',
        'bill_amount',
        'description',
        'pm',
        'weight',
        'paid',
        'to_pay',
        'topay_collected',
        'topay_collected_date',
        'topay_collected_by',
        'status_updated_at',
        'status_updated_by',
        'frieght_amount',
        'sur_ch',
        'labour',
        'dd',
        'local_charge',
        'c_r',
        'other',
        'bc_amount',
        'total_amount',
        'rate_type',
        'rate',
        'office',
        'status',
        'delivery_status',
        'pod_file',
        'pod_date',
        'pod_note',
        'pod_uploaded_by',
        'delivered_at',
        'created_by_id',
        'from_branch_id',
        'to_branch_id',
        'consignor_id',
        'consignee_id',
        'gst_rate',
        'gst_type',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'gst_total',
    ];

    protected $casts = [
        'copy_date'            => 'date',
        'pod_date'             => 'date',
        'topay_collected_date' => 'date',
        'delivered_at'         => 'datetime',
        'status_updated_at'    => 'datetime',
        'paid'                 => 'boolean',
        'to_pay'               => 'boolean',
        'topay_collected'      => 'boolean',
        'bill_amount'          => 'decimal:2',
        'frieght_amount'       => 'decimal:2',
        'sur_ch'               => 'decimal:2',
        'labour'               => 'decimal:2',
        'dd'                   => 'decimal:2',
        'local_charge'         => 'decimal:2',
        'c_r'                  => 'decimal:2',
        'other'                => 'decimal:2',
        'bc_amount'            => 'decimal:2',
        'total_amount'         => 'decimal:2',
        'rate'                 => 'decimal:2',
        'weight'               => 'decimal:3',
        'nugs'                 => 'integer',
        'gst_rate'             => 'decimal:2',
        'cgst_amount'          => 'decimal:2',
        'sgst_amount'          => 'decimal:2',
        'igst_amount'          => 'decimal:2',
        'gst_total'            => 'decimal:2',
    ];

    /**
     * Get the GST entry for this GR (polymorphic)
     */
    public function gstEntry()
    {
        return $this->morphOne(\App\Models\Accounting\GstEntry::class, 'taxable');
    }

    /**
     * Get the branch this GR was booked from
     */
    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    /**
     * Get the branch this GR is destined for
     */
    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    /**
     * Get the consignor (customer)
     */
    public function consignorCustomer()
    {
        return $this->belongsTo(Customer::class, 'consignor_id');
    }

    /**
     * Get the consignee (customer)
     */
    public function consigneeCustomer()
    {
        return $this->belongsTo(Customer::class, 'consignee_id');
    }

    /**
     * Get the user who created this GR
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get gatepasses for this GR (via pivot)
     */
    public function gatepasses()
    {
        return $this->belongsToMany(gatepass::class, 'gatepass_gr', 'gr_id', 'gatepass_id')
                    ->withPivot('gr_no')
                    ->withTimestamps();
    }

    /**
     * Get challan items for this GR
     */
    public function challanItems()
    {
        return $this->hasMany(ChallanItem::class);
    }

    /**
     * Check if GR has a gatepass
     */
    public function hasGatepass()
    {
        return $this->gatepasses()->count() > 0;
    }

    /**
     * Scope to filter by branch
     */
    public function scopeFromBranch($query, $branchId)
    {
        return $query->where('from_branch_id', $branchId);
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by office (branch)
     */
    public function scopeForOffice($query, $office)
    {
        return $query->where('office', $office);
    }

    /**
     * Scope to search by GR number
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('gr_no', 'like', "%{$term}%");
    }
}