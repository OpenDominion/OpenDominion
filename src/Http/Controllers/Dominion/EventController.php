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

        if (!$dominion->user->isStaff()) {
            $query->where(static function (Builder $query) use ($dominion) {
                $query
                    ->orWhere(static function (Builder $query) use ($dominion) {
                        $query->where('source_type', Dominion::class)
                            ->where('source_id', $dominion->id);
                    })
                    ->orWhere(static function (Builder $query) use ($dominion) {
                        $query->where('target_type', Dominion::class)
                            ->where('target_id', $dominion->id);
                    })
                    ->orWhere(static function (Builder $query) use ($dominion) {
                        $query->where('source_type', Realm::class)
                            ->where('source_id', $dominion->realm->id);
                    })
                    ->orWhere(static function (Builder $query) use ($dominion) {
                        $query->where('target_type', Realm::class)
                            ->where('target_id', $dominion->realm->id);
                    });
            });
        }

        $event = $query->firstOrFail();

        return view("pages.dominion.event.{$event->type}", [
            'event' => $event, // todo: compact()
            'unitHelper' => app(UnitHelper::class), // todo: only load if event->type == 'invasion'
            'militaryCalculator' => app(MilitaryCalculator::class), // todo: same thing here
        ]);
    }
}
