<?php

namespace OpenDominion\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;
use OpenDominion\Contracts\Council\ForumServiceContract;
use OpenDominion\Models\User;

class RealmAccessPolicy
{
    use HandlesAuthorization;
    protected $forum;

    /**
     * RealmAccessPolicy constructor.
     */
    public function __construct(ForumServiceContract $forum)
    {
        $this->forum = $forum;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \OpenDominion\Models\User $user
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return mixed
     */
    public function view(User $user, Model $model)
    {
        return $this->forum->canView($user, $model);
    }
}
