<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class gatepass extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'gp_no',
        'gp_date',
        'consignor',
        'from_dest',
        'from_branch_id',
        'to_dest',
        'to_branch_id',
        'gr_no',
        'vehicle_no',
        'vehicle_id',
        'driver_id',
        'driver_name',
        'driver_license',
        'weight',
        'nugs',
        'pm',
        'frieght_amount',
        'labour_amount',
        'other',
        'dc_amount',
        'delivery_charge',
        'total_amount',
        'note',
        'remarks',
        'office',
        'status',
        'created_by_id',
    ];

    protected $casts = [
        'gp_date'         => 'date',
        'weight'          => 'decimal:3',
        'frieght_amount'  => 'decimal:2',
        'labour_amount'   => 'decimal:2',
        'other'           => 'decimal:2',
        'dc_amount'       => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'nugs'            => 'integer',
    ];

    /**
     * Get GRs associated with this gatepass (via pivot table).
     */
    public function grs()
    {
        return $this->belongsToMany(Gr::class, 'gatepass_gr', 'gatepass_id', 'gr_id')
                    ->withPivot('gr_no')
                    ->withTimestamps();
    }

    /**
     * Get the vehicle assigned to this gatepass.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    /**
     * Get the driver assigned to this gatepass.
     */
    public function driver()
    {
        return $this->belongsTo(truckdriver::class, 'driver_id');
    }

    /**
     * Get the user who created this gatepass.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Scope to filter by office.
     */
    public function scopeForOffice($query, $office)
    {
        return $query->where('office', $office);
    }
}
