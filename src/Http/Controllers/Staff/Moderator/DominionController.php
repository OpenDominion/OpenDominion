<?php

namespace OpenDominion\Http\Controllers\Staff\Moderator;

use Carbon\Carbon;
use DateInterval;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\InfoOp;
use OpenDominion\Models\Round;
use OpenDominion\Models\UserActivity;
use OpenDominion\Services\GameEventService;

class DominionController extends AbstractController
{
    public function index(Request $request)
    {
        $rounds = Round::all()->sortByDesc('start_date');

        $selectedRound = $request->input('round');
        if ($selectedRound) {
            $round = Round::findOrFail($selectedRound);
        } else {
            $round = $rounds->first();
        }

        $dominions = Dominion::with(['round'])->where('round_id', $round->id)->get();

        return view('pages.staff.moderator.dominions.index', [
            'round' => $round,
            'rounds' => $rounds,
            'dominions' => $dominions,
            'landCalculator' => app(LandCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
        ]);
    }

    public function show(Dominion $dominion)
    {
        $gameEventService = app(GameEventService::class);

        $gameEvents = $gameEventService->getGameEventsForDominion($dominion);

        $userIps = UserActivity::select('ip')
            ->where('created_at', '>', $dominion->round->start_date)
            ->where('user_id', '=', $dominion->user_id)
            ->distinct('ip')
            ->get();

        $otherUserCount = UserActivity::query()
            ->where('created_at', '>', $dominion->round->start_date)
            ->whereIn('ip', $userIps)
            ->distinct('user_id')
            ->count('user_id');

        $ipsUsedCount = $userIps->count();

        return view('pages.staff.moderator.dominions.show', [
            'dominion' => $dominion,
            'gameEvents' => $gameEvents,
            'ipsUsedCount' => $ipsUsedCount,
            'otherUserCount' => $otherUserCount
        ]);
    }

    public function showGameEvent(Dominion $dominion, GameEvent $gameEvent, Request $request)
    {
        $timeOfEvent = Carbon::parse($gameEvent->created_at);
        $infoOps = InfoOp::where('target_dominion_id', '=', $gameEvent->target_id)
            ->where('created_at', '<', $timeOfEvent)
            ->orderBy('created_at', 'desc')
            ->get();

        $realmNumbers = $infoOps->map(function ($infoOp) { return $infoOp->sourceDominion->realm->number;})->unique()->sort();

        $selectedRealmNumber = $request->input('realm');
        if($selectedRealmNumber) {
            $infoOps = $infoOps->filter(function ($infoOp) use ($selectedRealmNumber) {
                return $infoOp->sourceDominion->realm->number == $selectedRealmNumber;
            });
        }

        $lastDay = Carbon::parse($gameEvent->created_at)->subDay();
        return view('pages.staff.moderator.dominions.showGameEvent', [
            'realmNumbers' => $realmNumbers,
            'selectedRealmNumber' => $selectedRealmNumber ?? -1,
            'dominion' => $dominion,
            'gameEvent' => $gameEvent,
            'infoOps' => $infoOps,
            'lastDay' => $lastDay
        ]);
    }
}
