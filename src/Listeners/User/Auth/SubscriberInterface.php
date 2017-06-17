<?php

namespace OpenDominion\Listeners\User\Auth;

use OpenDominion\Events\UserLoginEvent;
use OpenDominion\Listeners\SubscriberInterface as BaseSubscriberInterface;

interface SubscriberInterface extends BaseSubscriberInterface
{
//    public function onRegister();

    public function onLogin(UserLoginEvent $event);

//    public function onFailedLogin();

//    public function onLogout();
}
