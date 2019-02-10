<?php

namespace OpenDominion\Sharp\Auth;

use Code16\Sharp\Auth\SharpAuthenticationCheckHandler;
use Illuminate\Contracts\Auth\Authenticatable;

class SharpCheckHandler implements SharpAuthenticationCheckHandler
{
    /**
     * @param Authenticatable $user
     * @return bool
     */
    function check($user): bool
    {
        return in_array($user->email, [
            base64_decode('ZW1haWxAd2F2ZWhhY2submV0'), // WaveHack
        ], true);
    }
}
