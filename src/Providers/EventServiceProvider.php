<?php

namespace OpenDominion\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use OpenDominion\Events\InfoOpCreatingEvent;
use OpenDominion\Events\UserRegisteredEvent;
use OpenDominion\Listeners\SendUserRegistrationNotification;
use OpenDominion\Listeners\SetUserDefaultSettings;
use OpenDominion\Listeners\InfoOpCreating;
use OpenDominion\Listeners\Subscribers\ActivitySubscriber;
use OpenDominion\Listeners\Subscribers\AnalyticsSubscriber;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        InfoOpCreatingEvent::class => [
            InfoOpCreating::class,
        ],
        UserRegisteredEvent::class => [
            SetUserDefaultSettings::class,
            SendUserRegistrationNotification::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        ActivitySubscriber::class,
        AnalyticsSubscriber::class,
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
