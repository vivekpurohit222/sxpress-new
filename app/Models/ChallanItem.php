<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallanItem extends Model
{
    use HasFactory;

    protected $table = 'challan_items';

    protected $fillable = [
        'challan_id',
        'challan_no',
        'gr_no',
        'gr_id',
        'nugs',
        'meth',
        'description',
        'weight',
        'paid',
        'to_pay',
        'sur_ch',
        'c_r',
        'other',
    ];

    protected $casts = [
        'nugs'   => 'integer',
        'weight' => 'decimal:2',
        'paid'   => 'decimal:2',
        'to_pay' => 'decimal:2',
        'sur_ch' => 'decimal:2',
        'c_r'    => 'decimal:2',
        'other'  => 'decimal:2',
    ];

    /**
     * Get the challan this item belongs to.
     */
    public function challan(): BelongsTo
    {
        return $this->belongsTo(challan::class, 'challan_id');
    }

    /**
     * Get the GR this item references.
     */
    public function gr(): BelongsTo
    {
        return $this->belongsTo(Gr::class, 'gr_id');
    }
}
