<?php

namespace OpenDominion\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use OpenDominion\Events\UserRegisteredEvent;
use OpenDominion\Listeners\SendUserRegistrationNotification;
use OpenDominion\Listeners\Subscribers\AnalyticsSubscriber;

//use OpenDominion\Events\UserLoginEvent;
//use OpenDominion\Listeners\User\ActivityListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        UserRegisteredEvent::class => [
            SendUserRegistrationNotification::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        \OpenDominion\Listeners\User\Auth\ActivitySubscriber::class,
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
