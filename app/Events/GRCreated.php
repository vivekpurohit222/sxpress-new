<?php

namespace App\Events;

use App\Models\Gr;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a new GR is created.
 * Per SXPRESS_PHASE_11 - Notification System Foundation.
 */
class GRCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Gr $gr
    ) {}
}