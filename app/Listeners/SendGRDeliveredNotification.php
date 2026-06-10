<?php

namespace App\Listeners;

use App\Events\GRDelivered;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener for GRDelivered event.
 * Queue-ready: implement ShouldQueue for async processing.
 *
 * TODO: Integrate with WhatsApp provider for SMS/WhatsApp notifications.
 *
 * Per SXPRESS_PHASE_11 - Notification System Foundation.
 */
class SendGRDeliveredNotification implements ShouldQueue
{
    public function handle(GRDelivered $event): void
    {
        // TODO: When WhatsApp integration is ready, uncomment:
        // Notification::send($event->gr->consignor, new GRDeliveredNotification($event->gr));

        \Log::info('GR Delivered event fired', [
            'gr_no'      => $event->gr->gr_no,
            'consignor'  => $event->gr->consignor,
            'consignee'  => $event->gr->consignee,
            'delivered_at' => $event->gr->delivered_at,
        ]);
    }
}