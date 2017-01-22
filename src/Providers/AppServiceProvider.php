<?php

namespace OpenDominion\Providers;

use Illuminate\Support\ServiceProvider;
use OpenDominion\Calculators\Dominion\AbstractDominionCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Repositories\DominionRepository;
use OpenDominion\Repositories\RealmRepository;
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
        $this->app->singleton(DominionQueueService::class, function ($app) {
            return new DominionQueueService;
        });

        $this->app->singleton(DominionSelectorService::class, function ($app) {
            return new DominionSelectorService(new DominionRepository($app));
        });

        $this->app->singleton(RealmFinderService::class, function ($app) {
            return new RealmFinderService(new RealmRepository($app));
        });
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
        // Generic calculators
        $genericCalculatorClasses = [
            NetworthCalculator::class,
        ];

        foreach ($genericCalculatorClasses as $class) {
            $this->app->singleton($class, function ($app) use ($class) {
                return new $class;
            });
        }

        // Dominion calculators
        $dominionCalculatorClasses = [
            BuildingCalculator::class,
            LandCalculator::class,
            MilitaryCalculator::class,
            PopulationCalculator::class,
            ProductionCalculator::class,
        ];

        foreach ($dominionCalculatorClasses as $calculatorClass) {
            $this->app->instance($calculatorClass, new $calculatorClass);
        }

        $allCalculatorClasses = ($genericCalculatorClasses + $dominionCalculatorClasses);

        $this->app->tag($allCalculatorClasses, 'calculators');
        $this->app->tag($dominionCalculatorClasses, 'initializableCalculators');
    }

    protected function initCalculators()
    {
        foreach ($this->app->tagged('initializableCalculators') as $calculator) {
            /** @var AbstractDominionCalculator $calculator */
            $calculator->initDependencies();
        }
    }
}
