<?php namespace OpenDominion\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['password', 'remember_token'];

    public function dominion()
    {
        return $this->hasOne('OpenDominion\Models\Dominion');
    }
}
