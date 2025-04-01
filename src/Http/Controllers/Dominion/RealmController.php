<?php

namespace OpenDominion\Http\Controllers\Dominion;

use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\WonderCalculator;
use OpenDominion\Helpers\DiscordHelper;
use OpenDominion\Helpers\GovernmentHelper;
use OpenDominion\Helpers\RankingsHelper;
use OpenDominion\Helpers\WonderHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\RankingsService;

class RealmController extends AbstractDominionController
{
    public function getRealm(Request $request, int $realmNumber = null)
    {
        $discordHelper = app(DiscordHelper::class);
        $governmentHelper = app(GovernmentHelper::class);
        $governmentService = app(GovernmentService::class);
        $guardMembershipService = app(GuardMembershipService::class);
        $landCalculator = app(LandCalculator::class);
        $networthCalculator = app(NetworthCalculator::class);
        $protectionService = app(ProtectionService::class);
        $rankingsHelper = app(RankingsHelper::class);
        $rankingsService = app(RankingsService::class);
        $wonderCalculator = app(WonderCalculator::class);
        $wonderHelper = app(WonderHelper::class);

        $dominion = $this->getSelectedDominion();
        $round = $dominion->round;

        if (!$round->hasAssignedRealms()) {
            $request->session()->flash('alert-warning', 'You cannot access this page until realm assignment is finished.');
            return redirect()->back();
        }

        if ($realmNumber === null) {
            $realmNumber = (int)$dominion->realm->number;
        }

        $isOwnRealm = ($realmNumber === (int)$dominion->realm->number);

        if ($round->start_date > now() && !$isOwnRealm) {
            $request->session()->flash('alert-warning', 'You cannot view other realms before the round begins.');
            return redirect()->route('dominion.realm', (int)$dominion->realm->number);
        }

        // Eager load some relational data to save on SQL queries down the road in NetworthCalculator
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
                    }
                );
            })
            ->flatten();

        $rankings = $rankingsService->getTopRankedDominions($round);

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
            'discordHelper',
            'governmentHelper',
            'governmentService',
            'guardMembershipService',
            'landCalculator',
            'networthCalculator',
            'protectionService',
            'rankingsHelper',
            'wonderCalculator',
            'wonderHelper',
            'rankings',
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
