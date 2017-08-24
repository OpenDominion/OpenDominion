<?php

namespace OpenDominion\Events;

use Illuminate\Queue\SerializesModels;
use OpenDominion\Models\User;

abstract class AbstractUserEvent
{
    use SerializesModels;

    /** @var User */
    public $user;

    /**
     * AbstractUserEvent constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
