<?php

use Illuminate\Database\Seeder;
use OpenDominion\Contracts\Council\ForumServiceContract;
use OpenDominion\Models\Race;
use OpenDominion\Models\RacePerk;
use OpenDominion\Models\RacePerkType;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Models\Unit;
use OpenDominion\Models\UnitPerkType;

class CoreDataSeeder extends Seeder
{
    protected $forum;
    private $roundLeagueIds = [];
    private $racePerkTypeIds = [];
    private $unitPerkTypeIds = [];
    private $raceIds = [];

    /**
     * CoreDataSeeder constructor.
     */
    public function __construct(ForumServiceContract $forum)
    {
        $this->forum = $forum;
    }


    public function run()
    {
        DB::beginTransaction();

        $this->createRoundLeagues();
        $this->createPerks();
        $this->createRaces();
        $this->createUnits();
        $this->createForums();

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

        foreach ($json->races as $raceData) {
            $race = Race::create([
                'name' => $raceData->name,
                'alignment' => $raceData->alignment,
                'home_land_type' => $raceData->home_land_type,
            ]);

            $this->raceIds[$race->name] = $race->id;

            if (isset($raceData->perks)) {
                foreach ($raceData->perks as $perkData) {
                    RacePerk::create([
                        'race_id' => $race->id,
                        'race_perk_type_id' => $this->racePerkTypeIds[$perkData->key],
                        'value' => $perkData->value,
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
            foreach ($unitRows as $unitData) {
                $data = [
                    'race_id' => $this->raceIds[$raceName],
                    'slot' => $unitData->slot,
                    'name' => $unitData->name,
                    'cost_platinum' => $unitData->cost->platinum,
                    'cost_ore' => $unitData->cost->ore,
                    'power_offense' => $unitData->power->offense,
                    'power_defense' => $unitData->power->defense,
                ];

                if (isset($unitData->need_boat)) {
                    $data += ['need_boat' => $unitData->need_boat];
                }

                if (isset($unitData->perk)) {
                    $data += [
                        'unit_perk_type_id' => $this->unitPerkTypeIds[$unitData->perk->key],
                        'unit_perk_type_values' => implode(',', $unitData->perk->values),
                    ];
                }

                $unit = Unit::create($data);
            }
        }
    }

    /**
     * Create the base forums.
     */
    protected function createForums()
    {
        $this->command->info('Creating forums');

        $this->forum->create('General', '#3c8dbc');
        $this->forum->create('Help', '#00a65a');
        $this->forum->create('Off-topic', '#f39c12');
    }
}
