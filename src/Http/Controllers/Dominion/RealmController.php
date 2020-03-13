<?php

namespace OpenDominion\Http\Controllers\Dominion;

use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\ProtectionService;

class RealmController extends AbstractDominionController
{
    public function getRealm(Request $request, int $realmNumber = null)
    {
        $governmentService = app(GovernmentService::class);
        $guardMembershipService = app(GuardMembershipService::class);
        $landCalculator = app(LandCalculator::class);
        $networthCalculator = app(NetworthCalculator::class);
        $protectionService = app(ProtectionService::class);

        $dominion = $this->getSelectedDominion();
        $round = $dominion->round;

        if ($realmNumber === null) {
            $realmNumber = (int)$dominion->realm->number;
        }

        $isOwnRealm = ($realmNumber === (int)$dominion->realm->number);

        if (!$round->hasStarted() && !$isOwnRealm) {
            $request->session()->flash(
                'alert-warning',
                'You cannot view other realms before the round has started.'
            );
            return redirect()->route('dominion.realm', (int)$dominion->realm->number);
        }

        // Eager load some relational data to save on SQL queries down the road in NetworthCalculator and
        // ProtectionService
        $with = [
            'dominions.queues',
            'dominions.race',
            'dominions.race.units',
            'dominions.race.units.perks',
            'dominions.realm',
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

        $dominions = $realm->dominions
            ->groupBy(static function (Dominion $dominion) use ($landCalculator) {
                return $landCalculator->getTotalLand($dominion);
            })
            ->sortKeysDesc()
            ->map(static function (Collection $collection) use ($networthCalculator) {
                return $collection->sortByDesc(
                    static function (Dominion $dominion) use ($networthCalculator) {
                        return $networthCalculator->getDominionNetworth($dominion);
                    });
            })
            ->flatten();

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
            ->orderBy('number')
            ->limit(1)
            ->first();

        $realmCount = DB::table('realms')
            ->where('round_id', $round->id)
            ->count();

        return view('pages.dominion.realm', compact(
            'governmentService',
            'guardMembershipService',
            'landCalculator',
            'networthCalculator',
            'protectionService',
            'realm',
            'round',
            'dominions',
            'prevRealm',
            'nextRealm',
            'isOwnRealm',
            'realmCount'
        ));
    }

    public function postChangeRealm(Request $request) // todo: RealmChangeRequest, parse realm number to int
    {
        return redirect()->route('dominion.realm', (int)$request->get('realm'));
    }
}
