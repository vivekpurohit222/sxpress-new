<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BranchSerial Model
 *
 * Stores GR serial number configuration per branch.
 * SuperAdmin assigns the starting GR number for each branch via /dash/serial-assign.
 * Once a branch has GR records, the serial is locked (cannot be changed).
 *
 * Per SXPRESS_LOGIC_SKILL section 13.
 */
class BranchSerial extends Model
{
    protected $table = 'branch_serials';

    protected $fillable = [
        'office',
        'gr_prefix',
        'start_from',
        'assigned_by',
        'assigned_at',
        'notes',
    ];

    protected $casts = [
        'start_from'  => 'integer',
        'assigned_at' => 'datetime',
    ];

    /**
     * Get the user who assigned this serial.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Check if this serial is locked (branch has GRs).
     */
    public function isLocked(): bool
    {
        return Gr::where('office', $this->office)->exists();
    }
}