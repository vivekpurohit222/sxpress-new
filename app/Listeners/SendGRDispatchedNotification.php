<?php

namespace App\Listeners;

use App\Events\GRDispatched;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener for GRDispatched event.
 * Queue-ready: implement ShouldQueue for async processing.
 *
 * TODO: Integrate with WhatsApp provider for SMS/WhatsApp notifications.
 *
 * Per SXPRESS_PHASE_11 - Notification System Foundation.
 */
class SendGRDispatchedNotification implements ShouldQueue
{
    public function handle(GRDispatched $event): void
    {
        // TODO: When WhatsApp integration is ready, uncomment:
        // Notification::send($event->gr->consignee, new GRDispatchedNotification($event->gr));

        \Log::info('GR Dispatched event fired', [
            'gr_no'        => $event->gr->gr_no,
            'gatepass_id'  => $event->gatepassId,
            'consignee'    => $event->gr->consignee,
            'from'         => $event->gr->from_dest,
            'to'           => $event->gr->to_dest,
        ]);
    }
}