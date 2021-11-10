<?php

namespace OpenDominion\Services;

use GuzzleHttp\Client;
use OpenDominion\Helpers\DiscordHelper;
use OpenDominion\Models\DiscordUser;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;

class DiscordService
{
    /**
     * @var DiscordHelper
     */
    private $discordHelper;

    public function __construct()
    {
        $this->discordHelper = app(DiscordHelper::class);
    }

    public function authorize(User $user, string $code, string $callback): string
    {
        if (!config('app.discord_client_id')) {
            return '';
        }

        $client = new Client();

        $tokenResponse = $client->post(DiscordHelper::BASE_URL . '/oauth2/token', [
            'verify' => false,
            'form_params' => [
                'client_id' => $this->discordHelper->getClientId(),
                'client_secret' => $this->discordHelper->getClientSecret(),
                'grant_type' => 'authorization_code',
                'code' => $code,
                'scope' => DiscordHelper::AUTH_SCOPES,
                'redirect_uri' => $callback
            ]
        ]);

        $result = json_decode($tokenResponse->getBody()->getContents(), true);

        $discordUser = $user->discordUser()->first();
        if ($discordUser == null) {
            $this->createDiscordUser($user, $result);
        } else {
            $discordUser->update([
                'refresh_token' => $result['refresh_token'],
                'expires_at' => now()->addSeconds($result['expires_in'])
            ]);
            $discordUser->save();
        }

        return $result['access_token'];
    }

    public function refreshToken(DiscordUser $discordUser, string $callback): string
    {
        if (!config('app.discord_client_id')) {
            return '';
        }

        $client = new Client();

        $tokenResponse = $client->post(DiscordHelper::BASE_URL . '/oauth2/token', [
            'verify' => false,
            'form_params' => [
                'client_id' => $this->discordHelper->getClientId(),
                'client_secret' => $this->discordHelper->getClientSecret(),
                'grant_type' => 'refresh_token',
                'refresh_token' => $discordUser->refresh_token,
                'scope' => DiscordHelper::AUTH_SCOPES,
                'redirect_uri' => $callback
            ]
        ]);

        $result = json_decode($tokenResponse->getBody()->getContents(), true);

        // Update refresh token
        $discordUser->refresh_token = $result['refresh_token'];
        $discordUser->expires_at = now()->addSeconds($result['expires_in']);
        $discordUser->save();

        return $result['access_token'];
    }

    public function createDiscordUser(User $user, array $authResult): DiscordUser
    {
        if (!config('app.discord_client_id')) {
            return null;
        }

        $client = new Client();
        $accessToken = $authResult['access_token'];

        $userResponse = $client->get(DiscordHelper::BASE_URL . '/users/@me', [
            'verify' => false,
            'headers' => ['authorization' => "Bearer $accessToken"]
        ]);

        $result = json_decode($userResponse->getBody()->getContents(), true);

        $discordUserData = [
            'user_id' => $user->id,
            'discord_user_id' => $result['id'],
            'username' => $result['username'],
            'discriminator' => $result['discriminator'],
            'email' => $result['email'],
            'refresh_token' => $authResult['refresh_token'],
            'expires_at' => now()->addSeconds($authResult['expires_in'])
        ];

        return DiscordUser::create($discordUserData);
    }

    public function joinDiscordGuild(DiscordUser $discordUser, Realm $realm, string $accessToken): bool
    {
        if (!config('app.discord_client_id')) {
            return '';
        }

        $client = new Client();
        $botToken = $this->discordHelper->getBotToken();

        $memberResponse = $client->get(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/members/' . $discordUser->discord_user_id, [
            'http_errors' => false,
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"]
        ]);

        $result = json_decode($memberResponse->getBody()->getContents(), true);

        if (isset($result['roles'])) {
            $roleResponse = $client->patch(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/members/' . $discordUser->discord_user_id, [
                'verify' => false,
                'headers' => ['authorization' => "Bot $botToken"],
                'json' => [
                    'access_token' => $accessToken,
                    'roles' => array_merge($result['roles'], [$this->getDiscordRole($realm)])
                ]
            ]);

            $result = json_decode($roleResponse->getBody()->getContents(), true);
        } else {
            $joinResponse = $client->put(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/members/' . $discordUser->discord_user_id, [
                'verify' => false,
                'headers' => ['authorization' => "Bot $botToken"],
                'json' => [
                    'access_token' => $accessToken,
                    'roles' => [
                        $this->getDiscordRole($realm)
                    ]
                ]
            ]);

            $result = json_decode($joinResponse->getBody()->getContents(), true);
        }

        return true;
    }

    public function getDiscordGuild(Round $round): string
    {
        if (!config('app.discord_client_id')) {
            return '';
        }

        if ($round->discord_guild_id !== null) {
            return $round->discord_guild_id;
        }

        return $this->createDiscordGuild($round);
    }

    public function createDiscordGuild(Round $round): string
    {
        if (!config('app.discord_client_id')) {
            return '';
        }

        $client = new Client();
        $botToken = $this->discordHelper->getBotToken();

        $createGuildResponse = $client->post(DiscordHelper::BASE_URL . '/guilds', [
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"],
            'json' => [
                'name' => 'OpenDominion Realm Chat - Round ' . $round->number,
                'channels' => [
                    [
                        'name' => 'Text Channels',
                        'type' => 4
                    ],
                    [
                        'name' => 'Voice Channels',
                        'type' => 4
                    ]
                ]
            ]
        ]);

        $result = json_decode($createGuildResponse->getBody()->getContents(), true);
        $round->discord_guild_id = $result['id'];

        $getChannelsResponse = $client->get(DiscordHelper::BASE_URL . '/guilds/' . $round->discord_guild_id . '/channels', [
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"]
        ]);

        $result = json_decode($getChannelsResponse->getBody()->getContents(), true);
        $round->discord_text_category_channel_id = $result[0]['id'];
        $round->discord_voice_category_channel_id = $result[1]['id'];

        $round->save();

        $disablePermissionsResponse = $client->patch(DiscordHelper::BASE_URL . '/guilds/' . $round->discord_guild_id . '/roles/' . $round->discord_guild_id, [
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"],
            'json' => [
                'permissions' => '67108864' // CHANGE_NICKNAME
            ]
        ]);

        $result = json_decode($disablePermissionsResponse->getBody()->getContents(), true);

        return $round->discord_guild_id;
    }

    public function getDiscordRole(Realm $realm): string
    {
        if (!config('app.discord_client_id')) {
            return '';
        }

        if ($realm->discord_role_id !== null) {
            return $realm->discord_role_id;
        }

        return $this->createDiscordRole($realm);
    }

    public function createDiscordRole(Realm $realm): string
    {
        if (!config('app.discord_client_id')) {
            return '';
        }

        $client = new Client();
        $botToken = $this->discordHelper->getBotToken();

        $createRoleResponse = $client->post(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/roles', [
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"],
            'json' => [
                'name' => 'Realm ' . $realm->number,
                'permissions' => '0'
            ]
        ]);

        $result = json_decode($createRoleResponse->getBody()->getContents(), true);
        $realm->discord_role_id = $result['id'];

        $createTextChannelResponse = $client->post(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/channels', [
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"],
            'json' => [
                'name' => 'realm-' . $realm->number,
                'type' => 0,
                'topic' => 'General discussion for Realm ' . $realm->number,
                'permission_overwrites' => [
                    [
                        'id' => $realm->discord_role_id,
                        'type' => 0,
                        'allow' => $this->discordHelper->getPermissionsBitwise()
                    ]
                ],
                'parent_id' => $realm->round->discord_text_category_channel_id
            ]
        ]);

        $result = json_decode($createTextChannelResponse->getBody()->getContents(), true);

        $createOpsChannelResponse = $client->post(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/channels', [
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"],
            'json' => [
                'name' => 'realm-' . $realm->number . '-ops',
                'type' => 0,
                'topic' => 'Tracking top OP for Realm ' . $realm->number,
                'permission_overwrites' => [
                    [
                        'id' => $realm->discord_role_id,
                        'type' => 0,
                        'allow' => $this->discordHelper->getPermissionsBitwise()
                    ]
                ],
                'parent_id' => $realm->round->discord_text_category_channel_id
            ]
        ]);

        $result = json_decode($createOpsChannelResponse->getBody()->getContents(), true);

        $createVoiceChannelResponse = $client->post(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/channels', [
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"],
            'json' => [
                'name' => 'realm-' . $realm->number,
                'type' => 2,
                'topic' => 'Voice channel for Realm ' . $realm->number,
                'permission_overwrites' => [
                    [
                        'id' => $realm->discord_role_id,
                        'type' => 0,
                        'allow' => $this->discordHelper->getPermissionsBitwise()
                    ]
                ],
                'parent_id' => $realm->round->discord_voice_category_channel_id
            ]
        ]);

        $result = json_decode($createVoiceChannelResponse->getBody()->getContents(), true);

        $realm->save();

        return $realm->discord_role_id;
    }
}
