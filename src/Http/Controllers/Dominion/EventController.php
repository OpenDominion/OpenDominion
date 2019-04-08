<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\GameEvent;

class EventController
{
    public function index(string $eventUuid)
    {
        $event = GameEvent::query()
            ->with('source', 'target')
            ->where('id', $eventUuid)
            ->firstOrFail();

        return view("pages.dominion.event.{$event->type}", [
            'event' => $event, // todo: compact()
            'unitHelper' => app(UnitHelper::class), // todo: only load if event->type == 'invasion'
            'militaryCalculator' => app(MilitaryCalculator::class), // todo: same thing here
        ]);
    }
}
