<?php

namespace OpenDominion\Listeners;

use Illuminate\Events\Dispatcher;

interface SubscriberInterface
{
    public function subscribe(Dispatcher $events);
}
