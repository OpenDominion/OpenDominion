<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\DiscordUser
 * @property int $id
 * @property int $user_id
 * @property int $discord_user_id
 * @property string $username
 * @property int $discriminator
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DiscordUser extends AbstractModel
{
    protected $table = 'user_discord_users';
}
