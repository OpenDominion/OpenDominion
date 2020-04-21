<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Database\Eloquent\Builder;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;

class EventController extends AbstractDominionController
{
    public function index(string $eventUuid)
    {
        $dominion = $this->getSelectedDominion();

        $query = GameEvent::query()
            ->with([
                'source',
                'source.race',
                'source.race.units',
                'source.race.units.perks',
                'source.realm',
                'target',
                'target.race',
                'target.race.units',
                'target.race.units.perks',
                'target.realm',
            ])
            ->where('id', $eventUuid);

        $event = $query->firstOrFail();

        if(!$this->canView($event, $dominion))
        {
            abort(404);
        }

        return view("pages.dominion.event.{$event->type}", [
            'event' => $event, // todo: compact()
            'unitHelper' => app(UnitHelper::class), // todo: only load if event->type == 'invasion'
            'militaryCalculator' => app(MilitaryCalculator::class), // todo: same thing here
        ]);
    }

    private function canView(GameEvent $event, Dominion $dominion): bool
    {
        if($dominion->user && $dominion->user->isStaff()) {
            return true;
        }

        if($event->source_type === Dominion::class && $event->source->realm_id == $dominion->realm->id) {
            return true;
        }

        if($event->target_type === Dominion::class && $event->target->realm_id == $dominion->realm->id) {
            return true;
        }

        if($event->source_type === Realm::class && $event->source->id == $dominion->realm->id) {
            return true;
        }

        if($event->target_type === Realm::class && $event->target->id == $dominion->realm->id) {
            return true;
        }

        return false;
    }
}
