<?php

namespace OpenDominion\Providers;

use Illuminate\Support\ServiceProvider;
use OpenDominion\Calculators\Dominion\AbstractDominionCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Repositories\DominionRepository;
use OpenDominion\Services\DominionQueueService;
use OpenDominion\Services\DominionSelectorService;
use OpenDominion\Services\RealmFinderService;

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

        $this->registerServices();
        $this->registerHelpers();
        $this->registerCalculators();

        $this->initCalculators();
    }

    protected function registerServices()
    {
        $this->app->instance(DominionSelectorService::class, new DominionSelectorService(new DominionRepository($this->app)));

        $serviceClasses = [
            DominionQueueService::class,
            RealmFinderService::class,
        ];

        foreach ($serviceClasses as $serviceClass) {
            $this->app->singleton($serviceClass, function ($app) use ($serviceClass) {
                return new $serviceClass;
            });
        }
    }

    protected function registerHelpers()
    {
        $helperClasses = [
            BuildingHelper::class,
            LandHelper::class,
        ];

        foreach ($helperClasses as $helperClass) {
            $this->app->singleton($helperClass, function ($app) use ($helperClass) {
                return new $helperClass;
            });
        }
    }

    protected function registerCalculators()
    {
        $calculatorClasses = [
            BuildingCalculator::class,
            LandCalculator::class,
            MilitaryCalculator::class,
            PopulationCalculator::class,
            ProductionCalculator::class,
        ];

        foreach ($calculatorClasses as $calculatorClass) {
            $this->app->instance($calculatorClass, new $calculatorClass);
        }

        $this->app->tag($calculatorClasses, 'calculators');
    }

    protected function initCalculators()
    {
        foreach ($this->app->tagged('calculators') as $calculator) {
            /** @var AbstractDominionCalculator $calculator */
            $calculator->initDependencies();
        }
    }
}
