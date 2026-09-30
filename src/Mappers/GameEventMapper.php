<?php

namespace OpenDominion\Mappers;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\RaidObjectiveTactic;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Wonder;
use stdClass;

/**
 * Maps game events to their public API representation. Stored event data is
 * mostly private (battle reports), so only what the Town Crier displays to
 * every player is exposed. Mirrors partials/dominion/game-event.blade.php.
 */
class GameEventMapper
{
    /**
     * Event types exposed through the public API. Anything else is withheld,
     * including sentient wonder attacks (wonder_invasion).
     */
    public const PUBLIC_TYPES = [
        'invasion',
        'war_declared',
        'war_canceled',
        'wonder_spawned',
        'wonder_attacked',
        'wonder_destroyed',
        'raid_attacked',
        'abandoned',
    ];

    /**
     * Relations the mapper reads, for eager loading via GameEvent::with().
     *
     * @return array<string, callable>
     */
    public function getEagerLoads(): array
    {
        return [
            'source' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    RoundWonder::class => ['wonder'],
                ]);
            },
            'target' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    RealmWar::class => ['sourceRealm', 'targetRealm'],
                    RoundWonder::class => ['wonder', 'realm'],
                ]);
            },
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     type: string,
     *     source_type: string|null,
     *     source_id: int|null,
     *     target_type: string|null,
     *     target_id: int|null,
     *     data: array<string, mixed>|stdClass,
     *     created_at: string|null
     * }
     */
    public function mapPublic(GameEvent $event): array
    {
        $data = $this->mapPublicData($event);

        return [
            'id' => (string) $event->id,
            'type' => $event->type,
            'source_type' => $this->getPublicMorphType($event->source_type),
            'source_id' => $event->source_id,
            'target_type' => $this->getPublicMorphType($event->target_type),
            'target_id' => $this->isNeutralWonderAttack($event) ? null : $event->target_id,
            'data' => empty($data) ? new stdClass() : $data,
            'created_at' => $event->created_at?->toIso8601ZuluString(),
        ];
    }

    /**
     * Converts a stored morph class into its public name,
     * e.g. "OpenDominion\Models\RealmWar" becomes "realm_war".
     */
    public function getPublicMorphType(?string $morphType): ?string
    {
        if ($morphType === null || $morphType === '') {
            return null;
        }

        return Str::snake(class_basename($morphType));
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapPublicData(GameEvent $event): array
    {
        return match ($event->type) {
            'invasion' => $this->mapInvasion($event),
            'war_declared' => $this->mapWar($event, 'start'),
            'war_canceled' => $this->mapWar($event, 'end'),
            'wonder_spawned' => $this->mapWonderSpawned($event),
            'wonder_attacked' => $this->mapWonderAttacked($event),
            'wonder_destroyed' => $this->mapWonderDestroyed($event),
            'raid_attacked' => $this->mapRaidAttacked($event),
            default => [],
        };
    }

    /**
     * land_gained includes the bonus land generated for the attacker on top
     * of what was conquered, so it can exceed land_lost.
     *
     * @return array{success: bool, land_lost: int, land_gained: int}
     */
    protected function mapInvasion(GameEvent $event): array
    {
        $success = (bool) Arr::get($event->data, 'result.success', false);
        $landConquered = (int) array_sum((array) Arr::get($event->data, 'attacker.landConquered', []));
        $landGenerated = (int) array_sum((array) Arr::get($event->data, 'attacker.landGenerated', []));

        return [
            'success' => $success,
            'land_lost' => $success ? $landConquered : 0,
            'land_gained' => $success ? (int) Arr::get($event->data, 'attacker.landGained', $landConquered + $landGenerated) : 0,
        ];
    }

    /**
     * @return array{source_realm: array{number: int, name: string|null}|null, target_realm: array{number: int, name: string|null}|null}
     */
    protected function mapWar(GameEvent $event, string $nameSuffix): array
    {
        $target = $event->target;

        if ($target instanceof RealmWar) {
            return [
                'source_realm' => $this->mapRealm($target->sourceRealm, $target->{'source_realm_name_' . $nameSuffix}),
                'target_realm' => $this->mapRealm($target->targetRealm, $target->{'target_realm_name_' . $nameSuffix}),
            ];
        }

        return [
            'source_realm' => $this->mapRealm($event->source instanceof Realm ? $event->source : null),
            'target_realm' => $this->mapRealm($target instanceof Realm ? $target : null),
        ];
    }

    /**
     * @return array{wonder: string|null}
     */
    protected function mapWonderSpawned(GameEvent $event): array
    {
        return [
            'wonder' => $event->source instanceof Wonder ? $event->source->name : null,
        ];
    }

    /**
     * The Town Crier does not reveal which neutral wonder was attacked.
     *
     * @return array{neutral: bool, wonder: string|null, realm_number: int|null}
     */
    protected function mapWonderAttacked(GameEvent $event): array
    {
        if ($this->isNeutralWonderAttack($event)) {
            return [
                'neutral' => true,
                'wonder' => null,
                'realm_number' => null,
            ];
        }

        $roundWonder = $event->target instanceof RoundWonder ? $event->target : null;

        return [
            'neutral' => false,
            'wonder' => $roundWonder?->wonder?->name,
            'realm_number' => $roundWonder?->realm?->number,
        ];
    }

    /**
     * @return array{wonder: string|null, rebuilt_by_realm: array{number: int, name: string|null}|null}
     */
    protected function mapWonderDestroyed(GameEvent $event): array
    {
        return [
            'wonder' => $this->getRoundWonderName($event->source),
            'rebuilt_by_realm' => $this->mapRealm($event->target instanceof Realm ? $event->target : null),
        ];
    }

    /**
     * @return array{tactic: string|null}
     */
    protected function mapRaidAttacked(GameEvent $event): array
    {
        return [
            'tactic' => $event->target instanceof RaidObjectiveTactic ? $event->target->name : null,
        ];
    }

    protected function isNeutralWonderAttack(GameEvent $event): bool
    {
        return $event->type === 'wonder_attacked' && (bool) Arr::get($event->data, 'wonder.neutral', true);
    }

    protected function getRoundWonderName(mixed $roundWonder): ?string
    {
        return $roundWonder instanceof RoundWonder ? $roundWonder->wonder?->name : null;
    }

    /**
     * @return array{number: int, name: string|null}|null
     */
    protected function mapRealm(?Realm $realm, ?string $nameOverride = null): ?array
    {
        if ($realm === null) {
            return null;
        }

        return [
            'number' => $realm->number,
            'name' => $nameOverride ?? $realm->name,
        ];
    }
}
