<?php

use Illuminate\Database\Seeder;
use OpenDominion\Models\Race;
use OpenDominion\Models\RacePerk;
use OpenDominion\Models\RacePerkType;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Models\Unit;
use OpenDominion\Models\UnitPerkType;

class CoreDataSeeder extends Seeder
{
    private $roundLeagueIds = [];
    private $racePerkTypeIds = [];
    private $unitPerkTypeIds = [];
    private $raceIds = [];

    public function run()
    {
        DB::beginTransaction();

        $this->createRoundLeagues();
        $this->createPerks();
        $this->createRaces();
        $this->createUnits();

        DB::commit();
    }

    protected function createRoundLeagues()
    {
        $this->command->info('Creating round leagues');

        $json = json_decode(file_get_contents(base_path('app/data/round_leagues.json')));

        foreach ($json->round_leagues as $row) {
            $roundLeague = RoundLeague::create([
                'key' => $row->key,
                'description' => $row->description,
            ]);

            $this->roundLeagueIds[$roundLeague->key] = $roundLeague->id;
        }
    }

    protected function createPerks()
    {
        $this->command->info('Creating perks');

        $json = json_decode(file_get_contents(base_path('app/data/perks.json')));

        foreach ($json->race_perk_types as $row) {
            $racePerkType = RacePerkType::create([
                'key' => $row->key,
            ]);

            $this->racePerkTypeIds[$racePerkType->key] = $racePerkType->id;
        }

        foreach ($json->unit_perk_types as $row) {
            $unitPerkType = UnitPerkType::create([
                'key' => $row->key,
            ]);

            $this->unitPerkTypeIds[$unitPerkType->key] = $unitPerkType->id;
        }
    }

    protected function createRaces()
    {
        $this->command->info('Creating races');

        $json = json_decode(file_get_contents(base_path('app/data/races.json')));

        foreach ($json->races as $row) {
            $race = Race::create([
                'name' => $row->name,
                'alignment' => $row->alignment,
                'home_land_type' => $row->home_land_type,
            ]);

            $this->raceIds[$race->name] = $race->id;

            if (isset($race->perks)) {
                foreach ($race->perks as $perk) {
                    RacePerk::create([
                        'race_id' => $race->id,
                        'race_perk_type_id' => $this->racePerkTypeIds[$perk->key],
                        'value' => $perk->value,
                    ]);
                }
            }
        }
    }

    protected function createUnits()
    {
        $this->command->info('Creating units');

        $json = json_decode(file_get_contents(base_path('app/data/units.json')));

        foreach ($json->units as $raceName => $unitRows) {
            foreach ($unitRows as $row) {
                $data = [
                    'race_id' => $this->raceIds[$raceName],
                    'slot' => $row->slot,
                    'name' => $row->name,
                    'cost_platinum' => $row->cost->platinum,
                    'cost_ore' => $row->cost->ore,
                    'power_offense' => $row->power->offense,
                    'power_defense' => $row->power->defense,
                ];

                if (isset($row->perk)) {
                    $data += [
                        'unit_perk_type_id' => $this->unitPerkTypeIds[$row->perk->key],
                        'unit_perk_type_values' => implode(',', $row->perk->values),
                    ];
                }

                $unit = Unit::create($data);
            }
        }
    }
}
