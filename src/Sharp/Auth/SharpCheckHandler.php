<?php

namespace OpenDominion\Sharp\Auth;

use Code16\Sharp\Auth\SharpAuthenticationCheckHandler;
use OpenDominion\Models\User;

class SharpCheckHandler implements SharpAuthenticationCheckHandler
{
    /**
     * @param User $user
     * @return bool
     */
    public function check($user): bool
    {
        return $user->isStaff();
    }
}
