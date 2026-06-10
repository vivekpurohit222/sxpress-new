<?php

namespace App\Events;

use App\Models\Gr;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a POD (Proof of Delivery) is uploaded.
 * Per SXPRESS_PHASE_11 - Notification System Foundation.
 */
class PODUploaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Gr $gr,
        public string $podFilePath,
        public ?string $podNote = null
    ) {}
}