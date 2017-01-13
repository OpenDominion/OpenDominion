<?php

namespace OpenDominion\Providers;

use Cache;
use Illuminate\Contracts\View\View;
use Illuminate\Support\ServiceProvider;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Services\DominionSelectorService;

class ComposerServiceProvider extends ServiceProvider
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

        view()->composer('partials.resources-overview', function (View $view) {
            $dominionSelectorService = app()->make(DominionSelectorService::class);
            $dominion = $dominionSelectorService->getUserSelectedDominion();

            $networthCalculator = app()->make(NetworthCalculator::class, [$dominion]);

            $view->with('networthCalculator', $networthCalculator);
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
