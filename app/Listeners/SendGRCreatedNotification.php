<?php

namespace App\Listeners;

use App\Events\GRCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener for GRCreated event.
 * Queue-ready: implement ShouldQueue for async processing.
 *
 * TODO: Integrate with WhatsApp provider for SMS/WhatsApp notifications.
 *
 * Per SXPRESS_PHASE_11 - Notification System Foundation.
 */
class SendGRCreatedNotification implements ShouldQueue
{
    public function handle(GRCreated $event): void
    {
        // TODO: When WhatsApp integration is ready, uncomment:
        // $gr = $event->gr;
        // Notification::send($gr->consignee, new GRCreatedNotification($gr));

        // For now, log the event (queue-ready architecture)
        \Log::info('GR Created event fired', [
            'gr_no'      => $event->gr->gr_no,
            'consignor'  => $event->gr->consignor,
            'consignee'  => $event->gr->consignee,
            'from'       => $event->gr->from_dest,
            'to'         => $event->gr->to_dest,
            'total'      => $event->gr->total_amount,
        ]);
    }
}