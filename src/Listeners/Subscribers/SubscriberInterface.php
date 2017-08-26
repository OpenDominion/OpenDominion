<?php

namespace OpenDominion\Listeners\Subscribers;

use Illuminate\Events\Dispatcher;

interface SubscriberInterface
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events): void;
}

