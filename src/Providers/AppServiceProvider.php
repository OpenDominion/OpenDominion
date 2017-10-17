<?php

namespace OpenDominion\Providers;

use OpenDominion\Calculators\Dominion\Actions\BankingCalculator;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Services\Activity\ActivityService;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\CouncilService;
use OpenDominion\Services\Dominion\Actions\BankActionService;
use OpenDominion\Services\Dominion\Actions\ConstructActionService;
use OpenDominion\Services\Dominion\Actions\DailyBonusesActionService;
use OpenDominion\Services\Dominion\Actions\DestroyActionService;
use OpenDominion\Services\Dominion\Actions\ExploreActionService;
use OpenDominion\Services\Dominion\Actions\Military\ChangeDraftRateActionService;
use OpenDominion\Services\Dominion\Actions\Military\TrainActionService;
use OpenDominion\Services\Dominion\Actions\ReleaseActionService;
use OpenDominion\Services\Dominion\Actions\RezoneActionService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\Queue\ConstructionQueueService;
use OpenDominion\Services\Dominion\Queue\ExplorationQueueService;
use OpenDominion\Services\Dominion\Queue\TrainingQueueService;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Services\RealmFinderService;

class AppServiceProvider extends AbstractServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() === 'local') {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        }

        $this->app->alias('bugsnag.logger', \Illuminate\Contracts\Logging\Log::class);
        $this->app->alias('bugsnag.logger', \Psr\Log\LoggerInterface::class);

        $this->registerCalculators();
        $this->registerServices();
    }

    protected function registerCalculators()
    {
        // Generic Calculators
        $this->app->singleton(NetworthCalculator::class);

        // Dominion Calculators
        $this->app->singleton(BuildingCalculator::class);
        $this->app->singleton(LandCalculator::class);
        $this->app->singleton(MilitaryCalculator::class);
        $this->app->singleton(PopulationCalculator::class);
        $this->app->singleton(ProductionCalculator::class);
        $this->app->singleton(CasualtiesCalculator::class);

        // Dominion Action Calculators
        $this->app->singleton(BankingCalculator::class);
        $this->app->singleton(ConstructionCalculator::class);
        $this->app->singleton(ExplorationCalculator::class);
        $this->app->singleton(RezoningCalculator::class);
        $this->app->singleton(TrainingCalculator::class);
    }

    protected function registerServices()
    {
        // Services
        $this->app->singleton(ActivityService::class);
        $this->app->singleton(AnalyticsService::class);
        $this->app->singleton(CouncilService::class);
        $this->app->singleton(RealmFinderService::class);

        // Dominion Services
        $this->app->singleton(ProtectionService::class);
        $this->app->singleton(SelectorService::class);

        // Dominion Action Services
        $this->app->singleton(ChangeDraftRateActionService::class);
        $this->app->singleton(TrainActionService::class);
        $this->app->singleton(BankActionService::class);
        $this->app->singleton(ConstructActionService::class);
        $this->app->singleton(DailyBonusesActionService::class);
        $this->app->singleton(DestroyActionService::class);
        $this->app->singleton(ExploreActionService::class);
        $this->app->singleton(ReleaseActionService::class);
        $this->app->singleton(RezoneActionService::class);

        // Dominion Queue Services
        $this->app->singleton(ConstructionQueueService::class);
        $this->app->singleton(ExplorationQueueService::class);
        $this->app->singleton(TrainingQueueService::class);
    }
}
