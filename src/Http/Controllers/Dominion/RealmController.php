<?php

namespace OpenDominion\Http\Controllers\Dominion;

use DB;
use Illuminate\Http\Request;
use OpenDominion\Contracts\Calculators\Dominion\LandCalculator;
use OpenDominion\Contracts\Calculators\NetworthCalculator;
use OpenDominion\Contracts\Services\Dominion\ProtectionService;
use OpenDominion\Models\Realm;

class RealmController extends AbstractDominionController
{
    public function getRealm(Realm $realm = null)
    {
        $landCalculator = app(LandCalculator::class);
        $networthCalculator = app(NetworthCalculator::class);
        $protectionService = app(ProtectionService::class);

        if (($realm === null) || !$realm->exists) {
            $realm = $this->getSelectedDominion()->realm;
        }

        // todo: still duplicate queries on this page. investigate later

        // Eager load Realm relational data to save on SQL queries down the road in NetworthCalculator
        $realm->load(['dominions.race.units']); // todo: check if we can move this to inside NetworthCalculator

        $dominions = $realm->dominions()
            ->with(['race.units'])
            ->orderBy('networth', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        $round = $realm->round;

        // Todo: refactor this hacky hacky navigation stuff
        $prevRealm = DB::table('realms')
            ->where('round_id', $round->id)
            ->where('number', '<', $realm->number)
            ->orderBy('number', 'desc')
            ->limit(1)
            ->first();

        $nextRealm = DB::table('realms')
            ->where('round_id', $round->id)
            ->where('number', '>', $realm->number)
            ->orderBy('number', 'asc')
            ->limit(1)
            ->first();

        return view('pages.dominion.realm', compact(
            'landCalculator',
            'networthCalculator',
            'realm',
            'dominions',
            'prevRealm',
            'protectionService',
            'nextRealm'
        ));
    }

    public function postChangeRealm(Request $request)
    {
        $dominion = $this->getSelectedDominion();
        $realmNumber = $request->get('realm');

        $realm = Realm::where([
            'round_id' => $dominion->round_id,
            'number' => $realmNumber,
        ])->firstOrFail();

        return redirect()->route('dominion.realm', $realm);
    }
}
