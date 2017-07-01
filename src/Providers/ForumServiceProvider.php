<?php

namespace OpenDominion\Providers;

use DevDojo\Chatter\Models\Category;
use DevDojo\Chatter\Models\Discussion;
use Gate;
use Illuminate\Support\ServiceProvider;
use OpenDominion\Contracts\Council\ForumServiceContract;
use OpenDominion\Policies\RealmAccessPolicy;
use OpenDominion\Services\ChatterForumService;

class ForumServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(\DevDojo\Chatter\ChatterServiceProvider::class);
        $this->app->bind(ForumServiceContract::class, ChatterForumService::class);

        // Apply the forum policy to the chatter categories.
        Gate::policy(Category::class, RealmAccessPolicy::class);
        Gate::policy(Discussion::class, RealmAccessPolicy::class);
    }
}
