<?php

namespace OpenDominion\Services\Realm;

use DateTime;
use LogicException;
use OpenDominion\Models\Realm;

class HistoryService
{
    public const EVENT_ACTION_REALM_UPDATED = 'updated realm';

    /**
     * Records history changes in delta of a realm.
     *
     * @param Realm $realm
     * @param array $deltaAttributes
     * @param string $event
     */
    public function record(Realm $realm, array $deltaAttributes, string $event)
    {
        if (empty($deltaAttributes)) {
            return;
        }

        $realm->history()->create([
            'dominion_id' => $realm->monarch_dominion_id,
            'event' => $event,
            'delta' => $deltaAttributes,
        ]);
    }

    /**
     * Returns the attribute delta of a changed realm.
     *
     * @param Realm $realm
     * @return array
     */
    public function getDeltaAttributes(Realm $realm): array
    {
        $attributeKeys = $this->getChangedAttributeKeys($realm);

        // someone handy with array functions pls optimize/refactor
        $oldAttributes = collect($realm->getOriginal())
            ->intersectByKeys(array_flip($attributeKeys));

        $newAttributes = collect($realm->getAttributes())
            ->intersectByKeys(array_flip($attributeKeys));

        return $newAttributes->map(function ($value, $key) use ($realm, $oldAttributes) {
            $attributeType = gettype($realm->getAttribute($key));

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
                    return (string)$oldAttributes->get($key) . ' > ' . (string)$value;
                    break;
            }
        })->toArray();
    }

    /**
     * Returns the changed attribute keys of a realm.
     *
     * @param Realm $realm
     * @return array
     */
    protected function getChangedAttributeKeys(Realm $realm): array
    {
        return collect($realm->getAttributes())
            ->diffAssoc(collect($realm->getOriginal()))
            ->except([
                'id',
                'round_id',
                'monarch_dominion_id',
                'alignment',
                'number',
                'created_at',
                'updated_at',
            ])->keys()->toArray();
    }
}
