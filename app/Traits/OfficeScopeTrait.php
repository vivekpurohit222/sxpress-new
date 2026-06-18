<?php

namespace App\Traits;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;

/**
 * OfficeScopeTrait
 *
 * Provides office-scoped query helpers for all controllers.
 * SuperAdmin sees all branches; other roles see only their own office.
 */
trait OfficeScopeTrait
{
    protected function officeScope(Builder $query): Builder
    {
        if (auth()->user()->isSuperAdmin()) {
            if ($office = session('impersonating_office')) {
                return $query->where('office', $office);
            }
            return $query;
        }
        return $query->where('office', auth()->user()->office);
    }

    protected function currentOffice(): string
    {
        if (auth()->user()->isSuperAdmin() && $office = session('impersonating_office')) {
            return $office;
        }
        return auth()->user()->office;
    }

    protected function isSuperAdmin(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    protected function isImpersonating(): bool
    {
        return auth()->user()->isSuperAdmin() && session()->has('impersonating_office');
    }

    protected function getBranchOptions(): \Illuminate\Support\Collection
    {
        return $this->isSuperAdmin() ? Branch::pluck('branch_name', 'branch_name') : collect();
    }
}
