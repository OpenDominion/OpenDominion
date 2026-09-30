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
     * @var array<int, array{name: string, description: string}> Text channels created for every realm
     */
    const TEXT_CHANNELS = [
        ['name' => 'general', 'description' => 'General discussion for your realm'],
        ['name' => 'top-op', 'description' => 'Tracking top OP for your realm'],
        ['name' => 'ops-request', 'description' => 'Info op requests for your realm'],
        ['name' => 'strategy-advice', 'description' => 'Strategy and advice for your realm'],
        ['name' => 'war-room', 'description' => 'Coordination of War operations for your realm'],
    ];

    /**
     * @var Client
     */
    private $client;

    /**
     * @var DiscordHelper
     */
    private $discordHelper;

    /**
     * DiscordService constructor.
     *
     * @param Client|null $client
     */
    public function __construct(Client|null $client = null)
    {
        $this->client = $client ?? new Client();
        $this->discordHelper = app(DiscordHelper::class);
    }

    public function authorize(User $user, string $code, string $callback): string
    {
        if (!config('app.discord_client_id')) {
            return '';
        }

        $client = $this->client;

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

        $client = $this->client;

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

        $client = $this->client;
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
            return false;
        }

        $client = $this->client;
        $botToken = $this->discordHelper->getBotToken();

        $memberResponse = $client->get(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/members/' . $discordUser->discord_user_id, [
            'http_errors' => false,
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"]
        ]);

        $result = json_decode($memberResponse->getBody()->getContents(), true);

        $isGuildMember = isset($result['roles']);
        $usesRoles = $this->discordHelper->usesRoles();

        if ($isGuildMember && $usesRoles) {
            $roleResponse = $client->patch(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/members/' . $discordUser->discord_user_id, [
                'verify' => false,
                'headers' => ['authorization' => "Bot $botToken"],
                'json' => [
                    'access_token' => $accessToken,
                    'roles' => array_merge($result['roles'], [$this->getDiscordRole($realm)])
                ]
            ]);

            $result = json_decode($roleResponse->getBody()->getContents(), true);
        } elseif (!$isGuildMember) {
            $joinResponse = $client->put(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/members/' . $discordUser->discord_user_id, [
                'verify' => false,
                'headers' => ['authorization' => "Bot $botToken"],
                'json' => [
                    'access_token' => $accessToken,
                    'roles' => $usesRoles ? [$this->getDiscordRole($realm)] : []
                ]
            ]);

            $result = json_decode($joinResponse->getBody()->getContents(), true);
        }

        if (!$usesRoles) {
            $this->grantRealmAccess($discordUser, $realm);
        }

        $generalResponse = $client->get(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/channels', [
            'http_errors' => false,
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"]
        ]);
        if ($generalResponse->getStatusCode() == 200) {
            $result = json_decode($generalResponse->getBody()->getContents(), true);
            $generalChannel = collect($result)
                ->where('name', 'general')
                ->where('parent_id', $realm->discord_category_id)
                ->first();
            if ($generalChannel) {
                $client->post(DiscordHelper::BASE_URL . '/channels/' . $generalChannel['id'] . '/messages', [
                    'verify' => false,
                    'headers' => ['authorization' => "Bot $botToken"],
                    'json' => [
                        'content' => '@' . $discordUser->username . ' (' . $discordUser->user->display_name . ') has joined the chat.'
                    ]
                ]);
            }
        }

        return true;
    }

    public function grantRealmAccess(DiscordUser $discordUser, Realm $realm): bool
    {
        if (!config('app.discord_client_id')) {
            return false;
        }

        $botToken = $this->discordHelper->getBotToken();

        $this->client->put(DiscordHelper::BASE_URL . '/channels/' . $this->getDiscordCategory($realm) . '/permissions/' . $discordUser->discord_user_id, [
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"],
            'json' => [
                'type' => 1, // member
                'allow' => $this->discordHelper->getPermissionsBitwise(),
                'deny' => '0'
            ]
        ]);

        return true;
    }

    /**
     * Revokes a user's access to a realm's channels.
     *
     * @param DiscordUser $discordUser
     * @param Realm $realm
     * @return bool
     */
    public function revokeRealmAccess(DiscordUser $discordUser, Realm $realm): bool
    {
        if (!config('app.discord_client_id') || $realm->discord_category_id === null) {
            return false;
        }

        $botToken = $this->discordHelper->getBotToken();

        $this->client->delete(DiscordHelper::BASE_URL . '/channels/' . $realm->discord_category_id . '/permissions/' . $discordUser->discord_user_id, [
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"]
        ]);

        return true;
    }

    /**
     * Returns the ID of a realm's Discord category, creating the realm's
     * channels when they do not exist yet.
     *
     * @param Realm $realm
     * @return string
     */
    public function getDiscordCategory(Realm $realm): string
    {
        if (!config('app.discord_client_id')) {
            return '';
        }

        if ($realm->discord_category_id !== null) {
            return $realm->discord_category_id;
        }

        if ($this->discordHelper->usesRoles()) {
            $this->createDiscordRole($realm);

            return $realm->discord_category_id;
        }

        return $this->createRealmChannels($realm);
    }

    /**
     * Creates a realm's category and channels without a role.
     *
     * Nothing is granted on creation: the guild's @everyone role has no
     * VIEW_CHANNEL permission, so the channels stay hidden until a member
     * overwrite is added to the category. The channels are created without
     * overwrites of their own, which keeps them synced to the category so that
     * later overwrites propagate to them.
     *
     * @param Realm $realm
     * @return string
     */
    public function createRealmChannels(Realm $realm): string
    {
        if (!config('app.discord_client_id')) {
            return '';
        }

        $client = $this->client;
        $botToken = $this->discordHelper->getBotToken();
        $realmLabel = $this->discordHelper->getRealmLabel($realm);

        $createRealmCategoryResponse = $client->post(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/channels', [
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"],
            'json' => [
                'name' => $realmLabel,
                'type' => 4
            ]
        ]);

        $result = json_decode($createRealmCategoryResponse->getBody()->getContents(), true);
        $realm->discord_category_id = $result['id'];

        foreach (static::TEXT_CHANNELS as $channel) {
            $client->post(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/channels', [
                'verify' => false,
                'headers' => ['authorization' => "Bot $botToken"],
                'json' => [
                    'name' => $channel['name'],
                    'type' => 0,
                    'topic' => $channel['description'],
                    'parent_id' => $realm->discord_category_id
                ]
            ]);
        }

        $client->post(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/channels', [
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"],
            'json' => [
                'name' => 'Voice Chat',
                'type' => 2,
                'parent_id' => $realm->discord_category_id
            ]
        ]);

        $realm->save();

        return $realm->discord_category_id;
    }

    public function getDiscordGuild(Round $round): string
    {
        if (!config('app.discord_client_id')) {
            return '';
        }

        if ($round->discord_guild_id !== null) {
            return $round->discord_guild_id;
        }

        return '';
    }

    public function createDiscordGuild(Round $round): string
    {
        if (!config('app.discord_client_id')) {
            return '';
        }

        $client = $this->client;
        $botToken = $this->discordHelper->getBotToken();

        $createGuildResponse = $client->post(DiscordHelper::BASE_URL . '/guilds', [
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"],
            'json' => [
                'name' => 'OpenDominion Realm Chat - Round ' . $round->number
            ]
        ]);

        $result = json_decode($createGuildResponse->getBody()->getContents(), true);
        $round->discord_guild_id = $result['id'];
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

        $client = $this->client;
        $botToken = $this->discordHelper->getBotToken();
        $realmLabel = $this->discordHelper->getRealmLabel($realm);

        $createRoleResponse = $client->post(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/roles', [
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"],
            'json' => [
                'name' => $realmLabel,
                'permissions' => '0'
            ]
        ]);

        $result = json_decode($createRoleResponse->getBody()->getContents(), true);
        $realm->discord_role_id = $result['id'];

        $createRealmCategoryResponse = $client->post(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/channels', [
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"],
            'json' => [
                'name' => $realmLabel,
                'type' => 4,
                'permission_overwrites' => [
                    [
                        'id' => $realm->discord_role_id,
                        'type' => 0,
                        'allow' => $this->discordHelper->getPermissionsBitwise()
                    ]
                ]
            ]
        ]);
        $result = json_decode($createRealmCategoryResponse->getBody()->getContents(), true);
        $realm->discord_category_id = $result['id'];

        foreach (static::TEXT_CHANNELS as $channel) {
            $createTextChannelResponse = $client->post(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/channels', [
                'verify' => false,
                'headers' => ['authorization' => "Bot $botToken"],
                'json' => [
                    'name' => $channel['name'],
                    'type' => 0,
                    'topic' => $channel['description'],
                    'permission_overwrites' => [
                        [
                            'id' => $realm->discord_role_id,
                            'type' => 0,
                            'allow' => $this->discordHelper->getPermissionsBitwise()
                        ]
                    ],
                    'parent_id' => $realm->discord_category_id
                ]
            ]);
            $result = json_decode($createTextChannelResponse->getBody()->getContents(), true);
        }

        $createVoiceChannelResponse = $client->post(DiscordHelper::BASE_URL . '/guilds/' . $realm->round->discord_guild_id . '/channels', [
            'verify' => false,
            'headers' => ['authorization' => "Bot $botToken"],
            'json' => [
                'name' => 'Voice Chat',
                'type' => 2,
                'permission_overwrites' => [
                    [
                        'id' => $realm->discord_role_id,
                        'type' => 0,
                        'allow' => $this->discordHelper->getPermissionsBitwise()
                    ]
                ],
                'parent_id' => $realm->discord_category_id
            ]
        ]);
        $result = json_decode($createVoiceChannelResponse->getBody()->getContents(), true);

        $realm->save();

        return $realm->discord_role_id;
    }
}
