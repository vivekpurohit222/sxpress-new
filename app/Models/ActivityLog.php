<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ActivityLog Model
 *
 * Tracks all changes to key entities in the system.
 * Per SXPRESS_PHASE_13 - Audit Logging.
 *
 * Logs: Created, Updated, Deleted, Status Changed events
 * For: GR, Gatepass, Challan, Freight, POD, Users
 */
class ActivityLog extends Model
{
    const UPDATED_AT = null; // append-only table — no updated_at column

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'entity_description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Get the user who performed this action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subject entity (polymorphic)
     */
    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * Log a model creation
     */
    public static function logCreate($model, $user = null): self
    {
        return self::create([
            'user_id' => $user?->id ?? auth()->id(),
            'action' => 'created',
            'entity_type' => get_class($model),
            'entity_id' => $model->id,
            'entity_description' => self::getDescription($model),
            'new_values' => $model->getAttributes(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log a model update
     */
    public static function logUpdate($model, $oldValues, $user = null): self
    {
        return self::create([
            'user_id' => $user?->id ?? auth()->id(),
            'action' => 'updated',
            'entity_type' => get_class($model),
            'entity_id' => $model->id,
            'entity_description' => self::getDescription($model),
            'old_values' => $oldValues,
            'new_values' => $model->getChanges(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log a model deletion
     */
    public static function logDelete($model, $user = null): self
    {
        return self::create([
            'user_id' => $user?->id ?? auth()->id(),
            'action' => 'deleted',
            'entity_type' => get_class($model),
            'entity_id' => $model->id,
            'entity_description' => self::getDescription($model),
            'old_values' => $model->getAttributes(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log a status change
     */
    public static function logStatusChange($model, $oldStatus, $newStatus, $user = null): self
    {
        return self::create([
            'user_id' => $user?->id ?? auth()->id(),
            'action' => 'status_changed',
            'entity_type' => get_class($model),
            'entity_id' => $model->id,
            'entity_description' => self::getDescription($model) . " [{$oldStatus} → {$newStatus}]",
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $newStatus],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get a human-readable description of the model
     */
    private static function getDescription($model): string
    {
        $type = class_basename($model);

        return match (true) {
            isset($model->gr_no) => "{$type}: {$model->gr_no}",
            isset($model->memo_no) => "{$type}: {$model->memo_no}",
            isset($model->gp_no) => "{$type}: {$model->gp_no}",
            isset($model->challan_no) => "{$type}: {$model->challan_no}",
            isset($model->name) => "{$type}: {$model->name}",
            isset($model->vehicle_number) => "{$type}: {$model->vehicle_number}",
            isset($model->driver_name) => "{$type}: {$model->driver_name}",
            default => "{$type} #{$model->id}",
        };
    }

    /**
     * Scope to filter by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by entity type
     */
    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope to filter by action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}