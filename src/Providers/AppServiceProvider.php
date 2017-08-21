<?php

namespace OpenDominion\Providers;

use OpenDominion\Calculators\Dominion\Actions\BankingCalculator;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Contracts\Calculators\Dominion\Actions\BankingCalculator as BankingCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\Actions\ConstructionCalculator as ConstructionCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\Actions\ExplorationCalculator as ExplorationCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\Actions\RezoningCalculator as RezoningCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\Actions\TrainingCalculator as TrainingCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\BuildingCalculator as BuildingCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\LandCalculator as LandCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\MilitaryCalculator as MilitaryCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\PopulationCalculator as PopulationCalculatorContract;
use OpenDominion\Contracts\Calculators\Dominion\ProductionCalculator as ProductionCalculatorContract;
use OpenDominion\Contracts\Calculators\NetworthCalculator as NetworthCalculatorContract;
use OpenDominion\Contracts\Services\AnalyticsService as AnalyticsServiceContract;
use OpenDominion\Contracts\Services\AnalyticsService\Event as EventContract;
use OpenDominion\Contracts\Services\CouncilService as CouncilServiceContract;
use OpenDominion\Contracts\Services\Dominion\Actions\BankActionService as BankActionServiceContract;
use OpenDominion\Contracts\Services\Dominion\Actions\ConstructActionService as ConstructActionServiceContract;
use OpenDominion\Contracts\Services\Dominion\Actions\DestroyActionService as DestroyActionServiceContract;
use OpenDominion\Contracts\Services\Dominion\Actions\ExploreActionService as ExploreActionServiceContract;
use OpenDominion\Contracts\Services\Dominion\Actions\Military\ChangeDraftRateActionService as ChangeDraftRateActionServiceContract;
use OpenDominion\Contracts\Services\Dominion\Actions\Military\TrainActionService as TrainActionServiceContract;
use OpenDominion\Contracts\Services\Dominion\Actions\RezoneActionService as RezoneActionServiceContract;
use OpenDominion\Contracts\Services\Dominion\ProtectionService as ProtectionServiceContract;
use OpenDominion\Contracts\Services\Dominion\Queue\ConstructionQueueService as ConstructionQueueServiceContract;
use OpenDominion\Contracts\Services\Dominion\Queue\ExplorationQueueService as ExplorationQueueServiceContract;
use OpenDominion\Contracts\Services\Dominion\Queue\TrainingQueueService as TrainingQueueServiceContract;
use OpenDominion\Contracts\Services\Dominion\SelectorService as SelectorServiceContract;
use OpenDominion\Contracts\Services\RealmFinderService as RealmFinderServiceContract;
use OpenDominion\Services\AnalyticsService;
use OpenDominion\Services\AnalyticsService\Event;
use OpenDominion\Services\CouncilService;
use OpenDominion\Services\Dominion\Actions\BankActionService;
use OpenDominion\Services\Dominion\Actions\ConstructActionService;
use OpenDominion\Services\Dominion\Actions\DestroyActionService;
use OpenDominion\Services\Dominion\Actions\ExploreActionService;
use OpenDominion\Services\Dominion\Actions\Military\ChangeDraftRateActionService;
use OpenDominion\Services\Dominion\Actions\Military\TrainActionService;
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
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }

        $this->app->alias('bugsnag.logger', \Illuminate\Contracts\Logging\Log::class);
        $this->app->alias('bugsnag.logger', \Psr\Log\LoggerInterface::class);

        $this->bindContracts();
    }

    protected function bindContracts()
    {
        $this->bindCalculators();
        $this->bindServices();
    }

    protected function bindCalculators()
    {
        // Generic Calculators
        $this->app->singleton(NetworthCalculatorContract::class, NetworthCalculator::class);

        // Dominion Calculators
        $this->app->singleton(BuildingCalculatorContract::class, BuildingCalculator::class);
        $this->app->singleton(LandCalculatorContract::class, LandCalculator::class);
        $this->app->singleton(MilitaryCalculatorContract::class, MilitaryCalculator::class);
        $this->app->singleton(PopulationCalculatorContract::class, PopulationCalculator::class);
        $this->app->singleton(ProductionCalculatorContract::class, ProductionCalculator::class);

        // Dominion Action Calculators
        $this->app->singleton(BankingCalculatorContract::class, BankingCalculator::class);
        $this->app->singleton(ConstructionCalculatorContract::class, ConstructionCalculator::class);
        $this->app->singleton(ExplorationCalculatorContract::class, ExplorationCalculator::class);
        $this->app->singleton(RezoningCalculatorContract::class, RezoningCalculator::class);
        $this->app->singleton(TrainingCalculatorContract::class, TrainingCalculator::class);
    }

    protected function bindServices()
    {
        // Services
        $this->app->singleton(AnalyticsServiceContract::class, AnalyticsService::class);
        $this->app->singleton(CouncilServiceContract::class, CouncilService::class);
        $this->app->singleton(EventContract::class, Event::class);
        $this->app->singleton(RealmFinderServiceContract::class, RealmFinderService::class);

        // Dominion Services
        $this->app->singleton(ProtectionServiceContract::class, ProtectionService::class);
        $this->app->singleton(SelectorServiceContract::class, SelectorService::class);

        // Dominion Action Services
        $this->app->singleton(ChangeDraftRateActionServiceContract::class, ChangeDraftRateActionService::class);
        $this->app->singleton(TrainActionServiceContract::class, TrainActionService::class);
        $this->app->singleton(BankActionServiceContract::class, BankActionService::class);
        $this->app->singleton(ConstructActionServiceContract::class, ConstructActionService::class);
        $this->app->singleton(DestroyActionServiceContract::class, DestroyActionService::class);
        $this->app->singleton(ExploreActionServiceContract::class, ExploreActionService::class);
        $this->app->singleton(RezoneActionServiceContract::class, RezoneActionService::class);

        // Dominion Queue Services
        $this->app->singleton(ConstructionQueueServiceContract::class, ConstructionQueueService::class); // todo: singleton
        $this->app->singleton(ExplorationQueueServiceContract::class, ExplorationQueueService::class);
        $this->app->singleton(TrainingQueueServiceContract::class, TrainingQueueService::class);
    }
}
