<?php

namespace App\Listeners;

use App\Events\PODUploaded;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener for PODUploaded event.
 * Queue-ready: implement ShouldQueue for async processing.
 *
 * TODO: Integrate with WhatsApp provider for SMS/WhatsApp notifications.
 *
 * Per SXPRESS_PHASE_11 - Notification System Foundation.
 */
class SendPODUploadedNotification implements ShouldQueue
{
    public function handle(PODUploaded $event): void
    {
        // TODO: When WhatsApp integration is ready, uncomment:
        // Notification::send($event->gr->consignor, new PODUploadedNotification($event->gr, $event->podFilePath));

        \Log::info('POD Uploaded event fired', [
            'gr_no'       => $event->gr->gr_no,
            'pod_file'    => $event->podFilePath,
            'pod_note'    => $event->podNote,
            'uploaded_by' => $event->gr->pod_uploaded_by,
        ]);
    }
}