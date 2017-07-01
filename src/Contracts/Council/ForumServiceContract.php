<?php

namespace OpenDominion\Contracts\Council;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use OpenDominion\Models\Realm;
use OpenDominion\Models\User;

interface ForumServiceContract
{
    /**
     * Create a council forum for a realm.
     *
     * @param \OpenDominion\Models\Realm $realm
     *   The realm.
     * @return bool
     *   Boolean indicating success.
     */
    public function createForRealm(Realm $realm);

    /**
     * Create a forum.
     *
     * @param string $name
     *   The name of the forum.
     * @param null|string $color
     *   [Optional] color in "hex" format. e.g. '#030189'
     *
     * @return bool
     *   Boolean indicating success.
     */
    public function create($name, $color = null);

    /**
     * Determine if the user is allowed to view the model.
     *
     * @param User $user
     * @param Model $model
     *
     * @return bool
     */
    public function canView(User $user, Model $model);


    /**
     * Determine if the user is allowed to visit the route.
     *
     * @param User $user
     * @param Route $route
     *
     * @return bool
     */
    public function canVisit(User $user, Route $route);

}
