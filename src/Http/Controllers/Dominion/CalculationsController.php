<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Mappers\Dominion\InfoMapper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Tech;
use OpenDominion\Services\Dominion\QueueService;

class CalculationsController extends AbstractDominionController
{
    /**
     * @var SpellCalculator
     */
    private $spellCalculator;

    /**
     * @var InfoMapper
     */
    private $infoMapper;

    public function __construct(
        SpellCalculator $spellCalculator,
        InfoMapper $infoMapper
    )
    {
        $this->spellCalculator = $spellCalculator;
        $this->infoMapper = $infoMapper;
    }

    public function getGeneral(Request $request)
    {
        $dominion = $this->getSelectedDominion();

        return view('pages.dominion.calculations.general', [
            'targetDominion' => $dominion,
            'landCalculator' => app(LandCalculator::class),
            'buildingHelper' => app(BuildingHelper::class),
            'improvementHelper' => app(ImprovementHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'techHelper' => app(TechHelper::class),
            'unitHelper' => app(UnitHelper::class),
            'queueService' => app(QueueService::class),
        ]);
    }

    public function postGeneral(Request $request)
    {
        $attrs = $request->input('attrs');
        $calc = $request->input('calc');
        $spells = $request->input('spells');
        $techs = $request->input('techs');

        $buildingHelper = app(BuildingHelper::class);
        $populationCalculator = app(PopulationCalculator::class);

        $dominion = new Dominion($attrs);
        $dominion->setRelation('realm', new Realm());
        $dominion->race->load(['units.perks']);
        if ($spells) {
            $spells = Spell::with('perks')->whereIn('key', array_keys($spells))->get();
            $dominion->setRelation('spells', $spells);
        }
        if ($techs) {
            $techs = Tech::with('perks')->whereIn('key', array_keys($techs))->get();
            $dominion->setRelation('techs', $techs);
        }

        $dominion->resource_food = 1;
        $dominion->{'land_' . $dominion->race->home_land_type} = $calc['barren'];
        foreach ($buildingHelper->getBuildingTypesByRace($dominion->race) as $landType => $buildings) {
            foreach ($buildings as $building) {
                if (isset($dominion->{'building_' . $building})) {
                    $dominion->{'land_' . $landType} += $dominion->{'building_' . $building};
                }
            }
        }

        $maxPopulation = $populationCalculator->getMaxPopulation($dominion);
        $militaryPopulation = $populationCalculator->getPopulationMilitary($dominion);
        $dominion->peasants = max(0, $maxPopulation - $militaryPopulation);

        return view('pages.dominion.calculations.general', [
            'targetDominion' => $dominion,
            'improvementCalculator' => app(ImprovementCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'populationCalculator' => $populationCalculator,
            'productionCalculator' => app(ProductionCalculator::class),
            'buildingHelper' => $buildingHelper,
            'improvementHelper' => app(ImprovementHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'techHelper' => app(TechHelper::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    public function getMilitary(Request $request)
    {
        $targetDominionId = $request->input('dominion');
        if ($targetDominionId == null) {
            $request->session()->flash('alert-danger', 'A target dominion is required for military calculators.');
            return redirect()->back();
        }

        $dominion = $this->getSelectedDominion();
        $targetDominion = Dominion::find($targetDominionId);
        $races = Race::with(['units', 'units.perks'])->where('playable', true)->orderBy('name')->get()->keyBy('id');
        if ($races->contains($targetDominion->race->id)) {
            $race = $races[$targetDominion->race->id];
        } else {
            $race = $targetDominion->race()->with(['units', 'units.perks'])->first();
        }

        if ($dominion->inRealmAndSharesAdvisors($targetDominion)) {
            $targetInfoOps = collect([
                (object)[
                    'type' => 'clear_sight',
                    'data' => $this->infoMapper->mapStatus($targetDominion, false)
                ],
                (object)[
                    'type' => 'revelation',
                    'data' => $this->spellCalculator->getActiveSpells($targetDominion)
                ],
                (object)[
                    'type' => 'castle_spy',
                    'data' => $this->infoMapper->mapImprovements($targetDominion)
                ],
                (object)[
                    'type' => 'barracks_spy',
                    'data' => $this->infoMapper->mapMilitary($targetDominion, false)
                ],
                (object)[
                    'type' => 'survey_dominion',
                    'data' => $this->infoMapper->mapBuildings($targetDominion)
                ],
                (object)[
                    'type' => 'land_spy',
                    'data' => $this->infoMapper->mapLand($targetDominion)
                ],
                (object)[
                    'type' => 'vision',
                    'data' => [
                        'techs' => $this->infoMapper->mapTechs($targetDominion)
                    ]
                ],
            ])->keyBy('type');
        } else {
            $targetInfoOps = InfoOp::query()
                ->where('target_dominion_id', $targetDominionId)
                ->where('source_realm_id', $dominion->realm->id)
                ->where('latest', true)
                ->get()
                ->filter(function ($infoOp) {
                    if ($infoOp->type == 'barracks_spy' && $infoOp->isInvalid()) {
                        return false;
                    }
                    return true;
                })
                ->keyBy('type');
        }

        return view('pages.dominion.calculations.military', [
            'landCalculator' => app(LandCalculator::class),
            'targetDominion' => $targetDominion,
            'targetInfoOps' => $targetInfoOps,
            'race' => $race,
            'races' => $races,
            'buildingHelper' => app(BuildingHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }
}
