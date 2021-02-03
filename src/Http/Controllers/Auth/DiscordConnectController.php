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

    public function discordLinkCallback(Request $request)
    {
        $code = $request->get('code');
        $user = Auth::user();

        $accessToken = $this->discordService->authorize($user, $code, $this->discordHelper->getDiscordUserCallbackUrl());
        $result = $this->discordService->connectDiscordUser($user, $accessToken);

        if($result) {
            $request->session()->flash('alert-success', 'Discord account has been linked.');
        } else {
            $request->session()->flash('alert-warning', 'Account could not be linked at this time. Please try again later.');
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

        if ($code == null && $discordUser !== null && $discordUser->expires_at > now()) {
            $accessToken = $this->discordService->refreshToken($discordUser, $this->discordHelper->getDiscordGuildCallbackUrl());
        } else {
            $accessToken = $this->discordService->authorize($user, $code, $this->discordHelper->getDiscordGuildCallbackUrl());
        }

        $result = $this->discordService->joinDiscordGuild($discordUser, $selectedDominion->realm, $accessToken);

        // TODO: Remove this?
        if($result) {
            $request->session()->flash('alert-success', 'Discord account has been invited to the realm server.');
        } else {
            $request->session()->flash('alert-warning', 'Account could not be added to the realm server at this time. Please try again later.');
        }

        return redirect()->away(sprintf(
            'https://discord.com/channels/%s/%s',
            $selectedDominion->realm->round->discord_guild_id,
            $selectedDominion->realm->discord_text_channel_id
        ));
    }
}
