<?php

namespace OpenDominion\Services\Dominion;

use DateTime;
use LogicException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Activity\ActivityService;

class HistoryService
{
    public const EVENT_TICK = 'tick';
    public const EVENT_ACTION_DAILY_BONUS = 'daily bonus';
    public const EVENT_ACTION_EXPLORE = 'explore';
    public const EVENT_ACTION_CONSTRUCT = 'construct';
    public const EVENT_ACTION_DESTROY = 'destroy';
    public const EVENT_ACTION_REZONE = 'rezone';
    public const EVENT_ACTION_IMPROVE = 'improve';
    public const EVENT_ACTION_BANK = 'bank';
    public const EVENT_ACTION_TECH = 'tech';
    public const EVENT_ACTION_CHANGE_DRAFT_RATE = 'change draft rate';
    public const EVENT_ACTION_TRAIN = 'train';
    public const EVENT_ACTION_RELEASE = 'release';
    public const EVENT_ACTION_CAST_SPELL = 'cast spell';
    public const EVENT_ACTION_PERFORM_ESPIONAGE_OPERATION = 'perform espionage operation';
    public const EVENT_ACTION_INVADE = 'invade';
    public const EVENT_ACTION_JOIN_ROYAL_GUARD = 'join royal guard';
    public const EVENT_ACTION_JOIN_ELITE_GUARD = 'join elite guard';
    public const EVENT_ACTION_LEAVE_ROYAL_GUARD = 'leave royal guard';
    public const EVENT_ACTION_LEAVE_ELITE_GUARD = 'leave elite guard';
    public const EVENT_ACTION_RESTART = 'restart';
    public const EVENT_ACTION_WONDER_ATTACKED = 'wonder attacked';
    public const EVENT_ACTION_WONDER_DESTROYED = 'wonder destroyed';

    /**
     * Returns a cloned dominion instance with state at a certain time.
     *
     * @param Dominion $dominion
     * @param DateTime $at
     * @return Dominion
     */
    public function getDominionStateAtTime(Dominion $dominion, DateTime $at): Dominion
    {
        $clone = clone $dominion;

        // todo: add support for future state
        // if $at < now(), vvv
        // elseif $at > now(), where created_at <= $at && $clone->$attribute += $deltaValue;

        $history = $dominion->history()
            ->where('created_at', '>', $at)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($history->isEmpty()) {
            return $clone;
        }

        $history->each(function ($item, $key) use ($clone) {
            foreach ($item->delta as $attribute => $deltaValue) {
                $type = gettype($deltaValue);

                if ($type === 'bool') {
                    $clone->$attribute = !$deltaValue;
                } else {
                    $clone->$attribute -= $deltaValue;
                }
            }
        });

        return $clone;
    }

    /**
     * Records history changes in delta of a dominion.
     *
     * @param Dominion $dominion
     * @param array $deltaAttributes
     * @param string $event
     */
    public function record(Dominion $dominion, array $deltaAttributes, string $event)
    {
        if (empty($deltaAttributes)) {
            return;
        }

        $activityService = app(ActivityService::class);

        $dominion->history()->create([
            'event' => $event,
            'delta' => $deltaAttributes,
            'ip' => request()->ip(),
            'device' => $activityService->getDeviceString(),
        ]);
    }

    /**
     * Returns the attribute delta of a changed dominion.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getDeltaAttributes(Dominion $dominion): array
    {
        $attributeKeys = $this->getChangedAttributeKeys($dominion);

        // someone handy with array functions pls optimize/refactor
        $oldAttributes = collect($dominion->getOriginal())
            ->intersectByKeys(array_flip($attributeKeys));

        $newAttributes = collect($dominion->getAttributes())
            ->intersectByKeys(array_flip($attributeKeys));

        return $newAttributes->map(function ($value, $key) use ($dominion, $oldAttributes) {
            $attributeType = gettype($dominion->getAttribute($key));

            switch ($attributeType) {
                case 'boolean':
                    return (bool)$value;
                    break;

                case 'float':
                case 'double':
                    return ((float)$value - (float)$oldAttributes->get($key));
                    break;

                case 'integer':
                    return ((int)$value - (int)$oldAttributes->get($key));
                    break;

                default:
                    throw new LogicException("Unable to typecast attribute {$key} to type {$attributeType}");
            }
        })->toArray();
    }

    /**
     * Returns the changed attribute keys of a dominion.
     *
     * @param Dominion $dominion
     * @return array
     */
    protected function getChangedAttributeKeys(Dominion $dominion): array
    {
        return collect($dominion->getAttributes())
            ->diffAssoc(collect($dominion->getOriginal()))
            ->except([
                'id',
                'user_id',
                'pack_id',
                'round_id',
                'realm_id',
                'race_id',
                'name',
                'ruler_name',
                'peasants_last_hour',
                'created_at',
                'updated_at',
                'daily_platinum',
                'daily_land',
                'council_last_read',
                'forum_last_read',
                'royal_guard_active_at',
                'elite_guard_active_at',
                'last_tick_at',
                'locked_at',
                'monarchy_vote_for_dominion_id',
                'settings'
            ])->keys()->toArray();
    }
}
