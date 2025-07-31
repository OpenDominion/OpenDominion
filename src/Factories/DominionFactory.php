<?php

namespace OpenDominion\Factories;

use DB;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Models\User;
use OpenDominion\Services\Dominion\AutomationService;

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
        string $protectionType = 'quick',
        ?Pack $pack = null
    ): Dominion {
        $this->guardAgainstMultipleDominionsInARound($user, $realm->round);
        $this->guardAgainstMismatchedAlignments($race, $realm, $realm->round);

        $startingBuildings = $this->getStartingBuildings();

        $startingLand = $this->getStartingLand(
            $race,
            $this->getStartingBarrenLand($protectionType),
            $startingBuildings
        );

        $startingAttributes = $this->getStartingAttributes();

        $additionalAttributes = $this->getLateStartAttributes($realm->round);
        foreach ($additionalAttributes as $attribute => $value) {
            $startingAttributes[$attribute] += $value;
        }

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

            'draft_rate' => 35,
            'morale' => 100,
            'spy_strength' => 100,
            'wizard_strength' => 100,

            'daily_platinum' => $startingAttributes['daily_platinum'],
            'daily_land' => $startingAttributes['daily_land'],
            'daily_actions' => $startingAttributes['daily_actions'],
            'ai_enabled' => $startingAttributes['ai_enabled'],
            'ai_config' => $startingAttributes['ai_config'],

            'resource_platinum' => $startingAttributes['resource_platinum'],
            'resource_food' => $startingAttributes['resource_food'],
            'resource_lumber' => $startingAttributes['resource_lumber'],
            'resource_mana' => $startingAttributes['resource_mana'],
            'resource_ore' => $startingAttributes['resource_ore'],
            'resource_gems' => $startingAttributes['resource_gems'],
            'resource_tech' => $startingAttributes['resource_tech'],
            'resource_boats' => $startingAttributes['resource_boats'],

            'improvement_science' => 0,
            'improvement_keep' => 0,
            'improvement_spires' => 0,
            'improvement_forges' => 0,
            'improvement_walls' => 0,
            'improvement_harbor' => 0,

            'military_draftees' => $startingAttributes['military_draftees'],
            'military_unit1' => 0,
            'military_unit2' => $startingAttributes['military_unit2'],
            'military_unit3' => 0,
            'military_unit4' => 0,
            'military_spies' => 25,
            'military_assassins' => 0,
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

            'protection_type' => $startingAttributes['protection_type'],
            'protection_ticks' => $startingAttributes['protection_ticks'],
            'protection_ticks_remaining' => $startingAttributes['protection_ticks_remaining'],
            'protection_finished' => $startingAttributes['protection_finished'],
        ]);
    }

    /**
     * Resets a Dominion to an equivalent state as a new registration.
     *
     * @param  Dominion $dominion
     * @param  Race $race
     * @param string $name
     * @param string $rulerName
     * @param string $protectionType
     * @throws GameException
     */
    public function restart(Dominion $dominion, Race $race, ?string $name, ?string $rulerName, ?string $protectionType): void
    {
        // Reset Queues
        DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->delete();

        // Reset Spells
        DominionSpell::where('dominion_id', $dominion->id)->delete();

        // Reset Techs
        DB::table('dominion_techs')
            ->where('dominion_id', $dominion->id)
            ->delete();

        // Reset Heroes
        DB::table('heroes')
            ->where('dominion_id', $dominion->id)
            ->delete();

        // Reset Notifications
        DB::table('notifications')
            ->where('notifiable_type', Dominion::class)
            ->where('notifiable_id', $dominion->id)
            ->delete();

        // Reset starting buildings
        $startingBuildings = $this->getStartingBuildings($protectionType);
        foreach ($startingBuildings as $building_type => $value) {
            $dominion->{$building_type} = $value;
        }

        // Reset starting land
        $startingLand = $this->getStartingLand(
            $race,
            $this->getStartingBarrenLand($protectionType),
            $startingBuildings
        );
        foreach ($startingLand as $land_type => $value) {
            $dominion->{$land_type} = $value;
        }

        // Reset other starting attributes
        $startingAttributes = $this->getStartingAttributes($protectionType);
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

        if ($protectionType == 'quick') {
            // Late start defense
            if ($dominion->round->daysInRound() > 1 || $dominion->round->hoursInDay() >= 3) {
                $aiHelper = app(\OpenDominion\Helpers\AIHelper::class);
                $landCalculator = app(\OpenDominion\Calculators\Dominion\LandCalculator::class);
                $militaryCalculator = app(\OpenDominion\Calculators\Dominion\MilitaryCalculator::class);

                if ($race->name == 'Goblin') {
                    $unitSlot = 2;
                } elseif ($race->name == 'Troll') {
                    $unitSlot = 4;
                } else {
                    $unitSlot = 3;
                }

                $botDefense = $aiHelper->getDefenseForNonPlayer($dominion->round, $landCalculator->getTotalLand($dominion));
                $currentDefense = $militaryCalculator->getDefensivePower($dominion, null, null, null, 0, true, false);
                $defenseMod = $militaryCalculator->getDefensivePowerMultiplier($dominion);
                $unitPower = $militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $race->units[$unitSlot - 1], 'defense');

                $defenseNeeded = ($botDefense - $currentDefense) / $defenseMod * 1.1;
                if ($defenseNeeded > 0) {
                    $unitsNeeded = round($defenseNeeded / $unitPower);
                    $dominion->{"military_unit$unitSlot"} += $unitsNeeded;
                }
            }
        }

        // Additional late start resources
        $additionalAttributes = $this->getLateStartAttributes($dominion->round);
        foreach ($additionalAttributes as $attribute => $value) {
            $dominion->{$attribute} += $value;
        }

        $dominion->race_id = $race->id;
        if ($name !== null) {
            $dominion->name = $name;
        }
        if ($rulerName !== null) {
            $dominion->ruler_name = $rulerName;
        }

        $dominion->updated_at = now();
        $dominion->save([
            'event' => \OpenDominion\Services\Dominion\HistoryService::EVENT_ACTION_RESTART,
            'action' => $protectionType
        ]);
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
    protected function getStartingBarrenLand(string $protectionType = 'quick'): array
    {
        if ($protectionType == 'quick') {
            return [
                'land_plain' => 560,
                'land_mountain' => 0,
                'land_swamp' => 0,
                'land_cavern' => 0,
                'land_forest' => 0,
                'land_hill' => 0,
                'land_water' => 0,
            ];
        }

        return [
            'land_plain' => 350,
            'land_mountain' => 0,
            'land_swamp' => 0,
            'land_cavern' => 0,
            'land_forest' => 0,
            'land_hill' => 0,
            'land_water' => 0,
        ];
    }

    /**
     * Get amount of buildings a new Dominion starts with.
     *
     * @return array
     */
    protected function getStartingBuildings(string $protectionType = 'quick'): array
    {
        $startingBuildings = [
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
        ];

        return $startingBuildings;
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
            'land_plain' => $startingBarrenLand['land_plain'],
            'land_mountain' => $startingBarrenLand['land_mountain'],
            'land_swamp' => $startingBarrenLand['land_swamp'],
            'land_cavern' => $startingBarrenLand['land_cavern'],
            'land_forest' => $startingBarrenLand['land_forest'],
            'land_hill' => $startingBarrenLand['land_hill'],
            'land_water' => $startingBarrenLand['land_water'],
        ];

        $startingLand["land_{$race->home_land_type}"] += $startingBuildings['building_home'];

        return $startingLand;
    }

    /**
     * Get amount of total starting non-land, non-building attributes.
     *
     * @return array
     */
    protected function getStartingAttributes(string $protectionType = 'quick'): array
    {
        $startingAttributes = [
            'prestige' => 250,
            'peasants' => 1000,
            'peasants_last_hour' => 0,

            'draft_rate' => 90,
            'morale' => 100,
            'spy_strength' => 100,
            'wizard_strength' => 100,

            'daily_platinum' => 0,
            'daily_land' => 0,
            'daily_actions' => AutomationService::DAILY_ACTIONS,
            'ai_enabled' => false,
            'ai_config' => null,

            'resource_platinum' => 120000,
            'resource_food' => 15000,
            'resource_lumber' => 15000,
            'resource_ore' => 0,
            'resource_mana' => 0,
            'resource_gems' => 0,
            'resource_tech' => 0,
            'resource_boats' => 0,

            'improvement_science' => 0,
            'improvement_keep' => 0,
            'improvement_spires' => 0,
            'improvement_forges' => 0,
            'improvement_walls' => 0,
            'improvement_harbor' => 0,

            'military_draftees' => 300,
            'military_unit1' => 0,
            'military_unit2' => 0,
            'military_unit3' => 0,
            'military_unit4' => 0,
            'military_spies' => 0,
            'military_assassins' => 0,
            'military_wizards' => 0,
            'military_archmages' => 0,

            'discounted_land' => 0,
            'highest_land_achieved' => 350,
            'royal_guard_active_at' => null,
            'elite_guard_active_at' => null,
            'black_guard_active_at' => null,

            'protection_type' => $protectionType,
            'protection_ticks' => 48,
            'protection_ticks_remaining' => 49,
            'protection_finished' => false,
        ];

        if ($protectionType == 'quick') {
            $startingAttributes['resource_platinum'] = 50000;
            $startingAttributes['military_draftees'] = 150;
            $startingAttributes['protection_ticks'] = 36;
            $startingAttributes['protection_ticks_remaining'] = 37;
        }

        return $startingAttributes;
    }

    /**
     * Get additional resources awarded due to late start.
     *
     * @param Round $round
     * @return array
     */
    protected function getLateStartAttributes(Round $round): array
    {
        $days = 0;
        if ($round->hasStarted()) {
            $daysLate = now()->diffInDays($round->start_date);
            if ($daysLate >= 2) {
                // Additional resources are not added until the 2nd day of the round
                $days = $daysLate + 3;
            }
        }

        return [
            'peasants' => (100 * $days),
            'resource_platinum' => (5000 * $days),
            'resource_food' => (1500 * $days),
            'resource_lumber' => (2500 * $days),
            'resource_ore' => (2500 * $days),
            'resource_mana' => (1000 * $days),
            'resource_gems' => (2000 * $days),
            'resource_tech' => (2400 * $days),
            'resource_boats' => (20 * $days),

            'military_draftees' => (30 * $days),
            'military_unit2' => (30 * $days),
            'military_unit3' => 0,
        ];
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
        string $dominionName,
        int $landSize
    ): ?Dominion {
        $this->guardAgainstMismatchedAlignments($race, $realm, $realm->round);

        // Generate random starting build
        $startingAttributes = $this->getStartingAttributes();
        $startingBuildings = $this->getNonPlayerBuildings($race, $landSize);
        $startingLand = $this->getNonPlayerLand($race, $startingBuildings);

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

            'draft_rate' => 0,
            'morale' => 100,
            'spy_strength' => 100,
            'wizard_strength' => 100,

            'resource_platinum' => 0,
            'resource_food' => 100 * $landSize,
            'resource_lumber' => 100 * $landSize,
            'resource_mana' => 100 * $landSize,
            'resource_ore' => 100 * $landSize,
            'resource_gems' => 0,
            'resource_tech' => 0,
            'resource_boats' => 0,

            'improvement_science' => 0,
            'improvement_keep' => 0,
            'improvement_spires' => 0,
            'improvement_forges' => 0,
            'improvement_walls' => 0,
            'improvement_harbor' => 0,

            'military_draftees' => mt_rand(0, $landSize),
            'military_unit1' => 0,
            'military_unit2' => 0,
            'military_unit3' => 0,
            'military_unit4' => 0,
            'military_spies' => 15,
            'military_assassins' => 0,
            'military_wizards' => 15,
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

            'royal_guard_active_at' => (clone $realm)->round->start_date->addDays(6),
            'protection_type' => 'bot',
            'protection_ticks' => 1,
            'protection_ticks_remaining' => 0,
            'protection_finished' => true,
        ]);

        // Generate Military
        $specRatio = 1;
        if (random_chance(0.85)) {
            $specRatio = mt_rand(50, 75) / 100;
        }

        $militaryCalculator = app(\OpenDominion\Calculators\Dominion\MilitaryCalculator::class);
        $defenseMod = $militaryCalculator->getDefensivePowerMultiplier($dominion) + 0.1;
        $specPower = $militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $race->units[1], 'defense');
        $elitePower = $militaryCalculator->getUnitPowerWithPerks($dominion, null, null, $race->units[2], 'defense');

        if (random_chance(0.85)) {
            $accuracy = mt_rand(95, 105) * mt_rand(95, 105) / 10000;
        } else {
            // 15% spawn with 15-20% more DP
            $accuracy = 1.1 + (mt_rand(50, 100) / 1000);
        }
        if ($landSize > 525) {
            $defense = 710 * exp(0.003 * $landSize) * $accuracy;
        } else {
            $defense = 45.8 * exp(0.0075 * $landSize) * $accuracy;
        }
        $defense -= ($dominion->military_draftees * $defenseMod);
        $dominion->military_unit2 = (int) ($defense / $defenseMod / $specPower * $specRatio);
        $dominion->military_unit3 = (int) ($defense / $defenseMod / $elitePower * (1 - $specRatio));
        if ($accuracy < 1.15) {
            // Add some spec OP to normalize networth
            $specOP = (int) (($accuracy - 0.9) / 0.2 * $landSize);
            $dominion->military_unit1 = $specOP + mt_rand(50, 100);
        }

        try {
            $dominion->save();
        } catch (\Illuminate\Database\QueryException $e) {
            return null;
        }

        // Add incoming units
        $queueService = app(\OpenDominion\Services\Dominion\QueueService::class);
        $additionalDefense = (2 * $landSize) + mt_rand(200, 500);
        $incSpecs = (int) ($additionalDefense / $defenseMod / $specPower * $specRatio);
        $incElites = (int) ($additionalDefense / $defenseMod / $elitePower * (1 - $specRatio));
        $hourRange = collect(range(4, 12));
        $hours = $hourRange->random(mt_rand(2, 5));
        foreach ($hours as $hour) {
            if ($hour == $hours->last()) {
                $queueService->queueResources('training', $dominion, ['military_unit2' => $incSpecs, 'military_unit3' => $incElites], $hour);
            } else {
                if ($incElites > 0 && random_chance(0.5)) {
                    $amount = mt_rand($incElites / 5, $incElites / 3);
                    $incElites -= min($amount, $incElites);
                    $queueService->queueResources('training', $dominion, ['military_unit3' => $amount], $hour);
                } elseif ($incSpecs > 0) {
                    $amount = mt_rand($incSpecs / 5, $incSpecs / 3);
                    $queueService->queueResources('training', $dominion, ['military_unit2' => $amount], $hour);
                    $incSpecs -= min($amount, $incSpecs);
                }
            }
        }

        // Cast spells
        $spell = Spell::where('key', 'ares_call')->first();
        DominionSpell::insert([
            'dominion_id' => $dominion->id,
            'spell_id' => $spell->id,
            'duration' => 12,
            'cast_by_dominion_id' => $dominion->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $spell = Spell::where('key', 'midas_touch')->first();
        DominionSpell::insert([
            'dominion_id' => $dominion->id,
            'spell_id' => $spell->id,
            'duration' => 12,
            'cast_by_dominion_id' => $dominion->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
            'building_factory' => 42 * random_chance(0.75),
            'building_guard_tower' => 0,
            'building_shrine' => 0,
            'building_barracks' => 0,
            'building_dock' => 0,
        ];

        $landAvailable = $landSize - array_sum($startingBuildings);
        $racesWithoutOre = ['Firewalker', 'Lizardfolk', 'Merfolk', 'Nox', 'Spirit', 'Sylvan', 'Undead', 'Vampire'];
        $landBasedRaces = ['Gnome', 'Icekin', 'Nox', 'Sylvan', 'Wood Elf'];

        // Ore Mines
        if (!in_array($race->name, $racesWithoutOre)) {
            if (in_array($race->name, ['Gnome', 'Icekin'])) {
                $startingBuildings['building_ore_mine'] += min(100, $landAvailable);
            } else {
                $startingBuildings['building_ore_mine'] += 20;
                if ($race->name == 'Troll') {
                    $startingBuildings['building_ore_mine'] += 20;
                }
            }
            $landAvailable -= $startingBuildings['building_ore_mine'];
        }

        // Temples
        if ($landAvailable > 100) {
            $startingBuildings['building_temple'] += min(mt_rand(15, max(30, 0.05 * $landSize)), $landAvailable);
            $landAvailable -= $startingBuildings['building_temple'];
        }

        // Alchemies
        $startingBuildings['building_alchemy'] += min(mt_rand(20, 170), $landAvailable);
        $landAvailable -= ($startingBuildings['building_alchemy'] - 30);

        // Smithies
        if (!in_array($race->name, $landBasedRaces) && $landAvailable > 50) {
            $maxSmithies = (int) round(0.18 * $landSize);
            $startingBuildings['building_smithy'] += min(mt_rand(20, $maxSmithies), $landAvailable);
            $landAvailable -= $startingBuildings['building_smithy'];
        }

        // Guard Towers
        if (!in_array($race->name, $landBasedRaces) && $landAvailable > 150) {
            $startingBuildings['building_guard_tower'] += min(mt_rand(40, 100), $landAvailable);
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

    /**
     * Get the quick start options for all races.
     *
     * @return array
     */
    public function getQuickStartOptions(): array
    {
        $filesystem = app(\Illuminate\Filesystem\Filesystem::class);
        $files = $filesystem->files(base_path('app/data/quickstarts'));

        return collect($files)->map(function ($file) {
            $filename = $file->getFilename();
            $parts = explode('_', str_replace('.json', '', $filename));

            return [
                'filename' => $filename,
                'race' => $parts[0],
                'type' => $parts[1],
                'size' => $parts[2],
                'variant' => isset($parts[3]) ? $parts[3] : 'base'
            ];
        })->all();
    }

    /**
     * Return the quick start data from a filen.
     *
     * @param string $filename
     * @return array
     */
    public function getQuickStartData(string $filename): array
    {
        $filesystem = app(\Illuminate\Filesystem\Filesystem::class);
        $json = json_decode($filesystem->get(base_path('app/data/quickstarts/' . $filename)));
        return $json;
    }
}
