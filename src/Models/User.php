<?php

namespace OpenDominion\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends AbstractModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, HasRoles, Notifiable;

    protected $hidden = ['password', 'remember_token', 'activation_code'];

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

    // Methods

    public function isStaff(): bool
    {
        return $this->hasRole(['Developer', 'Administrator', 'Moderator']);
    }

    public function isDeveloper(): bool
    {
        return $this->hasRole('Developer');
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole('Administrator');
    }

    public function isModerator(): bool
    {
        return $this->hasRole('Moderator');
    }
}
