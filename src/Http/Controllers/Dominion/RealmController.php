<?php

namespace OpenDominion\Http\Controllers\Dominion;

use DB;
use Illuminate\Http\Request;
use OpenDominion\Contracts\Calculators\Dominion\LandCalculator;
use OpenDominion\Contracts\Calculators\NetworthCalculator;
use OpenDominion\Models\Realm;

class RealmController extends AbstractDominionController
{
    public function getRealm(Realm $realm = null)
    {
        $landCalculator = app(LandCalculator::class);
        $networthCalculator = app(NetworthCalculator::class);

        if (($realm === null) || !$realm->exists) {
            $realm = $this->getSelectedDominion()->realm;
        }

        $dominions = $realm->dominions()/*->with(['race.units'])*/->orderBy('networth', 'desc')->get();

        // Todo: refactor this hacky hacky navigation stuff
        $prevRealm = DB::table('realms')
            ->where('number', '<', $realm->number)
//            ->where('realm_id', $realm->id)
            ->orderBy('number', 'desc')
            ->limit(1)
            ->first();

        $nextRealm = DB::table('realms')
            ->where('number', '>', $realm->number)
//            ->where('realm_id', $realm->id)
            ->orderBy('number')
            ->limit(1)
            ->first();

        return view('pages.dominion.realm', compact(
            'landCalculator',
            'networthCalculator',
            'realm',
            'dominions',
            'prevRealm',
            'nextRealm'
        ));
    }

    public function postChangeRealm(Request $request)
    {
        return redirect()->route('dominion.realm', $request->get('realm'));
    }
}
