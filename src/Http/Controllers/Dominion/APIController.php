<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\API\InvadeCalculationRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\API\DefenseCalculationService;
use OpenDominion\Services\Dominion\API\InvadeCalculationService;

class APIController extends AbstractDominionController
{
    public function calculateInvasion(InvadeCalculationRequest $request): array
    {
        $dominion = $this->getSelectedDominion();
        $invadeCalculationService = app(InvadeCalculationService::class);

        try {
            $result = $invadeCalculationService->calculate(
                $dominion,
                Dominion::find($request->get('target_dominion')),
                $request->get('unit'),
                $request->get('calc')
            );
        } catch (GameException $e) {
            return [
                'result' => 'error',
                'errors' => [$e->getMessage()]
            ];
        }

        return $result;
    }

    public function calculateDefense(InvadeCalculationRequest $request): array
    {
        $calc = $request->get('calc');

        $dominion = new Dominion([
            'race_id' => $request->get('race'),
            'prestige' => isset($calc['prestige']) ? $calc['prestige'] : 250,
            'morale' => isset($calc['morale']) ? $calc['morale'] : 100,

            'military_draftees' => isset($calc['draftees']) ? $calc['draftees'] : 0,
            'military_unit1' => isset($calc['unit1']) ? $calc['unit1'] : 0,
            'military_unit2' => isset($calc['unit2']) ? $calc['unit2'] : 0,
            'military_unit3' => isset($calc['unit3']) ? $calc['unit3'] : 0,
            'military_unit4' => isset($calc['unit4']) ? $calc['unit4'] : 0,

            'land_plain' => 110,
            'land_mountain' => 20,
            'land_swamp' => 20,
            'land_cavern' => 20,
            'land_forest' => 40,
            'land_hill' => 20,
            'land_water' => 20,

            'building_home' => 0,
            'building_alchemy' => 0,
            'building_farm' => 0,
            'building_smithy' => 0,
            'building_masonry' => 0,
            'building_ore_mine' => 0,
            'building_gryphon_nest' => 0,
            'building_tower' => 0,
            'building_wizard_guild' => 0,
            'building_temple' => 0,
            'building_diamond_mine' => 0,
            'building_school' => 0,
            'building_lumberyard' => 0,
            'building_forest_haven' => 0,
            'building_factory' => 0,
            'building_guard_tower' => 0,
            'building_shrine' => 0,
            'building_barracks' => 0,
            'building_dock' => 0,
        ]);
        $defenseCalculationService = app(DefenseCalculationService::class);

        try {
            $result = $defenseCalculationService->calculate(
                $dominion,
                $request->get('calc')
            );
        } catch (GameException $e) {
            return [
                'result' => 'error',
                'errors' => [$e->getMessage()]
            ];
        }

        return $result;
    }
}
