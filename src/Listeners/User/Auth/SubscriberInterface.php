<?php

namespace OpenDominion\Listeners\User\Auth;

use OpenDominion\Events\UserLoggedInEvent;
use OpenDominion\Listeners\SubscriberInterface as BaseSubscriberInterface;

interface SubscriberInterface extends BaseSubscriberInterface
{
//    public function onRegister();

    public function onLogin(UserLoggedInEvent $event);

//    public function onFailedLogin();

//    public function onLogout();
}
