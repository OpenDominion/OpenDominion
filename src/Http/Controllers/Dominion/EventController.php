<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\GameEvent;

class EventController
{
    public function index(string $eventType, string $eventUuid)
    {
        $event = GameEvent::query()
            ->with('source', 'target')
            ->where([
                'id' => $eventUuid,
                'type' => $eventType,
            ])
            ->firstOrFail();

        return view("pages.dominion.event.{$eventType}", [
            'event' => $event, // todo: compact()
            'unitHelper' => app(UnitHelper::class), // todo: only load if event->type == 'invasion'
        ]);
    }
}
