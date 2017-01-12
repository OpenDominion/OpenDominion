<?php

namespace OpenDominion\Providers;

use Illuminate\Support\ServiceProvider;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function boot()
    {
        view()->composer('partials.main-footer', function ($view) {
            $branch = shell_exec('git branch | grep \' * \'');
            $branch = str_replace('* ', '', trim($branch));

            $hash = shell_exec('git log --pretty="%h" -n1 HEAD');

            $view->with('version', "#{$hash} ({$branch})");
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
