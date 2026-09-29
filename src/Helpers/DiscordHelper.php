<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Realm;
use RuntimeException;

class DiscordHelper
{
    const BASE_URL = 'https://discord.com/api';
    const AUTH_SCOPES = 'email identify guilds.join';

    /**
     * @var array<int, string> Labels used in place of realm numbers on Discord
     */
    const REALM_LABELS = [
        'Fehu', 'Uruz', 'Thurisaz', 'Ansuz', 'Raido', 'Kenaz', 'Gebo', 'Wunjo',
        'Hagalaz', 'Naudiz', 'Isa', 'Jera', 'Eihwaz', 'Perthro', 'Algiz', 'Sowilo',
        'Tiwaz', 'Berkano', 'Ehwaz', 'Mannaz', 'Laguz', 'Ingwaz', 'Dagaz', 'Othala',
    ];

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

    public function getDiscordConnectUrl(string|null $callbackType): string
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

    /**
     * Returns the obfuscated label used for a realm's Discord role and channels.
     *
     * Realm membership is private in-game, but Discord exposes role and channel
     * names to every member of the guild, so a realm's number must never appear
     * on Discord. The label pool is permuted per round using APP_KEY as the key,
     * which keeps the mapping reproducible without storing it or deriving it
     * from anything visible in-game.
     *
     * @param Realm $realm
     * @return string
     * @throws RuntimeException
     */
    public function getRealmLabel(Realm $realm): string
    {
        $labels = collect(static::REALM_LABELS)
            ->sortBy(function (string $label) use ($realm): string {
                return hash_hmac('sha256', $realm->round_id . ':' . $label, config('app.key'));
            })
            ->values();

        if ($realm->number < 1 || $realm->number > $labels->count()) {
            throw new RuntimeException(
                sprintf('No Discord label available for realm number %d.', $realm->number)
            );
        }

        return $labels[$realm->number - 1];
    }

    /**
     * Whether realm channel access is granted with roles instead of member
     * permission overwrites.
     *
     * @return bool
     */
    public function usesRoles(): bool
    {
        return (bool)config('app.discord_use_roles');
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
