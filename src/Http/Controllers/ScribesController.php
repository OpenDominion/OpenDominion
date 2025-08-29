<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Helpers\HeroHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Helpers\WonderHelper;
use OpenDominion\Models\Race;

class ScribesController extends AbstractController
{
    public function getOverview()
    {
        return view('pages.scribes.overview');
    }

    public function getRaces()
    {
        $races = collect(Race::where('playable', true)->orderBy('name')->get())->groupBy('alignment')->toArray();
        return view('pages.scribes.races', [
            'goodRaces' => $races['good'],
            'evilRaces' => $races['evil'],
            'legacy' => false,
        ]);
    }

    public function getLegacyRaces()
    {
        $races = collect(Race::where('playable', false)->orderBy('name')->get())->groupBy('alignment')->toArray();
        return view('pages.scribes.races', [
            'goodRaces' => $races['good'],
            'evilRaces' => $races['evil'],
            'legacy' => true,
        ]);
    }

    public function getRace(string $raceName)
    {
        $race = Race::where('key', $raceName)
            ->firstOrFail();

        return view('pages.scribes.race', [
            'landHelper' => app(LandHelper::class),
            'unitHelper' => app(UnitHelper::class),
            'raceHelper' => app(RaceHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'race' => $race,
        ]);
    }

    public function getAllRaces()
    {
        $races = Race::where('playable', true)->orderBy('name')->get();

        return view('pages.scribes.all-races', [
            'landHelper' => app(LandHelper::class),
            'unitHelper' => app(UnitHelper::class),
            'raceHelper' => app(RaceHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'races' => $races,
        ]);
    }

    public function getConstruction()
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

        return view('pages.scribes.construction', [
            'buildingTypeWithLandType' => $buildingTypeWithLandType,
            'buildingHelper' => $buildingHelper,
            'landHelper' => app(LandHelper::class),
        ]);
    }

    public function getEspionage()
    {
        return view('pages.scribes.espionage', [
            'espionageHelper' => app(EspionageHelper::class)
        ]);
    }

    public function getMagic()
    {
        return view('pages.scribes.magic', [
            'spellHelper' => app(SpellHelper::class)
        ]);
    }

    public function getTechs()
    {
        return view('pages.scribes.techs', [
            'techHelper' => app(TechHelper::class),
            'legacy' => false
        ]);
    }

    public function getLegacyTechs()
    {
        return view('pages.scribes.techs', [
            'techHelper' => app(TechHelper::class),
            'legacy' => true
        ]);
    }

    public function getHeroes()
    {
        return view('pages.scribes.heroes', [
            'heroCalculator' => app(HeroCalculator::class),
            'heroHelper' => app(HeroHelper::class)
        ]);
    }

    public function getWonders()
    {
        return view('pages.scribes.wonders', [
            'wonderHelper' => app(WonderHelper::class)
        ]);
    }
}
