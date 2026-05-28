<?php

namespace OpenDominion\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Queue\Events\QueueBusy;
use OpenDominion\Events\DominionSavedEvent;
use OpenDominion\Events\InfoOpCreatingEvent;
use OpenDominion\Events\UserRegisteredEvent;
use OpenDominion\Listeners\DominionSaved;
use OpenDominion\Listeners\InfoOpCreating;
use OpenDominion\Listeners\SendQueueBusyAlert;
use OpenDominion\Listeners\SendUserRegistrationNotification;
use OpenDominion\Listeners\SetUserDefaultSettings;
use OpenDominion\Listeners\Subscribers\ActivitySubscriber;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        DominionSavedEvent::class => [
            DominionSaved::class,
        ],
        InfoOpCreatingEvent::class => [
            InfoOpCreating::class,
        ],
        UserRegisteredEvent::class => [
            SetUserDefaultSettings::class,
            SendUserRegistrationNotification::class,
        ],
        QueueBusy::class => [
            SendQueueBusyAlert::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        ActivitySubscriber::class,
    ];

    /**
     * Register any other events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
