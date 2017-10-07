<?php

namespace OpenDominion\Http\Controllers\Dominion;

use DB;
use Exception;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\ProtectionService;

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
            ->get();

        $dominions = $dominions->sortBy(function (Dominion $dominion) use ($landCalculator) {
            return $landCalculator->getTotalLand($dominion);
        }, SORT_REGULAR, true);

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

    public function postChangeRealm(Request $request) // todo: RealmChangeRequest, parse realm number to int
    {
        $dominion = $this->getSelectedDominion();
        $realmNumber = (int)$request->get('realm');

        try {
            $realm = Realm::where([
                'round_id' => $dominion->round_id,
                'number' => $realmNumber,
            ])->firstOrFail();

        } catch (Exception $e) {
            $numRealms = Realm::where('round_id', $dominion->round_id)->count();

            return redirect()->back()
                ->withErrors(["Realm with number $realmNumber not found. There are {$numRealms} realms in this round."]);
        }

        return redirect()->route('dominion.realm', $realm);
    }
}
