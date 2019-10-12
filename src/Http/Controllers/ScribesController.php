<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Race;

class ScribesController extends AbstractController
{
    public function getRaces()
    {

        $races = collect(Race::orderBy('name')->get())->groupBy('alignment')->toArray();
        return view('pages.scribes.races', [
            'goodRaces' => $races['good'],
            'evilRaces' => $races['evil'],
        ]);
    }

    public function getRace(string $raceName)
    {
        $raceName = ucwords(str_replace('-', ' ', $raceName));

        $race = Race::where('name', $raceName)
            ->firstOrFail();

        return view('pages.scribes.race', [
            'landHelper' => app(LandHelper::class),
            'unitHelper' => app(UnitHelper::class),
            'raceHelper' => app(RaceHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'race' => $race,
        ]);
    }

    public function getBuildings()
    {
        $buildingHelper = app(BuildingHelper::class);

        $buildingTypesPerLandType = $buildingHelper->getBuildingTypesByRace();
        $buildingTypeWithLandType = [];
        foreach ($buildingTypesPerLandType as $landType => $buildingTypes) {
            foreach($buildingTypes as $buildingType) {
                $buildingTypeWithLandType[$buildingType] = $landType;
            }
        }

        $buildingTypeWithLandType['home'] = null;

        ksort($buildingTypeWithLandType);

        return view('pages.scribes.buildings', [
            'buildingTypeWithLandType' => $buildingTypeWithLandType,
            'buildingHelper' => $buildingHelper,
            'landHelper' => app(LandHelper::class),
        ]);
    }
}
