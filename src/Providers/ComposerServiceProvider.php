<?php

namespace OpenDominion\Providers;

use Cache;
use Illuminate\Contracts\View\View;
use OpenDominion\Contracts\Calculators\NetworthCalculator;

class ComposerServiceProvider extends AbstractServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function boot()
    {
        view()->composer('partials.main-footer', function (View $view) {
            $version = (Cache::has('version') ? Cache::get('version') : 'unknown');
            $view->with('version', $version);
        });

        // todo: do we need this here in this class?
        view()->composer('partials.resources-overview', function (View $view) {
            $networthCalculator = app(NetworthCalculator::class);
            $view->with('networthCalculator', $networthCalculator);
        });
    }
}
