<?php

namespace OpenDominion\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

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
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        \OpenDominion\Listeners\User\Auth\ActivitySubscriber::class,
        \OpenDominion\Listeners\User\Auth\AnalyticsSubscriber::class,
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
