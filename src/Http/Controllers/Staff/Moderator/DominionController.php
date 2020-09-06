<?php

namespace OpenDominion\Http\Controllers\Staff\Moderator;

use Auth;
use Carbon\Carbon;
use DateInterval;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Dominion\History;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\InfoOp;
use OpenDominion\Models\Round;
use OpenDominion\Models\UserActivity;
use OpenDominion\Services\Activity\ActivityEvent;
use OpenDominion\Services\Activity\ActivityService;
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

        $userLogins = UserActivity::query()
            ->where('user_id', '=', $dominion->user_id)
            ->where('created_at', '>', $dominion->round->created_at)
            ->where('created_at', '<', $dominion->round->end_date)
            ->whereIn('key', ['user.login', 'user.logout'])
            ->whereNotIn('ip', ['', '127.0.0.1'])
            ->count();

        $userIps = UserActivity::select('ip')
            ->where('user_id', '=', $dominion->user_id)
            ->where('created_at', '>', $dominion->round->created_at)
            ->where('created_at', '<', $dominion->round->end_date)
            ->whereIn('key', ['user.login', 'user.logout'])
            ->whereNotIn('ip', ['', '127.0.0.1'])
            ->distinct('ip')
            ->pluck('ip');

        $historyIps = $dominion->history()
            ->where('event', '!=', 'tick')
            ->where('event', '!=', 'invade')
            ->where('delta', 'NOT LIKE', '%source_dominion_id%')
            ->whereNotIn('ip', ['', '127.0.0.1'])
            ->groupBy('ip')
            ->pluck('ip');

        $ipsUsedCount = $userIps->merge($historyIps)->unique()->count();

        $otherUserLogins = UserActivity::query()
            ->where('created_at', '>', $dominion->round->created_at)
            ->where('created_at', '<', $dominion->round->end_date)
            ->whereIn('ip', $userIps->merge($historyIps)->unique())
            ->whereIn('key', ['user.login', 'user.logout'])
            ->distinct('user_id')
            ->pluck('user_id');

        $otherDominionsHistory = History::query()
            ->where('created_at', '>', $dominion->round->created_at)
            ->where('created_at', '<', $dominion->round->end_date)
            ->where('event', '!=', 'tick')
            ->where('event', '!=', 'invade')
            ->where('delta', 'NOT LIKE', '%source_dominion_id%')
            ->whereIn('ip', $userIps->merge($historyIps)->unique())
            ->distinct('dominion_id')
            ->pluck('dominion_id');

        $otherUsersHistory = Dominion::query()
            ->whereIn('id', $otherDominionsHistory)
            ->pluck('user_id');

        $otherUserCount = $otherUserLogins->merge($otherUsersHistory)->unique()->count();

        return view('pages.staff.moderator.dominions.show', [
            'dominion' => $dominion,
            'gameEvents' => $gameEvents,
            'userLogins' => $userLogins,
            'ipsUsedCount' => $ipsUsedCount,
            'otherUserCount' => $otherUserCount
        ]);
    }

    public function showGameEvent(Dominion $dominion, GameEvent $gameEvent, Request $request)
    {
        // Save to Audit Log
        $activityService = app(ActivityService::class);
        $user = Auth::user();
        $event = new ActivityEvent('staff.audit.invasion', ActivityEvent::STATUS_INFO, ['gameEvent' => $gameEvent->id]);
        $activityService->recordActivity($user, $event);

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

    public function showUserActivity(Dominion $dominion, Request $request)
    {
        $selectedDominionId = $request->input('dominion') ?? -1;

        if ($selectedDominionId == -1) {
            // Save to Audit Log
            $activityService = app(ActivityService::class);
            $user = Auth::user();
            $event = new ActivityEvent('staff.audit.activity', ActivityEvent::STATUS_INFO, ['dominion' => $dominion->id]);
            $activityService->recordActivity($user, $event);
        }

        $userIps = UserActivity::select('ip')
            ->where('user_id', '=', $dominion->user_id)
            ->where('created_at', '>', $dominion->round->created_at)
            ->where('created_at', '<', $dominion->round->end_date)
            ->whereIn('key', ['user.login', 'user.logout'])
            ->whereNotIn('ip', ['', '127.0.0.1'])
            ->distinct('ip')
            ->pluck('ip');

        $sharedUserActivity = UserActivity::query()
            ->where('created_at', '>', $dominion->round->created_at)
            ->where('created_at', '<', $dominion->round->end_date)
            ->whereIn('ip', $userIps)
            ->whereIn('key', ['user.login', 'user.logout'])
            ->orderByDesc('created_at')
            ->get();

        $sharedDominions = Dominion::query()
            ->where('round_id', $dominion->round_id)
            ->whereIn('user_id', $sharedUserActivity->pluck('user_id'))
            ->get()
            ->keyBy('user_id');

        // TODO: dominion_history ips

        return view('pages.staff.moderator.dominions.showUserActivity', [
            'selectedDominionId' => $selectedDominionId,
            'dominion' => $dominion,
            'sharedDominions' => $sharedDominions,
            'sharedUserActivity' => $sharedUserActivity,
        ]);
    }

    public function lockDominion(Dominion $dominion, Request $request)
    {
        $dominion->locked_at = now();
        $dominion->save();

        // Save to Audit Log
        $activityService = app(ActivityService::class);
        $user = Auth::user();
        $event = new ActivityEvent('staff.audit.lock', ActivityEvent::STATUS_INFO, ['dominion' => $dominion->id]);
        $activityService->recordActivity($user, $event);

        $request->session()->flash('alert-success', 'This dominion has been locked.');
        return redirect()->back();
    }

    public function unlockDominion(Dominion $dominion, Request $request)
    {
        $dominion->locked_at = null;
        $dominion->save();

        // Save to Audit Log
        $activityService = app(ActivityService::class);
        $user = Auth::user();
        $event = new ActivityEvent('staff.audit.unlock', ActivityEvent::STATUS_INFO, ['dominion' => $dominion->id]);
        $activityService->recordActivity($user, $event);

        $request->session()->flash('alert-success', 'This dominion has been unlocked.');
        return redirect()->back();
    }
}
