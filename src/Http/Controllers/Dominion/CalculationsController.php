<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Mappers\Dominion\InfoMapper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Models\Race;
use OpenDominion\Services\GameEventService;

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

    public function getIndex(Request $request)
    {
        $targetDominionId = $request->input('dominion');
        $targetDominion= null;
        $targetInfoOps = null;

        if ($targetDominionId !== null) {
            $dominion = $this->getSelectedDominion();
            $targetDominion = Dominion::find($targetDominionId);
            if ($targetDominion !== null) {

                if($this->inRealmAndSharesAdvisors($targetDominion)) {
                    $targetInfoOps = collect([
                        (object)[
                            'type' => 'clear_sight',
                            'data' => $this->infoMapper->mapStatus($targetDominion, false)
                        ],
                        (object)[
                            'type' => 'revelation',
                            'data' => $this->spellCalculator->getActiveSpells($targetDominion)
                                ->transform(function ($item, $key) {
                                    return (array)$item;
                                })
                                ->toArray()
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
                            if ($infoOp->type == 'barracks_spy') {
                                $hourTaken = $infoOp->created_at->startOfHour();
                                if ($hourTaken->diffInHours(now()) > 11) {
                                    return false;
                                }
                            }
                            return true;
                        })
                        ->keyBy('type');
                }

            }
        }

        return view('pages.dominion.calculations', [
            'landCalculator' => app(LandCalculator::class),
            'targetDominion' => $targetDominion,
            'targetInfoOps' => $targetInfoOps,
            'races' => Race::with(['units', 'units.perks'])->orderBy('name')->get(),
            'raceHelper' => app(RaceHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    private function inRealmAndSharesAdvisors(?Dominion $target): bool
    {
        $dominion = $this->getSelectedDominion();

        if ($dominion->id == $target->id) {
            return true;
        }

        if ($dominion->realm_id !== $target->realm_id) {
            return false;
        }

        if ($dominion->locked_at !== null) {
            return false;
        }

        $realmAdvisors = $target->getSetting('realmadvisors');

        // Realm Advisor is explicitly enabled
        if ($realmAdvisors && array_key_exists($dominion->id, $realmAdvisors) && $realmAdvisors[$dominion->id] === true) {
            return true;
        }

        // Realm Advisor is explicity disabled
        if ($realmAdvisors && array_key_exists($dominion->id, $realmAdvisors) && $realmAdvisors[$dominion->id] === false) {
            return false;
        }

        // Pack Advisor is enabled
        if ($target->user != null && $target->user->getSetting('packadvisors') !== false && ($dominion->pack_id != null && $dominion->pack_id == $target->pack_id)) {
            return true;
        }

        return false;
    }
}
