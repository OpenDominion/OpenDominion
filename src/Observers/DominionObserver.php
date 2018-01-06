<?php

namespace OpenDominion\Observers;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;

class DominionObserver
{
//    public function created(Dominion $dominion)
//    {
//        //
//    }
//
//    public function updated(Dominion $dominion)
//    {
//        dd([
//            'dominion updated',
//            $dominion,
//        ]);
//        //
//    }

    public function saved(Dominion $dominion)
    {
        if (!$dominion->isDirty()) {
            return;
        }

        $changedAttributeKeys = $this->getChangedAttributeKeys($dominion);

        if ($changedAttributeKeys->isEmpty()) {
            return;
        }

        $deltaAttributes = $this->getDeltaAttributes($dominion, $changedAttributeKeys->toArray());

        // find event type

        // create history record

        dd([
            $deltaAttributes,
            $changedAttributeKeys,
        ]);


        //

        dd($changedAttributeKeys->toArray());

        dd([
            'dominion saved',
            $dominion,
        ]);
    }

    /**
     * Returns the changed attributes of a dominion.
     *
     * @param Dominion $dominion
     * @return Collection
     */
    protected function getChangedAttributeKeys(Dominion $dominion): Collection
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
            ])->keys();
    }

    protected function getDeltaAttributes(Dominion $dominion, array $attributeKeys): Collection
    {
        // someone handy with array functions pls optimize/refactor
        $oldAttributes = collect($dominion->getOriginal())
            ->intersectByKeys(array_flip($attributeKeys));

        $newAttributes = collect($dominion->getAttributes())
            ->intersectByKeys(array_flip($attributeKeys));

        return $newAttributes->map(function ($value, $key) use ($oldAttributes) {
            return ($value - $oldAttributes->get($key));
        });
    }
}