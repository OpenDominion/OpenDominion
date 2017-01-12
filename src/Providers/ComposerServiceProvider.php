<?php

namespace OpenDominion\Providers;

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
            $env = getenv('APP_ENV');

            $shortHash = shell_exec('git log --pretty="%h" -n1 HEAD');
            $longHash = shell_exec('git log --pretty="%H" -n1 HEAD');

            $branch = shell_exec('git branch | grep \' * \'');
            $branch = str_replace('* ', '', trim($branch));

            $url = "https://github.com/WaveHack/OpenDominion/commit/{$longHash}";

            $view->with('version', "<strong>{$env}</strong> @ <a href=\"{$url}\" target=\"_blank\"><strong>#{$shortHash}</strong></a> ({$branch})");
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
