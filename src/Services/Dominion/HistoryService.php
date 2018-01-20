<?php

namespace OpenDominion\Services\Dominion;

use OpenDominion\Models\Dominion;

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
    public const EVENT_ACTION_CHANGE_DRAFT_RATE = 'change draft rate';
    public const EVENT_ACTION_TRAIN = 'train';
    public const EVENT_ACTION_RELEASE = 'release';
    public const EVENT_ACTION_CAST_SPELL = 'cast spell';

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

        $dominion->history()->create([
            'event' => $event,
            'delta' => $deltaAttributes,
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

        return $newAttributes->map(function ($value, $key) use ($oldAttributes) {
            return ($value - $oldAttributes->get($key));
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
        return collect(array_diff($dominion->getAttributes(), $dominion->getOriginal()))
            ->except([
                'id',
                'user_id',
                'round_id',
                'realm_id',
                'race_id',
                'name',
                'created_at',
                'updated_at',
            ])->keys()->toArray();
    }
}
