<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Freight extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'frieghts';

    protected $fillable = [
        'memo_no',
        'memo_date',
        'gr_no',
        'consignor',
        'consignee',
        'freight_amount',
        'other_charges',
        'total',
        'payment_type',
        'payment_status',
        'remarks',
        'office',
        'created_by_id',
        // Legacy/Indian freight memo columns
        'fm_no',
        'fm_date',
        'from_dest',
        'from_branch_id',
        'to_dest',
        'to_branch_id',
        'truck_no',
        'truck_id',
        'entry_1', 'entry_1_amount',
        'entry_2', 'entry_2_amount',
        'entry_3', 'entry_3_amount',
        'entry_4', 'entry_4_amount',
        'total_amount',
        'truck_freight',
        'commission',
        'extra',
        'balance_due',
        'note',
    ];

    protected $casts = [
        'memo_date'      => 'date',
        'fm_date'        => 'date',
        'freight_amount' => 'decimal:2',
        'other_charges'  => 'decimal:2',
        'total'          => 'decimal:2',
        'total_amount'   => 'decimal:2',
    ];

    public function gr()
    {
        return $this->belongsTo(Gr::class, 'gr_no', 'gr_no');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function scopeForOffice($query, $office)
    {
        return $query->where('office', $office);
    }
}
