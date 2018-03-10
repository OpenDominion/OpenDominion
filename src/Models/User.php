<?php

namespace OpenDominion\Models;

use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use OpenDominion\Notifications\User\ResetPasswordNotification;
use Spatie\Permission\Traits\HasRoles;

class User extends AbstractModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, HasRoles, Notifiable;

    protected $hidden = ['password', 'remember_token', 'activation_code'];

    protected $dates = ['last_online', 'created_at', 'updated_at'];

//    public function dominion(Round $round)
//    {
//        return $this->dominions()->where('round_id', $round->id)->get();
//    }

    // Relations

    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }

    public function dominions()
    {
        return $this->hasMany(Dominion::class);
    }

    /**
     * {@inheritdoc}
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    // Methods

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
}
