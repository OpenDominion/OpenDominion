<?php

namespace OpenDominion\Http\Controllers\Dominion;

use DB;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Realm;

class RealmController extends AbstractDominionController
{
    public function getRealm(Realm $realm = null)
    {
        /** @var LandCalculator $landCalculator */
        $landCalculator = app(LandCalculator::class);

        /** @var NetworthCalculator $networthCalculator */
        $networthCalculator = app(NetworthCalculator::class);

        if (($realm === null) || !$realm->exists) {
            $realm = $this->getSelectedDominion()->realm;
        }

        $dominions = $realm->dominions()/*->with(['race.units'])*/->orderBy('networth', 'desc')->get();

        // Todo: optimize this hacky hacky stuff
        $prevRealm = DB::table('realms')
            ->where('number', '<', $realm->number)
            ->orderBy('number', 'desc')
            ->limit(1)
            ->first();

        $nextRealm = DB::table('realms')
            ->where('number', '>', $realm->number)
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
        return redirect()->route('dominion.other.realm', $request->get('realm'));
    }
}
