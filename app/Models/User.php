<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'office',
        'phone',
        'is_active',
        'branch_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    // ─── Relationships ───────────────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function permissions()
    {
        return $this->hasMany(UserPermission::class);
    }

    // ─── Role Checks ─────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isBranchManager(): bool
    {
        return $this->role === 'branch_manager';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    // ─── Permission Check ────────────────────────────────────────────

    /**
     * Check if this user can access a given module permission.
     *
     * - super_admin → always true
     * - branch_manager → check branch_permissions for their branch
     * - agent → check user_permissions for this user
     */
    public function can_access(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isBranchManager()) {
            return DB::table('branch_permissions')
                ->where('branch_id', $this->branch_id)
                ->where('permission', $permission)
                ->exists();
        }

        if ($this->isAgent()) {
            return DB::table('user_permissions')
                ->where('user_id', $this->id)
                ->where('permission', $permission)
                ->exists();
        }

        return false;
    }

    // ─── Scopes ──────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    // ─── Backward Compatibility ──────────────────────────────────────

    /**
     * Spatie-compatible hasRole() for backward compatibility with
     * existing operational controllers (GrController, etc.).
     * Will be removed once all controllers are migrated.
     */
    public function hasRole($role): bool
    {
        if ($role === 'SuperAdmin') return $this->isSuperAdmin();
        if ($role === 'BranchManager') return $this->isBranchManager();
        if ($role === 'Agent') return $this->isAgent();
        return false;
    }

    public function hasAnyRole($roles): bool
    {
        if (is_string($roles)) {
            $roles = explode('|', $roles);
        }
        foreach ($roles as $role) {
            if ($this->hasRole(trim($role))) return true;
        }
        return false;
    }
}
