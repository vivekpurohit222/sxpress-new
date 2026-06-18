<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'type', 'parent_id', 'is_group',
        'branch', 'opening_balance', 'is_system', 'is_active',
    ];

    protected $casts = [
        'is_group'        => 'boolean',
        'is_system'       => 'boolean',
        'is_active'       => 'boolean',
        'opening_balance' => 'decimal:2',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id')->orderBy('code');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGroups($query)
    {
        return $query->where('is_group', true);
    }

    public function scopeTransactional($query)
    {
        return $query->where('is_group', false);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function getFullPathAttribute(): string
    {
        $path = $this->name;
        $parent = $this->parent;
        while ($parent) {
            $path = $parent->name . ' > ' . $path;
            $parent = $parent->parent;
        }
        return $path;
    }

    public function isDeleteable(): bool
    {
        if ($this->is_system) return false;
        if ($this->children()->exists()) return false;
        // Will add ledger_entries check in Phase 3
        return true;
    }

    public static function getTypeLabel(string $type): string
    {
        return match($type) {
            'asset'     => 'Asset',
            'liability' => 'Liability',
            'income'    => 'Income',
            'expense'   => 'Expense',
            'equity'    => 'Equity',
            default     => ucfirst($type),
        };
    }
}
