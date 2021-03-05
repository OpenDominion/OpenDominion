<?php

namespace OpenDominion\Helpers;

class DiscordHelper
{
    const BASE_URL = 'https://discord.com/api';
    const AUTH_SCOPES = 'email identify guilds.join';

    public function getClientId()
    {
        return config('app.discord_client_id');
    }

    public function getClientSecret()
    {
        return config('app.discord_client_secret');
    }

    public function getBotToken()
    {
        return config('app.discord_bot_token');
    }

    public function getDiscordConnectUrl(?string $callbackType): string
    {
        if ($callbackType == 'join') {
            $callback = $this->getDiscordGuildCallbackUrl();
        } else {
            $callback = $this->getDiscordUserCallbackUrl();
        }

        return sprintf("%s/oauth2/authorize?response_type=code&client_id=%s&scope=%s&redirect_uri=%s",
            DiscordHelper::BASE_URL,
            $this->getClientId(),
            urlencode(DiscordHelper::AUTH_SCOPES),
            urlencode($callback)
        );
    }

    public function getPermissionsBitwise(): string
    {
        return (
            0x00000400 | // VIEW_CHANNEL
            0x00000040 | // ADD_REACTIONS
            0x00000800 | // SEND_MESSAGES
            0x00004000 | // EMBED_LINKS
            0x00008000 | // ATTACH_FILES
            0x00010000 | // READ_MESSAGE_HISTORY
            0x00100000 | // CONNECT
            0x00200000 | // SPEAK
            0x02000000 | // USE_VAD
            0x04000000   // CHANGE_NICKNAME
        );
    }

    public function getDiscordUserCallbackUrl(): string
    {
        return request()->getSchemeAndHttpHost() . '/discord/link';
    }

    public function getDiscordGuildCallbackUrl(): string
    {
        return request()->getSchemeAndHttpHost() . '/discord/join';
    }
}
