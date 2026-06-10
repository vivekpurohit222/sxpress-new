<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class challan extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'challan_no',
        'from_dest',
        'from_branch_id',
        'challan_date',
        'to_dest',
        'to_branch_id',
        'vehicle_id',
        'driver_id',
        'truck_no',
        'truck_id',
        'driver_name',
        'license',
        'owner_name',
        'note',
        'total_items',
        'total_weight',
        'challan_total',
        'status',
        'office',
    ];

    protected $casts = [
        'challan_date' => 'date',
        'total_weight' => 'decimal:2',
        'challan_total' => 'decimal:2',
    ];

    /**
     * Get the truck driver
     */
    public function truckDriver()
    {
        return $this->belongsTo(truckdriver::class, 'truck_id');
    }

    /**
     * Get challan items.
     * Per SXPRESS_LOGIC_SKILL section 6.
     */
    public function items()
    {
        return $this->hasMany(ChallanItem::class);
    }

    /**
     * Get the vehicle assigned to this challan.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    /**
     * Get the driver assigned to this challan.
     */
    public function driver()
    {
        return $this->belongsTo(truckdriver::class, 'driver_id');
    }

    /**
     * Scope to filter by office
     */
    public function scopeForOffice($query, $office)
    {
        return $query->where('office', $office);
    }
}