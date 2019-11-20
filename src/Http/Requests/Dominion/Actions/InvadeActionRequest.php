<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class InvadeActionRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = ['target_dominion' => 'required|integer'];
        foreach ($this->unitHelper->getUnitTypes() as $unitType) {
            $rules[$unitType] = 'integer|nullable|min:0';
        }

        return $rules;
    }
}
