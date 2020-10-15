<?php

namespace OpenDominion\Http\Controllers\Auth;

use Auth;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\DiscordUser;
use OpenDominion\Services\DiscordUserService;

class DiscordConnectController extends AbstractController
{
    /**
     * @var DiscordUserService
     */
    private $discordUserService;

    public function __construct()
    {
        $this->discordUserService = app(DiscordUserService::class);
    }

    public function discordOauthCallback(Request $request)
    {
        $code = $request->get('code');
        $user = Auth::user();

        $result = $this->discordUserService->connectDiscordUser($user, $code);

        if($result) {
            $request->session()->flash('alert-success', 'Discord account has been linked.');
        } else {
            $request->session()->flash('alert-warning', 'Account could not be linked at this time. Please try again later.');
        }

        return redirect()->to(route('settings'));
    }
}
