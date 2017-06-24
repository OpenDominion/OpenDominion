<?php

namespace OpenDominion\Providers;

use Illuminate\Support\ServiceProvider;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Contracts\Calculators\Dominion\Actions\ConstructionCalculator as ConstructionCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\BuildingCalculator as BuildingCalculatorContract;

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

        // todo: refactor
//        $this->registerServices();
//        $this->registerHelpers();
//        $this->registerCalculators();

//        $this->initCalculatorDependencies();
        // todo: /refactor

        $this->registerContracts();
    }

//    protected function registerServices()
//    {
//        $this->app->singleton(DominionProtectionService::class, function ($app) {
//            return new DominionProtectionService;
//        });
//
//        $this->app->singleton(DominionQueueService::class, function ($app) {
//            return new DominionQueueService;
//        });
//
//        $this->app->singleton(DominionSelectorService::class, function ($app) {
//            return new DominionSelectorService(new DominionRepository($app));
//        });
//
//        $this->app->singleton(RealmFinderService::class, function ($app) {
//            return new RealmFinderService(new RealmRepository($app));
//        });
//    }
//
//    protected function registerHelpers()
//    {
//        $helperClasses = [
//            BuildingHelper::class,
//            LandHelper::class,
//        ];
//
//        foreach ($helperClasses as $helperClass) {
//            $this->app->singleton($helperClass, function ($app) use ($helperClass) {
//                return new $helperClass;
//            });
//        }
//    }

//    protected function registerCalculators()
//    {
//        // Generic calculators
//        $genericCalculatorClasses = [
////            NetworthCalculator::class,
//        ];
//
//        foreach ($genericCalculatorClasses as $class) {
//            $this->app->singleton($class, function ($app) use ($class) {
//                return new $class;
//            });
//        }
//
//        // Dominion calculators
//        $dominionCalculatorClasses = [
////            BuildingCalculator::class,
//            LandCalculator::class,
//            MilitaryCalculator::class,
//            PopulationCalculator::class,
//            ProductionCalculator::class,
//        ];
//
//        foreach ($dominionCalculatorClasses as $class) {
//            $this->app->instance($class, new $class);
//        }
//
//        $this->app->tag($dominionCalculatorClasses, 'dominionCalculators');
//
//        $allCalculatorClasses = array_merge($dominionCalculatorClasses, $genericCalculatorClasses);
//        $this->app->tag($allCalculatorClasses, 'initializableCalculators');
//    }
//
//    protected function initCalculatorDependencies()
//    {
//        foreach ($this->app->tagged('initializableCalculators') as $calculator) {
//            /** @var DependencyInitializableInterface $calculator */
//            $calculator->initDependencies();
//        }
//    }

    protected function registerContracts()
    {
        $this->app->bind(BuildingCalculatorContract::class,BuildingCalculator::class);
        $this->app->bind(ConstructionCalculatorContract::class, ConstructionCalculator::class);
    }
}
