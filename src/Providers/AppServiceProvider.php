<?php

namespace OpenDominion\Providers;

use Bugsnag;
use Cache;
use Illuminate\Pagination\Paginator;
use OpenDominion\Calculators\Dominion\Actions\BankingCalculator;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\Actions\TechCalculator;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Calculators\Dominion\API\DefenseCalculationService;
use OpenDominion\Calculators\Dominion\API\InvadeCalculationService;
use OpenDominion\Calculators\Dominion\API\OffenseCalculationService;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\OpsCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\WonderCalculator;
use OpenDominion\Services\Activity\ActivityService;
use OpenDominion\Services\CouncilService;
use OpenDominion\Services\DiscordService;
use OpenDominion\Services\Dominion\Actions\BankActionService;
use OpenDominion\Services\Dominion\Actions\ConstructActionService;
use OpenDominion\Services\Dominion\Actions\DailyBonusesActionService;
use OpenDominion\Services\Dominion\Actions\DestroyActionService;
use OpenDominion\Services\Dominion\Actions\EspionageActionService;
use OpenDominion\Services\Dominion\Actions\ExploreActionService;
use OpenDominion\Services\Dominion\Actions\GovernmentActionService;
use OpenDominion\Services\Dominion\Actions\GuardMembershipActionService;
use OpenDominion\Services\Dominion\Actions\HeroActionService;
use OpenDominion\Services\Dominion\Actions\ImproveActionService;
use OpenDominion\Services\Dominion\Actions\InvadeActionService;
use OpenDominion\Services\Dominion\Actions\Military\ChangeDraftRateActionService;
use OpenDominion\Services\Dominion\Actions\Military\TrainActionService;
use OpenDominion\Services\Dominion\Actions\ReleaseActionService;
use OpenDominion\Services\Dominion\Actions\RezoneActionService;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\Actions\TechActionService;
use OpenDominion\Services\Dominion\Actions\WonderActionService;
use OpenDominion\Services\Dominion\AIService;
use OpenDominion\Services\Dominion\AutomationService;
use OpenDominion\Services\Dominion\BountyService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\HeroBattleService;
use OpenDominion\Services\Dominion\HeroTournamentService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\InfoOpService;
use OpenDominion\Services\Dominion\InvadeService;
use OpenDominion\Services\Dominion\LogParserService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\RankingsService;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Services\ForumService;
use OpenDominion\Services\GameEventService;
use OpenDominion\Services\JournalService;
use OpenDominion\Services\MessageBoardService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Services\PackService;
use OpenDominion\Services\RealmFinderService;
use OpenDominion\Services\ValorService;
use OpenDominion\Services\WonderService;
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

        // Set Bugsnag app version
        if (($appVersion = Cache::get('version')) !== null) {
            Bugsnag::getConfig()->setAppVersion($appVersion);
        }
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
        // Generic Calculators
        $this->app->singleton(NetworthCalculator::class);
        $this->app->singleton(WonderCalculator::class);

        // Dominion Calculators
        $this->app->singleton(BuildingCalculator::class);
        $this->app->singleton(CasualtiesCalculator::class);
        $this->app->singleton(EspionageCalculator::class);
        $this->app->singleton(HeroCalculator::class);
        $this->app->singleton(ImprovementCalculator::class);
        $this->app->singleton(LandCalculator::class);
        $this->app->singleton(MilitaryCalculator::class);
        $this->app->singleton(OpsCalculator::class);
        $this->app->singleton(PopulationCalculator::class);
        $this->app->singleton(PrestigeCalculator::class);
        $this->app->singleton(ProductionCalculator::class);
        $this->app->singleton(RangeCalculator::class);
        $this->app->singleton(SpellCalculator::class);

        // Dominion Action Calculators
        $this->app->singleton(BankingCalculator::class);
        $this->app->singleton(ConstructionCalculator::class);
        $this->app->singleton(ExplorationCalculator::class);
        $this->app->singleton(RezoningCalculator::class);
        $this->app->singleton(TechCalculator::class);
        $this->app->singleton(TrainingCalculator::class);
    }

    protected function registerServices()
    {
        // Services
        $this->app->singleton(ActivityService::class);
        $this->app->singleton(CouncilService::class);
        $this->app->singleton(DiscordService::class);
        $this->app->singleton(JournalService::class);
        $this->app->singleton(ForumService::class);
        $this->app->singleton(GameEventService::class);
        $this->app->singleton(MessageBoardService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(PackService::class);
        $this->app->singleton(RealmFinderService::class);
        $this->app->singleton(ValorService::class);
        $this->app->singleton(WonderService::class);

        // Dominion Services
        $this->app->singleton(AIService::class);
        $this->app->singleton(AutomationService::class);
        $this->app->singleton(BountyService::class);
        $this->app->singleton(GovernmentService::class);
        $this->app->singleton(GuardMembershipService::class);
        $this->app->singleton(HeroBattleService::class);
        $this->app->singleton(HeroTournamentService::class);
        $this->app->singleton(HistoryService::class);
        $this->app->singleton(InfoOpService::class);
        $this->app->singleton(InvadeService::class);
        $this->app->singleton(LogParserService::class);
        $this->app->singleton(ProtectionService::class);
        $this->app->singleton(QueueService::class);
        $this->app->singleton(RankingsService::class);
        $this->app->singleton(SelectorService::class);
        $this->app->singleton(TickService::class);

        // Dominion Action Services
        $this->app->singleton(ChangeDraftRateActionService::class);
        $this->app->singleton(TrainActionService::class);
        $this->app->singleton(BankActionService::class);
        $this->app->singleton(ConstructActionService::class);
        $this->app->singleton(DailyBonusesActionService::class);
        $this->app->singleton(DestroyActionService::class);
        $this->app->singleton(EspionageActionService::class);
        $this->app->singleton(ExploreActionService::class);
        $this->app->singleton(GovernmentActionService::class);
        $this->app->singleton(GuardMembershipActionService::class);
        $this->app->singleton(HeroActionService::class);
        $this->app->singleton(ImproveActionService::class);
        $this->app->singleton(InvadeActionService::class);
        $this->app->singleton(ReleaseActionService::class);
        $this->app->singleton(RezoneActionService::class);
        $this->app->singleton(SpellActionService::class);
        $this->app->singleton(TechActionService::class);
        $this->app->singleton(WonderActionService::class);

        // Dominion API Services
        $this->app->singleton(DefenseCalculationService::class);
        $this->app->singleton(InvadeCalculationService::class);
        $this->app->singleton(OffenseCalculationService::class);
    }
}
