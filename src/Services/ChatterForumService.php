<?php

namespace OpenDominion\Services;

use Carbon\Carbon;
use Colors\RandomColor;
use DevDojo\Chatter\Models\Category;
use DevDojo\Chatter\Models\Discussion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use OpenDominion\Contracts\Council\ForumServiceContract;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\User;

class ChatterForumService implements ForumServiceContract
{

    /**
     * {@inheritdoc}
     */
    public function createForRealm(Realm $realm)
    {
        \DB::table('chatter_categories')->insert([
                'name' => $realm->name,
                'color' => RandomColor::one(['luminosity' => 'dark']),
                'slug' => str_slug($realm->name),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'realm_id' => $realm->id,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function create($name, $color = null)
    {
        \DB::table('chatter_categories')->insert([
                'name' => $name,
                'color' => $color ?: RandomColor::one(['luminosity' => 'dark']),
                'slug' => str_slug($name),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );
    }

    /**
     * Determine if the user is allowed to view the model.
     *
     * @param User $user
     * @param Model $model
     *
     * @return bool
     */
    public function canView(User $user, Model $model)
    {
        $access = true;
        if ($model instanceof Discussion) {
            $model = $model->category;
        }
        if ($model instanceof Category) {
            $realmId = $model->getAttribute('realm_id');
            if ($realmId > 0) {
                $access = Dominion::whereUserId($user->id)->where('realm_id', $realmId)->exists();
            }
        }
        return $access;
    }

    /**
     * Determine if the user is allowed to visit the route.
     *
     * @param User $user
     * @param Route $route
     *
     * @return bool
     */
    public function canVisit(User $user, Route $route)
    {
        $access = true;
        $categorySlug = $route->parameter('category');
        if ($categorySlug) {
            $category = Category::whereSlug($categorySlug)->first();
            $access = $this->canView($user, $category);
        }
        return $access;
    }
}
