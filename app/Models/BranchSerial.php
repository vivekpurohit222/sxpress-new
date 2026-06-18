<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BranchSerial Model
 *
 * Stores serial number range configuration per branch per module per financial year.
 * Each branch gets a range (e.g. 1-999999) for each module (GR, Challan, FM, GP).
 * The current_value tracks how many numbers have been used.
 */
class BranchSerial extends Model
{
    protected $table = 'branch_serials';

    protected $fillable = [
        'branch_id',
        'module',
        'fy_year',
        'range_start',
        'range_end',
        'current_value',
    ];

    protected $casts = [
        'branch_id'     => 'integer',
        'range_start'   => 'integer',
        'range_end'     => 'integer',
        'current_value' => 'integer',
    ];

    /**
     * The branch this serial range belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
