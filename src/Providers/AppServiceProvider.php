<?php

namespace OpenDominion\Providers;

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
use OpenDominion\Contracts\Services\Dominion\Actions\ConstructActionService as ConstructActionServiceContract;
use OpenDominion\Contracts\Services\Dominion\Actions\DestroyActionService as DestroyActionServiceContract;
use OpenDominion\Contracts\Services\Dominion\Actions\ExploreActionService as ExploreActionServiceContract;
use OpenDominion\Contracts\Services\Dominion\Actions\Military\ChangeDraftRateActionService as ChangeDraftRateActionServiceContract;
use OpenDominion\Contracts\Services\Dominion\Actions\RezoneActionService as RezoneActionServiceContract;
use OpenDominion\Contracts\Services\Dominion\ProtectionService as ProtectionServiceContract;
use OpenDominion\Contracts\Services\Dominion\Queue\ConstructionQueueService as ConstructionQueueServiceContract;
use OpenDominion\Contracts\Services\Dominion\Queue\ExplorationQueueService as ExplorationQueueServiceContract;
use OpenDominion\Contracts\Services\Dominion\SelectorService as SelectorServiceContract;
use OpenDominion\Contracts\Services\RealmFinderService as RealmFinderServiceContract;
use OpenDominion\Services\AnalyticsService;
use OpenDominion\Services\AnalyticsService\Event;
use OpenDominion\Services\Dominion\Actions\ConstructActionService;
use OpenDominion\Services\Dominion\Actions\DestroyActionService;
use OpenDominion\Services\Dominion\Actions\ExploreActionService;
use OpenDominion\Services\Dominion\Actions\Military\ChangeDraftRateActionService;
use OpenDominion\Services\Dominion\Actions\RezoneActionService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\Queue\ConstructionQueueService;
use OpenDominion\Services\Dominion\Queue\ExplorationQueueService;
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
        $this->app->bind(NetworthCalculatorContract::class, NetworthCalculator::class);

        // Dominion Calculators
        $this->app->bind(BuildingCalculatorContract::class, BuildingCalculator::class);
        $this->app->bind(LandCalculatorContract::class, LandCalculator::class);
        $this->app->bind(MilitaryCalculatorContract::class, MilitaryCalculator::class);
        $this->app->bind(PopulationCalculatorContract::class, PopulationCalculator::class);
        $this->app->bind(ProductionCalculatorContract::class, ProductionCalculator::class);

        // Dominion Action Calculators
        $this->app->bind(ConstructionCalculatorContract::class, ConstructionCalculator::class);
        $this->app->bind(ExplorationCalculatorContract::class, ExplorationCalculator::class);
        $this->app->bind(RezoningCalculatorContract::class, RezoningCalculator::class);
        $this->app->bind(TrainingCalculatorContract::class, TrainingCalculator::class);
    }

    protected function bindServices()
    {
        // Services
        $this->app->bind(AnalyticsServiceContract::class, AnalyticsService::class);
        $this->app->bind(EventContract::class, Event::class);
        $this->app->bind(RealmFinderServiceContract::class, RealmFinderService::class);

        // Dominion Services
        $this->app->bind(ProtectionServiceContract::class, ProtectionService::class);
        $this->app->bind(SelectorServiceContract::class, SelectorService::class);

        // Dominion Action Services
        $this->app->bind(ConstructActionServiceContract::class, ConstructActionService::class);
        $this->app->bind(DestroyActionServiceContract::class, DestroyActionService::class);
        $this->app->bind(ExploreActionServiceContract::class, ExploreActionService::class);
        $this->app->bind(ChangeDraftRateActionServiceContract::class, ChangeDraftRateActionService::class);
        $this->app->bind(RezoneActionServiceContract::class, RezoneActionService::class);

        // Dominion Queue Services
        $this->app->bind(ConstructionQueueServiceContract::class, ConstructionQueueService::class);
        $this->app->bind(ExplorationQueueServiceContract::class, ExplorationQueueService::class);
    }
}
