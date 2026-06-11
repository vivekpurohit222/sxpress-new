<?php

namespace App\Traits;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * OfficeScopeTrait
 *
 * Provides office-scoped query helpers for all controllers.
 * SuperAdmin sees all branches; other roles see only their own office.
 *
 * Usage in controller:
 *   use OfficeScopeTrait;
 *   $items = $this->officeScope(GR::query())->latest()->get();
 *
 * Per MASTER_DOCUMENT section 13 Golden Rule #1 and
 * SXPRESS_LOGIC_SKILL section 2.
 */
trait OfficeScopeTrait
{
    /**
     * Apply office scope to a query.
     * SuperAdmin bypasses the filter (sees all branches) UNLESS impersonating an office.
     */
    protected function officeScope(Builder $query): Builder
    {
        if (auth()->user()->hasRole('SuperAdmin')) {
            // If impersonating, scope to impersonated office
            if ($office = session('impersonating_office')) {
                return $query->where('office', $office);
            }
            return $query;
        }
        return $query->where('office', auth()->user()->office);
    }

    /**
     * Return the current working office.
     * If SuperAdmin is impersonating, returns the impersonated office.
     */
    protected function currentOffice(): string
    {
        if (auth()->user()->hasRole('SuperAdmin') && $office = session('impersonating_office')) {
            return $office;
        }
        return auth()->user()->office;
    }

    /**
     * Check if current user is SuperAdmin.
     */
    protected function isSuperAdmin(): bool
    {
        return auth()->user()->hasRole('SuperAdmin');
    }

    /**
     * Check if SuperAdmin is currently impersonating an office.
     */
    protected function isImpersonating(): bool
    {
        return auth()->user()->hasRole('SuperAdmin') && session()->has('impersonating_office');
    }

    /**
     * Check if current user is Admin or higher (SuperAdmin, Admin).
     */
    protected function isAdminOrAbove(): bool
    {
        return auth()->user()->hasAnyRole(['SuperAdmin', 'Admin']);
    }

    /**
     * Check if current user is Manager or higher.
     */
    protected function isManagerOrAbove(): bool
    {
        return auth()->user()->hasAnyRole(['SuperAdmin', 'Admin', 'Manager']);
    }

    /**
     * Check if current user is Staff or higher.
     */
    protected function isStaffOrAbove(): bool
    {
        return auth()->user()->hasAnyRole(['SuperAdmin', 'Admin', 'Manager', 'Staff']);
    }

    /**
     * Get office-scoped branch filter value from request (for SuperAdmin dropdown).
     * Returns null if not SuperAdmin or no branch selected.
     */
    protected function getBranchFilter($request): ?string
    {
        if (!$this->isSuperAdmin()) {
            return null;
        }
        return $request->branch ?? null;
    }

    /**
     * Apply optional branch filter to a query (SuperAdmin only).
     */
    protected function applyBranchFilter(Builder $query, ?string $branch): Builder
    {
        if ($this->isSuperAdmin() && $branch) {
            return $query->where('office', $branch);
        }
        return $query;
    }

    /**
     * Get all branch names for SuperAdmin filter dropdown.
     */
    protected function getBranchOptions(): \Illuminate\Support\Collection
    {
        return $this->isSuperAdmin() ? Branch::pluck('name', 'name') : collect();
    }
}