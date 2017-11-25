<?php

namespace OpenDominion\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;

class User extends AbstractModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, Notifiable;

    protected $hidden = ['password', 'remember_token', 'activation_code'];

//    public function dominion(Round $round)
//    {
//        return $this->dominions()->where('round_id', $round->id)->get();
//    }

    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }

    public function dominions()
    {
        return $this->hasMany(Dominion::class);
    }

    public function getAvatarUrl()
    {
        if ($this->avatar !== null) {
            return asset("storage/uploads/avatars/{$this->avatar}");
        }

        return \Gravatar::src($this->email, 200);
    }

}
