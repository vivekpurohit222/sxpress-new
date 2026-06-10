<?php

namespace App\Services;

use App\Models\Gr;
use App\Models\User;
use Exception;

/**
 * GrWorkflowService
 *
 * Manages GR document state transitions per the state machine defined in
 * SXPRESS_LOGIC_SKILL section 3.
 *
 * State flow:
 *   [created] ──→ [dispatched] ──→ [in_transit] ──→ [delivered] ──→ [closed]
 *        ↓               ↓
 *   [cancelled]      [cancelled]    (Admin+ only)
 */
class GrWorkflowService
{
    /**
     * Valid state transitions map.
     * Key = current state, Value = allowed next states.
     */
    private array $transitions = [
        'created'    => ['dispatched', 'cancelled'],
        'dispatched' => ['in_transit', 'cancelled'],
        'in_transit' => ['delivered'],
        'delivered'  => ['closed'],
        'closed'     => [],
        'cancelled'  => [],
    ];

    /**
     * Transition a GR to a new state with authorization checks.
     *
     * @param  Gr    $gr
     * @param  string $newStatus
     * @param  User  $user  The user performing the transition
     * @throws Exception
     */
    public function transition(Gr $gr, string $newStatus, User $user): void
    {
        $currentStatus = $gr->status ?? 'created';

        // Validate transition is allowed
        if (!in_array($newStatus, $this->transitions[$currentStatus] ?? [])) {
            throw new Exception("Invalid transition: {$currentStatus} → {$newStatus}");
        }

        // Only SuperAdmin can close a GR
        if ($newStatus === 'closed' && !$user->hasRole('SuperAdmin')) {
            throw new Exception("Only SuperAdmin can close a GR.");
        }

        // Only Admin or SuperAdmin can cancel
        if ($newStatus === 'cancelled' && !$user->hasAnyRole(['SuperAdmin', 'Admin'])) {
            throw new Exception("Only Admin or SuperAdmin can cancel a GR.");
        }

        $gr->update([
            'status'            => $newStatus,
            'status_updated_at' => now(),
            'status_updated_by' => $user->id,
        ]);
    }

    /**
     * Get the badge class for a given GR status.
     * Used in Blade views.
     */
    public static function badgeClass(string $status): string
    {
        return match ($status) {
            'created'    => 'bg-secondary',
            'dispatched' => 'bg-primary',
            'in_transit' => 'bg-warning text-dark',
            'delivered'  => 'bg-info',
            'closed'     => 'bg-success',
            'cancelled'  => 'bg-danger',
            default      => 'bg-light text-dark',
        };
    }

    /**
     * Get human-readable label for a status.
     */
    public static function label(string $status): string
    {
        return match ($status) {
            'created'    => 'Created',
            'dispatched' => 'Dispatched',
            'in_transit' => 'In Transit',
            'delivered'  => 'Delivered',
            'closed'     => 'Closed',
            'cancelled'  => 'Cancelled',
            default      => ucfirst($status),
        };
    }

    /**
     * Check if a transition is valid without performing it.
     */
    public function canTransition(Gr $gr, string $newStatus): bool
    {
        $currentStatus = $gr->status ?? 'created';
        return in_array($newStatus, $this->transitions[$currentStatus] ?? []);
    }
}