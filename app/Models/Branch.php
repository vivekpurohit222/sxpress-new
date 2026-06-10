<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_name',
        'branch_code',
        'gr_prefix',
        'name',         // legacy sync column
        'code',         // legacy sync column
        'address',
        'city',
        'state',
        'pincode',
        'phone',
        'email',
        'status',
        'is_active',
    ];

    protected $casts = [
        'status'    => 'boolean',
        'is_active' => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * GRs belonging to this branch (linked via office = branch_name).
     */
    public function grs(): HasMany
    {
        return $this->hasMany(Gr::class, 'office', 'branch_name');
    }

    /**
     * Users belonging to this branch.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'office', 'branch_name');
    }

    /**
     * Stations belonging to this branch.
     */
    public function stations(): HasMany
    {
        return $this->hasMany(Station::class ?? \stdClass::class);
    }

    /**
     * GR serial assignment for this branch.
     */
    public function serial(): HasOne
    {
        return $this->hasOne(BranchSerial::class, 'office', 'branch_name');
    }

    // ─────────────────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('branch_name', 'like', "%{$term}%")
              ->orWhere('branch_code', 'like', "%{$term}%")
              ->orWhere('city', 'like', "%{$term}%");
        });
    }
}
