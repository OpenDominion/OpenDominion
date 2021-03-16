<?php

namespace OpenDominion\Http\Controllers\Auth;

use Auth;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use OpenDominion\Helpers\DiscordHelper;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\DiscordUser;
use OpenDominion\Services\DiscordService;
use OpenDominion\Services\Dominion\SelectorService;

class DiscordConnectController extends AbstractController
{
    /**
     * @var DiscordHelper
     */
    protected $discordHelper;

    /**
     * @var DiscordService
     */
    protected $discordService;

    public function __construct()
    {
        $this->discordHelper = app(DiscordHelper::class);
        $this->discordService = app(DiscordService::class);
    }

    public function discordUnlink(Request $request)
    {
        $user = Auth::user();

        $user->discordUser()->delete();
        $request->session()->flash('alert-success', 'Discord account has been unlinked.');

        return redirect()->route('settings');
    }

    public function discordLinkCallback(Request $request)
    {
        $code = $request->get('code');
        $user = Auth::user();

        if ($code == null) {
            $request->session()->flash('alert-danger', 'Invalid authorization code.');
        } else {
            $this->discordService->authorize($user, $code, $this->discordHelper->getDiscordUserCallbackUrl());
            $request->session()->flash('alert-success', 'Discord account has been linked.');
        }

        return redirect()->route('settings');
    }

    public function discordJoinCallback(Request $request)
    {
        $code = $request->get('code');
        $user = Auth::user();

        $discordUser = $user->discordUser()->first();
        $dominionSelectorService = app(SelectorService::class);
        $selectedDominion = $dominionSelectorService->getUserSelectedDominion();

        if ($selectedDominion == null) {
            $request->session()->flash('alert-warning', 'You must first select a dominion.');
            return redirect()->route('dashboard');
        }

        if ($selectedDominion->realm->number == 0) {
            $request->session()->flash('alert-danger', 'Discord is not enabled for this realm.');
            return redirect()->route('dominion.status');
        }

        if (!$selectedDominion->round->discord_guild_id) {
            $request->session()->flash('alert-danger', 'Discord is not enabled for this round.');
            return redirect()->route('dominion.status');
        }

        if ($code == null && $discordUser !== null && $discordUser->expires_at > now()) {
            $accessToken = $this->discordService->refreshToken($discordUser, $this->discordHelper->getDiscordGuildCallbackUrl());
        } else {
            $accessToken = $this->discordService->authorize($user, $code, $this->discordHelper->getDiscordGuildCallbackUrl());
            $discordUser = $user->discordUser()->first();
        }

        $this->discordService->joinDiscordGuild($discordUser, $selectedDominion->realm, $accessToken);

        return redirect()->away(sprintf(
            'https://discord.com/channels/%s/',
            $selectedDominion->realm->round->discord_guild_id
        ));
    }
}
