<?php

namespace Database\Seeders;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Seeder;
use OpenDominion\Models\Raid;
use OpenDominion\Models\RaidObjective;
use OpenDominion\Models\RaidObjectiveTactic;
use OpenDominion\Models\Round;

class RaidSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $round = Round::where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->first();

            if (!$round) {
                $this->command->warn('No active round found. Creating a test round...');
                $round = Round::create([
                    'number' => 1,
                    'league_id' => 1,
                    'name' => 'Test Round',
                    'start_date' => now()->subDays(7),
                    'end_date' => now()->addDays(60),
                    'round_type' => 'standard',
                    'realm_size' => 12,
                    'pack_size' => 4,
                    'players_per_race' => 4,
                ]);
            }

            $this->createDragonSlayerRaid($round);
            $this->createAncientRelicRaid($round);

            $this->command->info('Raid data seeded successfully!');
        });
    }

    /**
     * Create the Dragon Slayer raid with multiple objectives
     */
    protected function createDragonSlayerRaid(Round $round): void
    {
        $raid = Raid::create([
            'round_id' => $round->id,
            'name' => 'The Dragon Slayer Campaign',
            'description' => 'Unite the realms to slay the ancient dragon threatening all of the kingdom.',
            'reward_resource' => 'platinum',
            'reward_amount' => 50000,
            'completion_reward_resource' => 'research_points',
            'completion_reward_amount' => 1000,
            'start_date' => now()->subHours(1),
            'end_date' => now()->addDays(14),
        ]);

        // Objective 1: Gather Intelligence
        $objective1 = RaidObjective::create([
            'raid_id' => $raid->id,
            'name' => 'Gather Dragon Intelligence',
            'description' => 'Scout the dragon\'s lair and gather information about its weaknesses.',
            'order' => 1,
            'score_required' => 10000,
            'start_date' => now()->subHours(1),
            'end_date' => now()->addDays(3),
        ]);

        // Espionage tactics for intelligence gathering
        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective1->id,
            'type' => 'espionage',
            'name' => 'Scout Dragon Perimeter',
            'attributes' => [
                'strength_cost' => 15,
                'points_awarded' => 100,
            ],
            'bonuses' => [
                'race_bonuses' => ['halfling' => 1.2, 'elf' => 1.1],
                'tech_bonuses' => ['spy_networks' => 1.15],
            ],
        ]);

        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective1->id,
            'type' => 'espionage',
            'name' => 'Stealth Reconnaissance',
            'attributes' => [
                'strength_cost' => 25,
                'points_awarded' => 160,
            ],
            'bonuses' => [
                'race_bonuses' => ['halfling' => 1.2, 'elf' => 1.1],
                'tech_bonuses' => ['spy_networks' => 1.15],
            ],
        ]);

        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective1->id,
            'type' => 'espionage',
            'name' => 'Deep Lair Infiltration',
            'attributes' => [
                'strength_cost' => 35,
                'points_awarded' => 250,
            ],
            'bonuses' => [
                'race_bonuses' => ['halfling' => 1.2, 'elf' => 1.1],
                'tech_bonuses' => ['spy_networks' => 1.15],
            ],
        ]);

        // Investment tactics
        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective1->id,
            'type' => 'investment',
            'name' => 'Fund Intelligence Network (Platinum)',
            'attributes' => [
                'resource' => 'platinum',
                'amount' => 1000,
                'points_awarded' => 100,
            ],
            'bonuses' => [
                'race' => ['gnome' => 1.1, 'dwarf' => 1.05],
                'tech' => ['economics' => 1.1],
            ],
        ]);

        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective1->id,
            'type' => 'investment',
            'name' => 'Fund Intelligence Network (Gems)',
            'attributes' => [
                'resource' => 'gems',
                'amount' => 100,
                'points_awarded' => 200,
            ],
            'bonuses' => [
                'race' => ['gnome' => 1.1, 'dwarf' => 1.05],
                'tech' => ['economics' => 1.1],
            ],
        ]);

        // Objective 2: Prepare Defenses
        $objective2 = RaidObjective::create([
            'raid_id' => $raid->id,
            'name' => 'Prepare Siege Equipment',
            'description' => 'Build massive siege weapons and fortifications for the assault.',
            'order' => 2,
            'score_required' => 15000,
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(8),
        ]);

        // Exploration tactics (though this one only has one option)
        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective2->id,
            'type' => 'exploration',
            'name' => 'Expand Weapon Foundries',
            'attributes' => [
                'draftee_cost' => 500,
                'morale_cost' => 2,
                'points_awarded' => 200,
            ],
            'bonuses' => [
                'race' => ['dwarf' => 1.25, 'human' => 1.1],
                'tech' => ['engineering' => 1.2],
            ],
        ]);

        // Investment for materials
        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective2->id,
            'type' => 'investment',
            'name' => 'Provide Construction Materials (Lumber)',
            'attributes' => [
                'resource' => 'lumber',
                'amount' => 500,
                'points_awarded' => 75,
            ],
            'bonuses' => [
                'race' => ['dwarf' => 1.2, 'human' => 1.1],
                'tech' => ['construction' => 1.15],
            ],
        ]);

        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective2->id,
            'type' => 'investment',
            'name' => 'Provide Construction Materials (Ore)',
            'attributes' => [
                'resource' => 'ore',
                'amount' => 250,
                'points_awarded' => 100,
            ],
            'bonuses' => [
                'race' => ['dwarf' => 1.2, 'human' => 1.1],
                'tech' => ['construction' => 1.15],
            ],
        ]);

        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective2->id,
            'type' => 'investment',
            'name' => 'Provide Construction Materials (Platinum)',
            'attributes' => [
                'resource' => 'platinum',
                'amount' => 2000,
                'points_awarded' => 40,
            ],
            'bonuses' => [
                'race' => ['dwarf' => 1.2, 'human' => 1.1],
                'tech' => ['construction' => 1.15],
            ],
        ]);

        // Objective 3: Magical Preparations
        $objective3 = RaidObjective::create([
            'raid_id' => $raid->id,
            'name' => 'Weave Protective Enchantments',
            'description' => 'Cast powerful protective spells to shield the assault force.',
            'order' => 3,
            'score_required' => 12000,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(11),
        ]);

        // Magic tactics
        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective3->id,
            'type' => 'magic',
            'name' => 'Ward of Dragon Resistance',
            'attributes' => [
                'mana_cost' => 8, // 8x land multiplier
                'strength_cost' => 25,
                'points_awarded' => 300,
            ],
            'bonuses' => [
                'race_bonuses' => ['elf' => 1.3, 'lizardfolk' => 1.2],
                'tech_bonuses' => ['magical_focus' => 1.25],
            ],
        ]);

        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective3->id,
            'type' => 'magic',
            'name' => 'Shield of the Ancients',
            'attributes' => [
                'mana_cost' => 12, // 12x land multiplier
                'strength_cost' => 35,
                'points_awarded' => 420,
            ],
            'bonuses' => [
                'race_bonuses' => ['elf' => 1.3, 'lizardfolk' => 1.2],
                'tech_bonuses' => ['magical_focus' => 1.25],
            ],
        ]);

        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective3->id,
            'type' => 'magic',
            'name' => 'Enchant Siege Weapons',
            'attributes' => [
                'mana_cost' => 15, // 15x land multiplier
                'strength_cost' => 40,
                'points_awarded' => 500,
            ],
            'bonuses' => [
                'race_bonuses' => ['elf' => 1.3, 'lizardfolk' => 1.2],
                'tech_bonuses' => ['magical_focus' => 1.25],
            ],
        ]);

        // Objective 4: The Final Assault
        $objective4 = RaidObjective::create([
            'raid_id' => $raid->id,
            'name' => 'Assault the Dragon',
            'description' => 'Launch the final coordinated assault on the dragon\'s lair.',
            'order' => 4,
            'score_required' => 20000,
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(14),
        ]);

        // Invasion tactics
        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective4->id,
            'type' => 'invasion',
            'name' => 'Frontal Assault',
            'description' => 'Launch a direct assault on the dragon\'s lair entrance with your military forces. Points awarded based on damage dealt.',
            'attributes' => [
                'casualties' => 15.0,
                'target_type' => 'dragon_lair_entrance',
            ],
            'bonuses' => [
                'race_bonuses' => ['human' => 1.15, 'dwarf' => 1.2],
                'tech_bonuses' => ['military_tactics' => 1.2],
            ],
        ]);

        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective4->id,
            'type' => 'invasion',
            'name' => 'Elite Strike Force',
            'description' => 'Deploy elite units to target the dragon\'s weak points with precision strikes. Points awarded based on damage dealt.',
            'attributes' => [
                'casualties' => 10.0,
                'target_type' => 'dragon_weak_point',
            ],
            'bonuses' => [
                'race_bonuses' => ['human' => 1.2, 'elf' => 1.15],
                'tech_bonuses' => ['military_tactics' => 1.25, 'archery' => 1.2],
            ],
        ]);

        // Hero tactics
        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective4->id,
            'type' => 'hero',
            'name' => 'Champion Duel',
            'attributes' => [
                'name' => 'Champion Duel with Ancient Dragon',
                'health' => 100,
                'attack' => 40,
                'defense' => 20,
                'evasion' => 10,
                'focus' => 10,
                'counter' => 10,
                'recover' => 20,
                'points_awarded' => 800,
            ],
            'bonuses' => [
                'race' => ['human' => 1.25, 'dwarf' => 1.2],
                'hero_class' => [
                    'alchemist' => 1.2,
                    'blacksmith' => 1.3,
                ],
            ],
        ]);
    }

    /**
     * Create the Ancient Relic raid - a simpler resource-focused raid
     */
    protected function createAncientRelicRaid(Round $round): void
    {
        $raid = Raid::create([
            'round_id' => $round->id,
            'name' => 'Ancient Relic Recovery',
            'description' => 'Recover powerful ancient relics from forgotten ruins.',
            'reward_resource' => 'gems',
            'reward_amount' => 10000,
            'completion_reward_resource' => 'mana',
            'completion_reward_amount' => 25000,
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(21),
        ]);

        // Single objective focused on resource contribution
        $objective = RaidObjective::create([
            'raid_id' => $raid->id,
            'name' => 'Fund Archaeological Expedition',
            'description' => 'Provide resources to fund a massive archaeological expedition.',
            'order' => 1,
            'score_required' => 50000,
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(21),
        ]);

        // Investment tactics with various resources
        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective->id,
            'type' => 'investment',
            'name' => 'Fund Expedition (Platinum)',
            'attributes' => [
                'resource' => 'platinum',
                'amount' => 1000,
                'points_awarded' => 150,
            ],
            'bonuses' => [
                'race' => [
                    'gnome' => 1.15,
                    'dwarf' => 1.2,
                    'human' => 1.1,
                ],
                'tech' => [
                    'economics' => 1.15,
                    'construction' => 1.1,
                ],
            ],
        ]);

        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective->id,
            'type' => 'investment',
            'name' => 'Fund Expedition (Lumber)',
            'attributes' => [
                'resource' => 'lumber',
                'amount' => 500,
                'points_awarded' => 60,
            ],
            'bonuses' => [
                'race' => [
                    'gnome' => 1.15,
                    'dwarf' => 1.2,
                    'human' => 1.1,
                ],
                'tech' => [
                    'economics' => 1.15,
                    'construction' => 1.1,
                ],
            ],
        ]);

        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective->id,
            'type' => 'investment',
            'name' => 'Fund Expedition (Ore)',
            'attributes' => [
                'resource' => 'ore',
                'amount' => 200,
                'points_awarded' => 50,
            ],
            'bonuses' => [
                'race' => [
                    'gnome' => 1.15,
                    'dwarf' => 1.2,
                    'human' => 1.1,
                ],
                'tech' => [
                    'economics' => 1.15,
                    'construction' => 1.1,
                ],
            ],
        ]);

        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective->id,
            'type' => 'investment',
            'name' => 'Fund Expedition (Gems)',
            'attributes' => [
                'resource' => 'gems',
                'amount' => 50,
                'points_awarded' => 250,
            ],
            'bonuses' => [
                'race' => [
                    'gnome' => 1.15,
                    'dwarf' => 1.2,
                    'human' => 1.1,
                ],
                'tech' => [
                    'economics' => 1.15,
                    'construction' => 1.1,
                ],
            ],
        ]);

        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective->id,
            'type' => 'investment',
            'name' => 'Fund Expedition (Food)',
            'attributes' => [
                'resource' => 'food',
                'amount' => 2000,
                'points_awarded' => 160,
            ],
            'bonuses' => [
                'race' => [
                    'gnome' => 1.15,
                    'dwarf' => 1.2,
                    'human' => 1.1,
                ],
                'tech' => [
                    'economics' => 1.15,
                    'construction' => 1.1,
                ],
            ],
        ]);

        // Exploration for finding ruins
        RaidObjectiveTactic::create([
            'raid_objective_id' => $objective->id,
            'type' => 'exploration',
            'name' => 'Search for Ruins',
            'attributes' => [
                'search_ruins' => [
                    'name' => 'Search Ruins',
                    'draftee_cost' => 300,
                    'morale_cost' => 1,
                    'points_awarded' => 150,
                ],
            ],
            'bonuses' => [
                'race' => ['halfling' => 1.2, 'elf' => 1.15],
                'tech' => ['cartography' => 1.25],
            ],
        ]);
    }
}
