<?php

namespace OpenDominion\Models;

use Carbon\Carbon;
use Database\Factories\UserFactory;
use Gravatar;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use OpenDominion\Notifications\User\ResetPasswordNotification;
use Spatie\Permission\Traits\HasRoles;

/**
 * OpenDominion\Models\User
 *
 * @property int $id
 * @property string $email
 * @property string $password
 * @property string $display_name
 * @property string|null $avatar
 * @property string|null $remember_token
 * @property int $activated
 * @property string $activation_code
 * @property array|null $settings
 * @property \Illuminate\Support\Carbon|null $last_online
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Achievement[] $achievements
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\UserActivity[] $activities
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\UserIdentity[] $identities
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\UserOrigin[] $origins
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Dominion[] $dominions
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\UserFeedback[] $feedback
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Permission\Models\Permission[] $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Permission\Models\Role[] $roles
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\User permission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\User query()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\User role($roles)
 * @mixin \Eloquent
 */
class User extends AbstractModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, HasFactory, HasRoles, Notifiable;

    protected $casts = [
        'settings' => 'array',
    ];

    protected $dates = ['last_online', 'created_at', 'updated_at'];

    protected $hidden = ['password', 'remember_token', 'activation_code'];

    protected $with = ['roles'];

    // Relations

    public function achievements()
    {
        return $this->hasManyThrough(
            Achievement::class,
            UserAchievement::class,
            'user_id',
            'id',
            'id',
            'achievement_id'
        );
    }

    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }

    public function identities()
    {
        return $this->hasMany(UserIdentity::class);
    }

    public function origins()
    {
        return $this->hasMany(UserOrigin::class);
    }

    public function dominions()
    {
        return $this->hasMany(Dominion::class);
    }

    public function discordUser()
    {
        return $this->hasOne(DiscordUser::class);
    }

    public function feedback()
    {
        return $this->hasMany(UserFeedback::class, 'source_id');
    }

    /**
     * {@inheritdoc}
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    // Methods

    public function hasUpvotedUser(int $userId, int $roundId)
    {
        return !$this->feedback
            ->where('target_id', $userId)
            ->where('round_id', $roundId)
            ->where('endorsed', 1)
            ->isEmpty();
    }

    public function hasDownvotedUser(int $userId, int $roundId)
    {
        return !$this->feedback
            ->where('target_id', $userId)
            ->where('round_id', $roundId)
            ->where('endorsed', 0)
            ->isEmpty();
    }

    public function getAvatarUrl()
    {
        if ($this->avatar !== null) {
            return asset("storage/uploads/avatars/{$this->avatar}");
        }

        return Gravatar::src($this->email, 200);

    }

    public function getSetting(string $key)
    {
        if (!array_has($this->settings, $key)) {
            return null;
        }

        return array_get($this->settings, $key);

    }

    /**
     * Returns whether the user is online.
     *
     * A user is considered online if any last activity (like a pageview) occurred within the last 5 minutes.
     *
     * @return bool
     */
    public function isOnline(): bool
    {
        return (
            ($this->last_online !== null)
            && ($this->last_online > new Carbon('-5 minutes'))
        );
    }

    /**
     * Returns whether the user is inactive.
     *
     * A user is considered inactive the user hasn't been in the game for 72 hours (3 days).
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return (
            ($this->last_online !== null)
            && ($this->last_online < new Carbon('-72 hours'))
        );
    }

    /**
     * Returns whether the user has any staff roll associated with it.
     *
     * @return bool
     */
    public function isStaff(): bool
    {
        return $this->hasRole(['Developer', 'Administrator', 'Moderator']);
    }

    /**
     * Returns whether the user has a developer staff role.
     *
     * @return bool
     */
    public function isDeveloper(): bool
    {
        return $this->hasRole('Developer');
    }

    /**
     * Returns whether the user has an administrator staff role.
     *
     * @return bool
     */
    public function isAdministrator(): bool
    {
        return $this->hasRole('Administrator');
    }

    /**
     * Returns whether the user has a moderator staff role.
     *
     * @return bool
     */
    public function isModerator(): bool
    {
        return $this->hasRole('Moderator');
    }

    /**
     * Returns the highest priority staff role.
     */
    public function displayRoleHtml()
    {
        if ($this->isAdministrator())
        {
            return '<i>(Administrator)</i>';
        }

        if ($this->isDeveloper())
        {
            return '<i>(Developer)</i>';
        }

        if ($this->isModerator())
        {
            return '<i>(Moderator)</i>';
        }
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
