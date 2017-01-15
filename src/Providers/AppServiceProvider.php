<?php

namespace OpenDominion\Providers;

use Illuminate\Support\ServiceProvider;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Repositories\DominionRepository;
use OpenDominion\Services\DominionSelectorService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() === 'local') {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }

        // Services
        $this->app->instance(DominionSelectorService::class, new DominionSelectorService(new DominionRepository($this->app)));

        // Helpers
        $helperClasses = [
            BuildingHelper::class,
            LandHelper::class,
        ];

        foreach ($helperClasses as $helperClass) {
            $this->app->instance($helperClass, new $helperClass);
        }

        // Calculators
        $calculatorClasses = [
            BuildingCalculator::class,
            LandCalculator::class,
            PopulationCalculator::class,
            ProductionCalculator::class,
        ];

        foreach ($calculatorClasses as $calculatorClass) {
            $this->app->instance($calculatorClass, new $calculatorClass);
        }

        $this->app->tag($calculatorClasses, 'calculators');

    }
}
