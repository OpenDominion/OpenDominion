<?php

namespace OpenDominion\Events;

use Illuminate\Queue\SerializesModels;
use OpenDominion\Models\User;

class UserLoginEvent
{
    use SerializesModels;

    /** @var User */
    public $user;

    /**
     * UserLoginEvent constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
