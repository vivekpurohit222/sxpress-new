<?php

namespace App\Events;

use App\Models\Gr;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a GR is marked as delivered (POD uploaded).
 * Per SXPRESS_PHASE_11 - Notification System Foundation.
 */
class GRDelivered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Gr $gr
    ) {}
}