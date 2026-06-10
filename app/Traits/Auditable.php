<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Auditable Trait
 *
 * Add this trait to any model to automatically log changes.
 * Per SXPRESS_PHASE_13 - Audit Logging.
 *
 * Usage:
 *   class Gr extends Model
 *   {
 *       use Auditable;
 *   }
 *
 * Events automatically logged:
 *   - created: When a new record is created
 *   - updated: When a record is modified
 *   - deleted: When a record is deleted
 *   - status_changed: When the 'status' field changes
 *
 * Note: Status change logging requires the model to have a 'status' field
 * and the observer to detect changes.
 */
trait Auditable
{
    /** Static cache to store old values during update (avoids polluting model attributes). */
    protected static array $auditCache = [];

    /**
     * Boot the auditable trait.
     * Registers model event observers.
     */
    public static function bootAuditable(): void
    {
        // Log creation
        static::created(function (Model $model) {
            try {
                ActivityLog::logCreate($model);
            } catch (\Throwable $e) {
                \Log::warning('Audit log failed on create: ' . $e->getMessage());
            }
        });

        // Log update with old/new values
        static::updating(function (Model $model) {
            static::$auditCache[$model->getKey()] = $model->getOriginal();
        });

        static::updated(function (Model $model) {
            try {
                $oldValues = static::$auditCache[$model->getKey()] ?? $model->getOriginal();
                $newValues = $model->getChanges();

                if (isset($oldValues['status']) && isset($newValues['status']) && $oldValues['status'] !== $newValues['status']) {
                    ActivityLog::logStatusChange($model, $oldValues['status'], $newValues['status']);
                } else {
                    ActivityLog::logUpdate($model, $oldValues);
                }
            } catch (\Throwable $e) {
                \Log::warning('Audit log failed on update: ' . $e->getMessage());
            }
            unset(static::$auditCache[$model->getKey()]);
        });

        // Log deletion
        static::deleting(function (Model $model) {
            try {
                ActivityLog::logDelete($model);
            } catch (\Throwable $e) {
                \Log::warning('Audit log failed on delete: ' . $e->getMessage());
            }
        });
    }

    /**
     * Get the activity logs for this model.
     */
    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    /**
     * Check if a specific field changed.
     */
    public function fieldChanged(string $field): bool
    {
        return $this->isDirty($field);
    }
}