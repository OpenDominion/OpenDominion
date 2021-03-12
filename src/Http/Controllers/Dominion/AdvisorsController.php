<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\OpsCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\MiscHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\RankingsHelper;
use OpenDominion\Helpers\ResourceHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Mappers\Dominion\InfoMapper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\InfoOpService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\RankingsService;
use OpenDominion\Services\GameEventService;

class AdvisorsController extends AbstractDominionController
{
    /**
     * @var GameEventService
     */
    private $gameEventService;
    /**
     * @var SpellCalculator
     */
    private $spellCalculator;
    /**
     * @var InfoMapper
     */
    private $infoMapper;

    public function __construct(
        GameEventService $gameEventService,
        SpellCalculator $spellCalculator,
        InfoMapper $infoMapper
    )
    {
        $this->gameEventService = $gameEventService;
        $this->spellCalculator = $spellCalculator;
        $this->infoMapper = $infoMapper;
    }

    public function getAdvisors()
    {
        return redirect()->route('dominion.advisors.production');
    }

    public function getAdvisorsOpCenter(Dominion $target = null)
    {
        try {
            $this->guardPackRealm($target);
        } catch (GameException $e) {
            return redirect()->back()
                ->withErrors([$e->getMessage()]);
        }

        $dominion = $target;
        if($dominion == null) {
            $dominion = $this->getSelectedDominion();
        }

        $latestInfoOps = collect([
            (object)[
                'type' => 'clear_sight',
                'data' => $this->infoMapper->mapStatus($dominion, false)
            ],
            (object)[
                'type' => 'revelation',
                'data' => $this->spellCalculator->getActiveSpells($dominion)
                    ->transform(function ($item, $key) {
                        return (array)$item;
                    })
                    ->toArray()
            ],
            (object)[
                'type' => 'castle_spy',
                'data' => $this->infoMapper->mapImprovements($dominion)
            ],
            (object)[
                'type' => 'barracks_spy',
                'data' => $this->infoMapper->mapMilitary($dominion, false)
            ],
            (object)[
                'type' => 'survey_dominion',
                'data' => $this->infoMapper->mapBuildings($dominion)
            ],
            (object)[
                'type' => 'land_spy',
                'data' => $this->infoMapper->mapLand($dominion)
            ],
            (object)[
                'type' => 'vision',
                'data' => [
                    'techs' => $this->infoMapper->mapTechs($dominion)
                ]
            ],
        ]);

        $latestInvasionEvents = $this->gameEventService->getLatestInvasionEventsForDominion($dominion, 10);

        return view('pages.dominion.op-center.show', [
            'buildingHelper' => app(BuildingHelper::class),
            'improvementHelper' => app(ImprovementHelper::class),
            'infoOpService' => app(InfoOpService::class),
            'landCalculator' => app(LandCalculator::class),
            'landHelper' => app(LandHelper::class),
            'miscHelper' => app(MiscHelper::class),
            'opsCalculator' => app(OpsCalculator::class),
            'raceHelper' => app(RaceHelper::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),
            'spellHelper' => app(SpellHelper::class),
            'techHelper' => app(TechHelper::class),
            'unitHelper' => app(UnitHelper::class),
            'dominion' => $dominion,
            'latestInfoOps' => $latestInfoOps,
            'latestInvasionEvents' => $latestInvasionEvents,
            'inRealm' => true,
            'targetDominion' => $target
        ]);
    }

    public function getAdvisorsProduction(Dominion $target = null)
    {
        try {
            $this->guardPackRealm($target);
        } catch (GameException $e) {
            return redirect()->back()
                ->withErrors([$e->getMessage()]);
        }

        return view('pages.dominion.advisors.production', [
            'militaryCalculator' => app(MilitaryCalculator::class),
            'populationCalculator' => app(PopulationCalculator::class),
            'productionCalculator' => app(ProductionCalculator::class),
            'infoMapper' => app(InfoMapper::class),
            'targetDominion' => $target
        ]);
    }

    public function getAdvisorsMilitary(Dominion $target = null)
    {
        try {
            $this->guardPackRealm($target);
        } catch (GameException $e) {
            return redirect()->back()
                ->withErrors([$e->getMessage()]);
        }

        return view('pages.dominion.advisors.military', [
            'casualtiesCalculator' => app(CasualtiesCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'queueService' => app(QueueService::class),
            'resourceHelper' => app(ResourceHelper::class),
            'unitHelper' => app(UnitHelper::class),
            'infoMapper' => app(InfoMapper::class),
            'targetDominion' => $target
        ]);
    }

    public function getAdvisorsMagic(Dominion $target = null)
    {
        try {
            $this->guardPackRealm($target);
        } catch (GameException $e) {
            return redirect()->back()
                ->withErrors([$e->getMessage()]);
        }

        return view('pages.dominion.advisors.magic', [
            'spellCalculator' => app(SpellCalculator::class),
            'spellHelper' => app(SpellHelper::class),
            'infoMapper' => app(InfoMapper::class),
            'targetDominion' => $target
        ]);
    }

    public function getAdvisorsRankings(Dominion $target = null)
    {
        try {
            $this->guardPackRealm($target);
        } catch (GameException $e) {
            return redirect()->back()
                ->withErrors([$e->getMessage()]);
        }

        return view('pages.dominion.advisors.rankings', [
            'rankingsHelper' => app(RankingsHelper::class),
            'rankingsService' => app(RankingsService::class),
            'targetDominion' => $target
        ]);
    }

    public function getAdvisorsStatistics(Dominion $target = null)
    {
        try {
            $this->guardPackRealm($target);
        } catch (GameException $e) {
            return redirect()->back()
                ->withErrors([$e->getMessage()]);
        }

        return view('pages.dominion.advisors.statistics', [
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'populationCalculator' => app(PopulationCalculator::class),
            'targetDominion' => $target
        ]);
    }

    private function guardPackRealm(?Dominion $target)
    {
        if($target == null) {
            return;
        }

        $dominion = $this->getSelectedDominion();

        if ($dominion->locked_at !== null) {
            throw new GameException('Locked dominions are not allowed to look at realm advisors.');
        }

        if ($dominion->realm_id !== $target->realm_id) {
            throw new GameException('You are only allowed to look at dominions in your realm.');
        }

        if ($dominion->id == $target->id) {
            return;
        }

        $realmAdvisors = $target->getSetting('realmadvisors');

        // Realm Advisor is explicitly enabled
        if ($realmAdvisors && array_key_exists($dominion->id, $realmAdvisors) && $realmAdvisors[$dominion->id] === true) {
            return;
        }

        // Realm Advisor is explicity disabled
        if ($realmAdvisors && array_key_exists($dominion->id, $realmAdvisors) && $realmAdvisors[$dominion->id] === false) {
            throw new GameException('This user has opted not to share their advisors.');
        }

        // Pack Advisor is enabled
        if ($target->user != null && $target->user->getSetting('packadvisors') !== false && ($dominion->pack_id != null && $dominion->pack_id == $target->pack_id)) {
            return;
        }

        throw new GameException('This user has opted not to share their advisors.');
    }
}
