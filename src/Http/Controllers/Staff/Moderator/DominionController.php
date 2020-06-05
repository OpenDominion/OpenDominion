<?php

namespace OpenDominion\Http\Controllers\Staff\Moderator;

use Carbon\Carbon;
use DateInterval;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Services\GameEventService;

class DominionController extends AbstractController
{
    public function index()
    {
        $dominions = Dominion::with(['round'])->get();

        return view('pages.staff.moderator.dominions.index', [
            'dominions' => $dominions,
            'landCalculator' => app(LandCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
        ]);
    }

    public function show(Dominion $dominion)
    {
        $gameEventService = app(GameEventService::class);

        $gameEvents = $gameEventService->getGameEventsForDominion($dominion);

        return view('pages.staff.moderator.dominions.show', [
            'dominion' => $dominion,
            'gameEvents' => $gameEvents
        ]);
    }

    public function showGameEvent(Dominion $dominion, GameEvent $gameEvent)
    {
        $timeOfEvent = Carbon::parse($gameEvent->created_at);
        $createdAfter = Carbon::parse($gameEvent->created_at)->subDay();
        $infoOps = $dominion->realm->infoOps()
            ->where('target_dominion_id', '=', $gameEvent->target_id)
            ->whereBetween('created_at', [$createdAfter, $timeOfEvent])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.staff.moderator.dominions.showGameEvent', [
            'dominion' => $dominion,
            'gameEvent' => $gameEvent,
            'infoOps' => $infoOps
        ]);
    }
}
