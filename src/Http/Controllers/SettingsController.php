<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use OpenDominion\Helpers\NotificationHelper;

class SettingsController extends AbstractController
{
    public function getIndex()
    {
        return redirect()->route('settings.account');
    }

    public function getAccount()
    {
        return view('pages.settings.account', [
        ]);
    }

    public function getNotifications()
    {
        return view('pages.settings.notifications', [
            'notificationHelper' => app(NotificationHelper::class),
        ]);
    }

    public function getSecurity()
    {
        return view('pages.settings.security', [
        ]);
    }
}
