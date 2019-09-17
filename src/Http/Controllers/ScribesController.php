<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Race;

class ScribesController extends AbstractController
{
    public function getIndex()
    {
        $races = collect(Race::orderBy('name')->get())->groupBy('alignment')->toArray();
        return view('pages.scribes.index', [
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
}
