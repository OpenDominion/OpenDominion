<?php


namespace OpenDominion\Helpers;


class DiscordHelper
{
    public function getDiscordConnectUrl(): string
    {
        $clientId = config('app.discord_client_id');
        return 'https://discord.com/api/oauth2/authorize?client_id=' . $clientId . '&redirect_uri=' . urlencode($this->getDiscordCallbackUrl()) . '&response_type=code&scope=email%20identify';
    }

    public function getDiscordCallbackUrl(): string
    {
        return request()->getSchemeAndHttpHost() . '/discordCallback';
    }
}