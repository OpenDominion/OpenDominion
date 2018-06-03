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
    public function getRealm(int $realmNumber = null)
    {
        $landCalculator = app(LandCalculator::class);
        $networthCalculator = app(NetworthCalculator::class);
        $protectionService = app(ProtectionService::class);

        $dominion = $this->getSelectedDominion();
        $round = $dominion->round;

        if ($realmNumber === null) {
            $realmNumber = (int)$dominion->realm->number;
        }

        $isOwnRealm = ($realmNumber === (int)$dominion->realm->number);

        // Eager load some relational data to save on SQL queries down the road in NetworthCalculator and
        // ProtectionService
        $with = [
            'dominions.race.units',
            'dominions.round',
        ];

        if ($isOwnRealm) {
            $with[] = 'dominions.user';
        }

        $realm = Realm::with($with)
            ->where([
                'round_id' => $round->id,
                'number' => $realmNumber,
            ])
            ->firstOrFail();

        // todo: still duplicate queries on this page. investigate later

        $dominions = $realm->dominions->sortByDesc(function (Dominion $dominion) use ($landCalculator) {
            return $landCalculator->getTotalLand($dominion);
        })->values();

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
            'nextRealm',
            'isOwnRealm'
        ));
    }

    public function postChangeRealm(Request $request) // todo: RealmChangeRequest, parse realm number to int
    {
        return redirect()->route('dominion.realm', (int)$request->get('realm'));
    }
}
