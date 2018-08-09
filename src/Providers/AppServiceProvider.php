<?php

namespace OpenDominion\Providers;

use Illuminate\Pagination\Paginator;
use OpenDominion\Calculators\Dominion\Actions\BankingCalculator;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Services\Activity\ActivityService;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\CouncilService;
use OpenDominion\Services\Dominion\Actions\BankActionService;
use OpenDominion\Services\Dominion\Actions\ConstructActionService;
use OpenDominion\Services\Dominion\Actions\DailyBonusesActionService;
use OpenDominion\Services\Dominion\Actions\DestroyActionService;
use OpenDominion\Services\Dominion\Actions\EspionageActionService;
use OpenDominion\Services\Dominion\Actions\ExploreActionService;
use OpenDominion\Services\Dominion\Actions\ImproveActionService;
use OpenDominion\Services\Dominion\Actions\InvadeActionService;
use OpenDominion\Services\Dominion\Actions\Military\ChangeDraftRateActionService;
use OpenDominion\Services\Dominion\Actions\Military\TrainActionService;
use OpenDominion\Services\Dominion\Actions\ReleaseActionService;
use OpenDominion\Services\Dominion\Actions\RezoneActionService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\InfoOpService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\Queue\ConstructionQueueService;
use OpenDominion\Services\Dominion\Queue\ExplorationQueueService;
use OpenDominion\Services\Dominion\Queue\LandIncomingQueueService;
use OpenDominion\Services\Dominion\Queue\TrainingQueueService;
use OpenDominion\Services\Dominion\Queue\UnitsReturningQueueService;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Services\RealmFinderService;
use Schema;

class AppServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        Paginator::useBootstrapThree();
        Schema::defaultStringLength(191);
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        if ($this->app->environment() === 'local') {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        }

        $this->registerCalculators();
        $this->registerServices();
    }

    protected function registerCalculators()
    {
        $func = "singleton";
        if ($this->app->environment() === 'testing') {
            $func = "bind";
        }
        // Generic Calculators
        $this->app->$func(NetworthCalculator::class);

        // Dominion Calculators
        $this->app->$func(BuildingCalculator::class);
        $this->app->$func(CasualtiesCalculator::class);
        $this->app->$func(EspionageCalculator::class);
        $this->app->$func(ImprovementCalculator::class);
        $this->app->$func(LandCalculator::class);
        $this->app->$func(MilitaryCalculator::class);
        $this->app->$func(PopulationCalculator::class);
        $this->app->$func(ProductionCalculator::class);
        $this->app->$func(RangeCalculator::class);
        $this->app->$func(SpellCalculator::class);

        // Dominion Action Calculators
        $this->app->$func(BankingCalculator::class);
        $this->app->$func(ConstructionCalculator::class);
        $this->app->$func(ExplorationCalculator::class);
        $this->app->$func(RezoningCalculator::class);
        $this->app->$func(TrainingCalculator::class);
    }

    protected function registerServices()
    {
        $func = "singleton";
        if ($this->app->environment() === 'testing') {
            $func = "bind";
        }
        // Services
        $this->app->$func(ActivityService::class);
        $this->app->$func(AnalyticsService::class);
        $this->app->$func(CouncilService::class);
        $this->app->$func(NotificationService::class);
        $this->app->$func(RealmFinderService::class);

        // Dominion Services
        $this->app->$func(HistoryService::class);
        $this->app->$func(InfoOpService::class);
        $this->app->$func(ProtectionService::class);
        $this->app->$func(SelectorService::class);
        $this->app->$func(TickService::class);

        // Dominion Action Services
        $this->app->$func(ChangeDraftRateActionService::class);
        $this->app->$func(TrainActionService::class);
        $this->app->$func(BankActionService::class);
        $this->app->$func(ConstructActionService::class);
        $this->app->$func(DailyBonusesActionService::class);
        $this->app->$func(DestroyActionService::class);
        $this->app->$func(EspionageActionService::class);
        $this->app->$func(ExploreActionService::class);
        $this->app->$func(ImproveActionService::class);
        $this->app->$func(InvadeActionService::class);
        $this->app->$func(ReleaseActionService::class);
        $this->app->$func(RezoneActionService::class);
        $this->app->$func(SpellActionService::class);

        // Dominion Queue Services
        $this->app->$func(ConstructionQueueService::class);
        $this->app->$func(ExplorationQueueService::class);
        $this->app->$func(LandIncomingQueueService::class);
        $this->app->$func(TrainingQueueService::class);
        $this->app->$func(UnitsReturningQueueService::class);
    }
}
