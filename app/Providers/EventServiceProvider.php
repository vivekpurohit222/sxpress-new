<?php

namespace App\Providers;

use App\Events\GRCreated;
use App\Events\GRDelivered;
use App\Events\GRDispatched;
use App\Events\PODUploaded;
use App\Listeners\SendGRCreatedNotification;
use App\Listeners\SendGRDeliveredNotification;
use App\Listeners\SendGRDispatchedNotification;
use App\Listeners\SendPODUploadedNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // GR Events - Per SXPRESS_PHASE_11
        GRCreated::class => [
            SendGRCreatedNotification::class,
        ],
        GRDispatched::class => [
            SendGRDispatchedNotification::class,
        ],
        GRDelivered::class => [
            SendGRDeliveredNotification::class,
        ],
        PODUploaded::class => [
            SendPODUploadedNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
