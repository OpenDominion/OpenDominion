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
            $branch = shell_exec('git branch | grep \' * \'');
            $branch = str_replace('* ', '', trim($branch));

            $shortHash = shell_exec('git log --pretty="%h" -n1 HEAD');
            $longHash = shell_exec('git log --pretty="%H" -n1 HEAD');

            $url = "https://github.com/WaveHack/OpenDominion/commit/{$longHash}";

            $view->with('version', "revision <a href=\"{$url}\" target=\"_blank\"><strong>#{$shortHash}</strong></a> on branch <strong>{$branch}</strong>");
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
