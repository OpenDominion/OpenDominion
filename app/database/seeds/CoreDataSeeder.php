<?php

use Illuminate\Database\Seeder;
use OpenDominion\Models\Race;
use OpenDominion\Models\RacePerk;
use OpenDominion\Models\RacePerkType;
use OpenDominion\Models\RoundLeague;

class CoreDataSeeder extends Seeder
{
    public function run()
    {
        $this->createRacePerkTypes();
        $this->createRaces();
        $this->createRoundLeagues();
    }

    private function createRacePerkTypes()
    {
        $this->command->info('Creating race perk types');

        $racePerkTypeDefinitions = [
            'max_population',
            'population_growth',
            'platinum_production',
            'food_production',
            'lumber_production',
            'mana_production',
            'ore_production',
            'gem_production',
            'food_consumption',
            'defense',
            'offense',
            'spy_strength',
            'wizard_strength',
            'invest_bonus',
            'rezone_cost',
            'construction_cost',
        ];

        foreach ($racePerkTypeDefinitions as $racePerkTypeDefinition) {
            RacePerkType::create(['key' => $racePerkTypeDefinition]);
        }
    }

    private function createRaces()
    {
        $this->command->info('Creating races');

        $raceDefinitions = [
            [
                'data' => ['name' => 'Human', 'alignment' => 'good', 'home_land_type' => 'plain'],
                'perks' => ['food_production' => 5],
            ],
            [
                'data' => ['name' => 'Nomad', 'alignment' => 'evil', 'home_land_type' => 'plain'],
                'perks' => ['food_production' => 5],
            ]
        ];

        foreach ($raceDefinitions as $raceDefinition) {
            $race = Race::create($raceDefinition['data']);

            foreach ($raceDefinition['perks'] as $perk_type_key => $perk_value) {
                RacePerk::create([
                    'race_id' => $race->id,
                    'race_perk_type_id' => RacePerkType::where('key', $perk_type_key)->firstOrFail()->id,
                    'value' => (float)$perk_value,
                ]);
            }
        }
    }

    private function createRoundLeagues()
    {
        $this->command->info('Creating round leagues');

        RoundLeague::create(['key' => 'standard', 'description' => 'Standard']);
    }
}