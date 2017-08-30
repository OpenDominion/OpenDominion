<?php

namespace OpenDominion\Events;

use Illuminate\Queue\SerializesModels;
use OpenDominion\Models\User;

abstract class AbstractUserEvent
{
    use SerializesModels;

    /** @var User */
    protected $user;

    /**
     * AbstractUserEvent constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
