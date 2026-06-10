<?php

namespace App\Events;

use App\Models\Gr;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a GR is dispatched (linked to a Gatepass).
 * Per SXPRESS_PHASE_11 - Notification System Foundation.
 */
class GRDispatched
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Gr $gr,
        public int $gatepassId
    ) {}
}