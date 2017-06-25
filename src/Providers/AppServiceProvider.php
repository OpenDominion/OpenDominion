<?php

namespace OpenDominion\Providers;

use Illuminate\Support\ServiceProvider;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Contracts\Calculators\Dominion\Actions\ConstructionCalculator as ConstructionCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\Actions\ExplorationCalculator as ExplorationCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\Actions\RezoningCalculator as RezoningCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\Actions\TrainingCalculator as TrainingCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\BuildingCalculator as BuildingCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\LandCalculator as LandCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\MilitaryCalculator as MilitaryCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\PopulationCalculator as PopulationCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\ProductionCalculator as ProductionCalculatorContract;

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
        // Dominion Calculators
        $this->app->bind(BuildingCalculatorContract::class,BuildingCalculator::class);
        $this->app->bind(LandCalculatorContract::class,LandCalculator::class);
        $this->app->bind(MilitaryCalculatorContract::class,MilitaryCalculator::class);
        $this->app->bind(PopulationCalculatorContract::class,PopulationCalculator::class);
        $this->app->bind(ProductionCalculatorContract::class,ProductionCalculator::class);

        // Dominion Action Calculators
        $this->app->bind(ConstructionCalculatorContract::class, ConstructionCalculator::class);
        $this->app->bind(ExplorationCalculatorContract::class, ExplorationCalculator::class);
        $this->app->bind(RezoningCalculatorContract::class, RezoningCalculator::class);
        $this->app->bind(TrainingCalculatorContract::class, TrainingCalculator::class);
    }
}
