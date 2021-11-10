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

        return sprintf(
            '%s/oauth2/authorize?response_type=code&client_id=%s&scope=%s&redirect_uri=%s',
            DiscordHelper::BASE_URL,
            $this->getClientId(),
            urlencode(DiscordHelper::AUTH_SCOPES),
            urlencode($callback)
        );
    }

    public function getPermissionsBitwise(): string
    {
        return (
            0x0000000040 | // ADD_REACTIONS
            0x0000000200 | // STREAM
            0x0000000400 | // VIEW_CHANNEL
            0x0000000800 | // SEND_MESSAGES
            0x0000004000 | // EMBED_LINKS
            0x0000008000 | // ATTACH_FILES
            0x0000010000 | // READ_MESSAGE_HISTORY
            0x0000020000 | // MENTION_EVERYONE
            0x0000040000 | // USE_EXTERNAL_EMOJIS
            0x0000100000 | // CONNECT
            0x0000200000 | // SPEAK
            0x0002000000 | // USE_VAD
            0x0004000000 | // CHANGE_NICKNAME
            0x2000000000   // USE_EXTERNAL_STICKERS
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
