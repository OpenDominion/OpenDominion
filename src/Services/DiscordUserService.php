<?php


namespace OpenDominion\Services;


use GuzzleHttp\Client;
use OpenDominion\Helpers\DiscordHelper;
use OpenDominion\Models\DiscordUser;
use OpenDominion\Models\User;

class DiscordUserService
{
    /**
     * @var DiscordHelper
     */
    private $discordHelper;

    public function __construct()
    {
        $this->discordHelper = app(DiscordHelper::class);
    }

    public function connectDiscordUser(User $user, string $code): bool
    {
        if($user->discordUser()->first()) {
            return false;
        }

        $clientId = config('app.discord_client_id');
        $clientSecret = config('app.discord_client_secret');
        $callbackUrl = $this->discordHelper->getDiscordCallbackUrl();

        $client = new Client();

        $tokenResponse = $client->post('https://discord.com/api/oauth2/token', [
            'verify' => false,
            'form_params' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'authorization_code',
                'code' => $code,
                'scope' => 'email identify',
                'redirect_uri' => $callbackUrl
            ]
        ]);

        $tokens = json_decode($tokenResponse->getBody()->getContents(), true);

        $accessToken = $tokens['access_token'];

        $userResponse = $client->get('https://discord.com/api/users/@me', [
            'verify' => false,
            'headers' => ['authorization' => "Bearer $accessToken"]
        ]);

        $discordResult = json_decode($userResponse->getBody()->getContents(), true);

        DiscordUser::create([
            'user_id' => $user->id,
            'discord_user_id' => $discordResult['id'],
            'username' => $discordResult['username'],
            'discriminator' => $discordResult['discriminator'],
            'email' => $discordResult['email'],
            'refresh_token' => $tokens['refresh_token']
        ]);

        return true;
    }
}