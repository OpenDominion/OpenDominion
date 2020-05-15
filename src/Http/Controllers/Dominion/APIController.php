<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\API\InvadeCalculationRequest;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\API\DefenseCalculationService;
use OpenDominion\Services\Dominion\API\InvadeCalculationService;
use OpenDominion\Services\Dominion\API\OffenseCalculationService;

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
        $units = array_fill(0, 5, 0);

        // Calculate units home and away
        if (isset($calc['draftees'])) {
            $units[0] = $calc['draftees'];
        } elseif (isset($calc['draftees_home'])) {
            $units[0] = (int) ($calc['draftees_home'] / 0.85);
        }
        foreach(range(1, 4) as $slot) {
            if (isset($calc["unit{$slot}"])) {
                $units[$slot] = $calc["unit{$slot}"];
                if (isset($calc["unit{$slot}_away"])) {
                    $unitsAway = (int) ($calc["unit{$slot}_away"] * 0.85);
                    $units[$slot] = max($units[$slot] - $unitsAway, 0);
                    if (isset($calc["unit{$slot}_home"])) {
                        $unitsHome = (int) ($calc["unit{$slot}_home"] / 0.85);
                        if ($unitsHome < $units[$slot]) {
                            $units[$slot] = $unitsHome;
                        }
                    }
                }
            } elseif (isset($calc["unit{$slot}_home"])) {
                $units[$slot] = (int) ($calc["unit{$slot}_home"] / 0.85);
            }
        }

        $dominion = new Dominion([
            'race_id' => $request->get('race'),
            'prestige' => isset($calc['prestige']) ? $calc['prestige'] : 250,
            'morale' => isset($calc['morale']) ? $calc['morale'] : 100,

            'military_draftees' => $units[0],
            'military_unit1' => $units[1],
            'military_unit2' => $units[2],
            'military_unit3' => $units[3],
            'military_unit4' => $units[4],

            'land_plain' => isset($calc['land']) ? $calc['land'] : 250,
            'land_mountain' => 0,
            'land_swamp' => 0,
            'land_cavern' => 0,
            'land_forest' => 0,
            'land_hill' => 0,
            'land_water' => 0,

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

    public function calculateOffense(InvadeCalculationRequest $request): array
    {
        $calc = $request->get('calc');
        $units = array_fill(0, 5, 0);

        // Check for target information
        $target = null;
        $landRatio = null;
        if (isset($calc['target_race'])) {
            $target = new Dominion(['race_id' => $calc['target_race']]);
        }
        if (isset($calc['target_land'])) {
            if (isset($calc['land']) && $calc['land'] > 0) {
                $land = $calc['land'];
            } else {
                $land = 1;
            }
            $landRatio = $calc['target_land'] / $land;
        }

        // Calculate unit totals
        foreach(range(1, 4) as $slot) {
            $units[$slot] = $calc["unit{$slot}"] + $calc["unit{$slot}_inc"];
        }

        $dominion = new Dominion([
            'race_id' => $request->get('race'),
            'prestige' => isset($calc['prestige']) ? $calc['prestige'] : 250,
            'morale' => isset($calc['morale']) ? $calc['morale'] : 100,

            'military_draftees' => 0,
            'military_unit1' => $units[1],
            'military_unit2' => $units[2],
            'military_unit3' => $units[3],
            'military_unit4' => $units[4],

            'land_plain' => isset($calc['land']) ? $calc['land'] : 250,
            'land_mountain' => 0,
            'land_swamp' => 0,
            'land_cavern' => 0,
            'land_forest' => 0,
            'land_hill' => 0,
            'land_water' => 0,

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
        $offenseCalculationService = app(OffenseCalculationService::class);

        try {
            $result = $offenseCalculationService->calculate(
                $dominion,
                $request->get('calc'),
                $target,
                $landRatio
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
