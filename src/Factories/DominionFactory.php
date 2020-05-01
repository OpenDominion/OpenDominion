<?php

namespace OpenDominion\Factories;

use DB;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;

class DominionFactory
{
    /**
     * Creates and returns a new Dominion instance.
     *
     * @param User $user
     * @param Realm $realm
     * @param Race $race
     * @param string $rulerName
     * @param string $dominionName
     * @param Pack|null $pack
     * @return Dominion
     * @throws GameException
     */
    public function create(
        User $user,
        Realm $realm,
        Race $race,
        string $rulerName,
        string $dominionName,
        ?Pack $pack = null
    ): Dominion {
        $this->guardAgainstMultipleDominionsInARound($user, $realm->round);
        $this->guardAgainstMismatchedAlignments($race, $realm, $realm->round);

        $startingBuildings = $this->getStartingBuildings();

        $startingLand = $this->getStartingLand(
            $race,
            $this->getStartingBarrenLand(),
            $startingBuildings
        );

        $startingAttributes = $this->getStartingAttributes($realm->round);

        return Dominion::create([
            'user_id' => $user->id,
            'round_id' => $realm->round->id,
            'realm_id' => $realm->id,
            'race_id' => $race->id,
            'pack_id' => $pack->id ?? null,

            'ruler_name' => $rulerName,
            'name' => $dominionName,
            'prestige' => 250,

            'peasants' => $startingAttributes['peasants'],
            'peasants_last_hour' => 0,

            'draft_rate' => 10,
            'morale' => 100,
            'spy_strength' => 100,
            'wizard_strength' => 100,

            'resource_platinum' => $startingAttributes['resource_platinum'],
            'resource_food' => $startingAttributes['resource_food'],
            'resource_lumber' => $startingAttributes['resource_lumber'],
            'resource_mana' => $startingAttributes['resource_mana'],
            'resource_ore' => $startingAttributes['resource_ore'],
            'resource_gems' => $startingAttributes['resource_gems'],
            'resource_tech' => 0,
            'resource_boats' => 0,

            'improvement_science' => 0,
            'improvement_keep' => 0,
            'improvement_towers' => 0,
            'improvement_forges' => 0,
            'improvement_walls' => 0,
            'improvement_harbor' => 0,

            'military_draftees' => $startingAttributes['military_draftees'],
            'military_unit1' => 0,
            'military_unit2' => $startingAttributes['military_unit2'],
            'military_unit3' => 0,
            'military_unit4' => 0,
            'military_spies' => 25,
            'military_wizards' => 25,
            'military_archmages' => 0,

            'land_plain' => $startingLand['land_plain'],
            'land_mountain' => $startingLand['land_mountain'],
            'land_swamp' => $startingLand['land_swamp'],
            'land_cavern' => $startingLand['land_cavern'],
            'land_forest' => $startingLand['land_forest'],
            'land_hill' => $startingLand['land_hill'],
            'land_water' => $startingLand['land_water'],

            'building_home' => $startingBuildings['building_home'],
            'building_alchemy' => $startingBuildings['building_alchemy'],
            'building_farm' => $startingBuildings['building_farm'],
            'building_smithy' => 0,
            'building_masonry' => 0,
            'building_ore_mine' => 0,
            'building_gryphon_nest' => 0,
            'building_tower' => 0,
            'building_wizard_guild' => 0,
            'building_temple' => 0,
            'building_diamond_mine' => 0,
            'building_school' => 0,
            'building_lumberyard' => $startingBuildings['building_lumberyard'],
            'building_forest_haven' => 0,
            'building_factory' => 0,
            'building_guard_tower' => 0,
            'building_shrine' => 0,
            'building_barracks' => 0,
            'building_dock' => 0,

            'protection_ticks_remaining' => $startingAttributes['protection_ticks_remaining'],
        ]);
    }

    /**
     * Resets a Dominion to an equivalent state as a new registration.
     *
     * @param  Dominion $dominion
     * @throws GameException
     */
    public function restart(Dominion $dominion, Race $race): void
    {
        // Reset Queues
        DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->delete();

        // Reset Spells
        DB::table('active_spells')
            ->where('dominion_id', $dominion->id)
            ->delete();

        // Reset Techs
        DB::table('dominion_techs')
            ->where('dominion_id', $dominion->id)
            ->delete();

        // Reset Notifications
        DB::table('notifications')
            ->where('notifiable_type', Dominion::class)
            ->where('notifiable_id', $dominion->id)
            ->delete();

        // Reset starting buildings
        $startingBuildings = $this->getStartingBuildings();
        foreach ($startingBuildings as $building_type => $value) {
            $dominion->{$building_type} = $value;
        }

        // Reset starting land
        $startingLand = $this->getStartingLand($race, $this->getStartingBarrenLand(), $startingBuildings);
        foreach ($startingLand as $land_type => $value) {
            $dominion->{$land_type} = $value;
        }

        // Reset other starting attributes
        $startingAttributes = $this->getStartingAttributes($dominion->round);
        foreach ($startingAttributes as $attribute => $value) {
            $dominion->{$attribute} = $value;
        }

        // Reset statistics
        $modelAttributes = $dominion->getAttributes();
        foreach ($modelAttributes as $attr => $value) {
            if (substr_compare($attr, 'stat_', 0, 5) === 0) {
                $dominion->{$attr} = 0;
            }
        }

        $dominion->race_id = $race->id;
        $dominion->created_at = now();
        $dominion->save([
            'event' => \OpenDominion\Services\Dominion\HistoryService::EVENT_ACTION_RESTART
        ]);

        // Reset Queues - duplicate to prevent race condition
        DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->delete();
    }

    /**
     * @param User $user
     * @param Round $round
     * @throws GameException
     */
    protected function guardAgainstMultipleDominionsInARound(User $user, Round $round): void
    {
        $dominionCount = Dominion::query()
            ->where([
                'user_id' => $user->id,
                'round_id' => $round->id,
            ])
            ->count();

        if ($dominionCount > 0) {
            throw new GameException('User already has a dominion in this round');
        }
    }

    /**
     * @param Race $race
     * @param Realm $realm
     * @param Round $round
     * @throws GameException
     */
    protected function guardAgainstMismatchedAlignments(Race $race, Realm $realm, Round $round): void
    {
        if (!$round->mixed_alignment && $race->alignment !== $realm->alignment) {
            throw new GameException('Race and realm alignment do not match');
        }
    }

    /**
     * Get amount of barren land a new Dominion starts with.
     *
     * @return array
     */
    protected function getStartingBarrenLand(): array
    {
        return [
            'land_plain' => 40,
            'land_mountain' => 20,
            'land_swamp' => 20,
            'land_cavern' => 20,
            'land_forest' => 20,
            'land_hill' => 20,
            'land_water' => 20,
        ];
    }

    /**
     * Get amount of buildings a new Dominion starts with.
     *
     * @return array
     */
    protected function getStartingBuildings(): array
    {
        return [
            'building_home' => 10,
            'building_alchemy' => 30,
            'building_farm' => 30,
            'building_smithy' => 0,
            'building_masonry' => 0,
            'building_ore_mine' => 0,
            'building_gryphon_nest' => 0,
            'building_tower' => 0,
            'building_wizard_guild' => 0,
            'building_temple' => 0,
            'building_diamond_mine' => 0,
            'building_school' => 0,
            'building_lumberyard' => 20,
            'building_forest_haven' => 0,
            'building_factory' => 0,
            'building_guard_tower' => 0,
            'building_shrine' => 0,
            'building_barracks' => 0,
            'building_dock' => 0,
        ];
    }

    /**
     * Get amount of total starting land a new Dominion starts with, factoring
     * in both buildings and barren land.
     *
     * @param Race $race
     * @param array $startingBarrenLand
     * @param array $startingBuildings
     * @return array
     */
    protected function getStartingLand(Race $race, array $startingBarrenLand, array $startingBuildings): array
    {
        $startingLand = [
            'land_plain' => $startingBarrenLand['land_plain'] + $startingBuildings['building_alchemy'] + $startingBuildings['building_farm'],
            'land_mountain' => $startingBarrenLand['land_mountain'],
            'land_swamp' => $startingBarrenLand['land_swamp'],
            'land_cavern' => $startingBarrenLand['land_cavern'],
            'land_forest' => $startingBarrenLand['land_forest'] + $startingBuildings['building_lumberyard'],
            'land_hill' => $startingBarrenLand['land_hill'],
            'land_water' => $startingBarrenLand['land_water'],
        ];

        $startingLand["land_{$race->home_land_type}"] += $startingBuildings['building_home'];

        return $startingLand;
    }

    /**
     * Get amount of total starting non-land, non-building attributes,
     * factoring in additional resources due to late start.
     *
     * @param Round $round
     * @return array
     */
    protected function getStartingAttributes(Round $round): array
    {
        $days = 0;
        if ($round->hasStarted()) {
            $daysLate = now()->diffInDays($round->start_date);
            if ($daysLate >= 3) {
                // Additional resources are not added until after the third day of the round
                $days = $daysLate;
            }
        }

        // Based on additional starting resource formula in Blackreign's Sim
        $startingAttributes = [
            'prestige' => 250,
            'peasants' => 1300 + (100 * $days),
            'peasants_last_hour' => 0,

            'draft_rate' => 10,
            'morale' => 100,
            'spy_strength' => 100,
            'wizard_strength' => 100,
            'daily_platinum' => 0,
            'daily_land' => 0,

            'resource_platinum' => 100000 + (5000 * $days),
            'resource_food' => 15000 + (1500 * $days),
            'resource_lumber' => 15000 + (2500 * $days),
            'resource_ore' => 0 + (2500 * $days),
            'resource_mana' => 0 + (1000 * $days),
            'resource_gems' => 10000,
            'resource_tech' => 0,
            'resource_boats' => 0,

            'improvement_science' => 0,
            'improvement_keep' => 0,
            'improvement_towers' => 0,
            'improvement_forges' => 0,
            'improvement_walls' => 0,
            'improvement_harbor' => 0,

            'military_draftees' => 100 + (30 * $days),
            'military_unit1' => 0,
            'military_unit2' => 150 + (30 * $days),
            'military_unit3' => 0,
            'military_unit4' => 0,
            'military_spies' => 25,
            'military_wizards' => 25,
            'military_archmages' => 0,

            'discounted_land' => 0,
            'highest_land_achieved' => 250,
            'royal_guard_active_at' => null,
            'elite_guard_active_at' => null,
            'protection_ticks_remaining' => 72,
        ];

        return $startingAttributes;
    }

    /**
     * Creates and returns a new Dominion instance.
     *
     * @param Realm $realm
     * @param Race $race
     * @param string $rulerName
     * @param string $dominionName
     * @return Dominion
     * @throws GameException
     */
    public function createNonPlayer(
        Realm $realm,
        Race $race,
        string $rulerName,
        string $dominionName
    ): ?Dominion {
        $this->guardAgainstMismatchedAlignments($race, $realm, $realm->round);

        $startingBuildings = $this->getStartingBuildings();

        $startingLand = $this->getStartingLand(
            $race,
            $this->getStartingBarrenLand(),
            $startingBuildings
        );

        $startingAttributes = $this->getStartingAttributes($realm->round);

        // Generate random starting build
        $landSize = (int) random_distribution(500, 100);
        if ($landSize < 325 || $landSize > 600) {
            // Clamp land size, those out of range converted to 270 acres
            $landSize = 270;
            $startingLand["land_{$race->home_land_type}"] += 20;
        }
        if ($landSize > 270) {
            $startingBuildings = $this->getNonPlayerBuildings($race, $landSize);
            $startingLand = $this->getNonPlayerLand($race, $startingBuildings);
        }

        $dominion = new Dominion([
            'user_id' => null,
            'round_id' => $realm->round->id,
            'realm_id' => $realm->id,
            'race_id' => $race->id,
            'pack_id' => null,

            'ruler_name' => $rulerName,
            'name' => $dominionName,
            'prestige' => 250,

            'peasants' => 10 * $landSize,
            'peasants_last_hour' => 0,

            'draft_rate' => 30,
            'morale' => 100,
            'spy_strength' => 100,
            'wizard_strength' => 100,

            'resource_platinum' => 0,
            'resource_food' => 100 * $landSize,
            'resource_lumber' => 0,
            'resource_mana' => 0,
            'resource_ore' => 0,
            'resource_gems' => 0,
            'resource_tech' => 0,
            'resource_boats' => 0,

            'improvement_science' => 0,
            'improvement_keep' => 0,
            'improvement_towers' => 0,
            'improvement_forges' => 0,
            'improvement_walls' => 0,
            'improvement_harbor' => 0,

            'military_draftees' => 0,
            'military_unit1' => 0,
            'military_unit2' => 0,
            'military_unit3' => 0,
            'military_unit4' => 0,
            'military_spies' => 20,
            'military_wizards' => 20,
            'military_archmages' => 0,

            'land_plain' => $startingLand['land_plain'],
            'land_mountain' => $startingLand['land_mountain'],
            'land_swamp' => $startingLand['land_swamp'],
            'land_cavern' => $startingLand['land_cavern'],
            'land_forest' => $startingLand['land_forest'],
            'land_hill' => $startingLand['land_hill'],
            'land_water' => $startingLand['land_water'],

            'building_home' => $startingBuildings['building_home'],
            'building_alchemy' => $startingBuildings['building_alchemy'],
            'building_farm' => $startingBuildings['building_farm'],
            'building_smithy' => $startingBuildings['building_smithy'],
            'building_masonry' => 0,
            'building_ore_mine' => $startingBuildings['building_ore_mine'],
            'building_gryphon_nest' => 0,
            'building_tower' => $startingBuildings['building_tower'],
            'building_wizard_guild' => 0,
            'building_temple' => $startingBuildings['building_temple'],
            'building_diamond_mine' => 0,
            'building_school' => 0,
            'building_lumberyard' => $startingBuildings['building_lumberyard'],
            'building_forest_haven' => 0,
            'building_factory' => $startingBuildings['building_factory'],
            'building_guard_tower' => $startingBuildings['building_guard_tower'],
            'building_shrine' => 0,
            'building_barracks' => 0,
            'building_dock' => 0,

            'protection_ticks_remaining' => 0,
        ]);

        if ($landSize > 270) {
            //  Calculate Defense
            $accuracy = 1 - (mt_rand(-10, 0) / 100);
            $defense = $landSize * ((0.008 * $landSize) + 0.9);
            $defense *= $accuracy;
            $specRatio = 1;
            if (random_chance(0.85)) {
                $specRatio = mt_rand(50, 75) / 100;
            }

            $militaryCalculator = app(\OpenDominion\Calculators\Dominion\MilitaryCalculator::class);
            $defenseMod = $militaryCalculator->getDefensivePowerMultiplier($dominion);
            $specPower = $militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $race->units[1], 'defense');
            $elitePower = $militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $race->units[2], 'defense');

            $dominion->military_unit2 = (int) $defense / $defenseMod / $specPower * $specRatio;
            $dominion->military_unit3 = (int) $defense / $defenseMod / $elitePower * (1 - $specRatio);
        } else {
            $dominion->military_unit2 = 150;
        }

        try {
            $dominion->save();
        } catch (\Illuminate\Database\QueryException $e) {
            return null;
        }

        //  Add incoming units
        if ($landSize > 270) {
            $queueService = app(\OpenDominion\Services\Dominion\QueueService::class);
            $incSpecs = (int) $dominion->military_unit2 * (mt_rand(25, 50) / 100);
            $incElites = (int) $dominion->military_unit3 * (mt_rand(25, 50) / 100);
            $hours = array_rand(range(4, 12), mt_rand(2, 5));
            foreach ($hours as $key => $hour) {
                if ($key === array_key_last($hours)) {
                    $queueService->queueResources('training', $dominion, ['military_unit2' => $incSpecs, 'military_unit3' => $incElites], $hour);
                } else {
                    if ($incElites > 0 && random_chance(0.5)) {
                        $amount = max(10, mt_rand($incElites / 5, $incElites));
                        $incElites -= $amount;
                        $queueService->queueResources('training', $dominion, ['military_unit3' => $amount], $hour);
                    } elseif ($incSpecs > 0) {
                        $amount = max(10, mt_rand($incSpecs / 5, $incSpecs));
                        $queueService->queueResources('training', $dominion, ['military_unit2' => $amount], $hour);
                        $incSpecs -= $amount;
                    }
                }
            }
        }

        return $dominion;
    }

    /**
     * Get the number of buildings a non player Dominion starts with.
     *
     * @param Race $race
     * @param int $landSize
     * @return array
     */
    protected function getNonPlayerBuildings(Race $race, int $landSize): array
    {
        $startingBuildings = [
            'building_home' => 10,
            'building_alchemy' => 30,
            'building_farm' => 30,
            'building_smithy' => 0,
            'building_masonry' => 0,
            'building_ore_mine' => 0,
            'building_gryphon_nest' => 0,
            'building_tower' => mt_rand(13, 0.044 * $landSize),
            'building_wizard_guild' => 0,
            'building_temple' => 0,
            'building_diamond_mine' => 0,
            'building_school' => 0,
            'building_lumberyard' => 20,
            'building_forest_haven' => 0,
            'building_factory' => 56 * random_chance(0.75),
            'building_guard_tower' => 0,
            'building_shrine' => 0,
            'building_barracks' => 0,
            'building_dock' => 0,
        ];

        $landAvailable = $landSize - array_sum($startingBuildings);

        // Ore Mines
        $racesWithoutOre = ['Firewalker', 'Lizardfolk', 'Merfolk', 'Spirit', 'Sylvan', 'Undead'];
        if (!in_array($race->name, $racesWithoutOre)) {
            $startingBuildings['building_ore_mine'] = 20;
            $landAvailable -= $startingBuildings['building_ore_mine'];
        }

        // Temples for larger doms
        if ($landAvailable > 100) {
            $startingBuildings['building_temple'] = min(mt_rand(15, max(30, 0.05 * $landSize)), $landAvailable);
            $landAvailable -= $startingBuildings['building_temple'];
        }

        // Alchemies
        $startingBuildings['building_alchemy'] = min(mt_rand(0.33 * $landSize, 250), $landAvailable);
        $landAvailable -= $startingBuildings['building_alchemy'];

        // Up to max Smithies
        if ($landAvailable > 50) {
            $maxSmithies = (int) round(0.18 * $landSize);
            if ($maxSmithies < $landAvailable) {
                $startingBuildings['building_smithy'] = $maxSmithies;
            } else {
                $startingBuildings['building_smithy'] = mt_rand(50, $landAvailable);
            }
            $landAvailable -= $startingBuildings['building_smithy'];
        }

        // Guard Towers
        if ($landAvailable > 25) {
            $startingBuildings['building_guard_tower'] = min(mt_rand(25, 0.20 * $landSize), $landAvailable);
            $landAvailable -= $startingBuildings['building_guard_tower'];
        }

        // Remainder into Homes
        if ($landAvailable > 0) {
            $startingBuildings['building_home'] += $landAvailable;
        }

        return $startingBuildings;
    }

    /**
     * Get amount of total starting land a non player Dominion starts with.
     *
     * @param Race $race
     * @param array $startingBuildings
     * @return array
     */
    protected function getNonPlayerLand(Race $race, array $startingBuildings): array
    {
        $startingLand = [
            'land_plain' => $startingBuildings['building_alchemy'] + $startingBuildings['building_farm'] + $startingBuildings['building_smithy'],
            'land_mountain' => $startingBuildings['building_ore_mine'],
            'land_swamp' => $startingBuildings['building_temple'] + $startingBuildings['building_tower'],
            'land_cavern' => 0,
            'land_forest' => $startingBuildings['building_lumberyard'],
            'land_hill' => $startingBuildings['building_factory'] + $startingBuildings['building_guard_tower'],
            'land_water' => 0,
        ];

        $startingLand["land_{$race->home_land_type}"] += $startingBuildings['building_home'];

        return $startingLand;
    }
}
