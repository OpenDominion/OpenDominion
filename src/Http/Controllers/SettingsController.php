<?php

namespace OpenDominion\Http\Controllers;

use Auth;

class SettingsController extends AbstractController
{
    public function getIndex()
    {
        return redirect()->route('settings.account');
    }

    public function getAccount()
    {
        return view('pages.settings.account', [
            'user' => Auth::user(),
        ]);
    }

    public function getNotifications()
    {
        return view('pages.settings.notifications', [
            'user' => Auth::user(),
        ]);
    }

    public function getSecurity()
    {
        return view('pages.settings.security', [
            'user' => Auth::user(),
        ]);
    }
}
