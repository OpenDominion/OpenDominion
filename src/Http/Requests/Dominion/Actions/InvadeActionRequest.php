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
        return [
            'target_dominion' => 'required|integer',
            'unit1' => 'nullable|integer',
            'unit2' => 'nullable|integer',
            'unit3' => 'nullable|integer',
            'unit4' => 'nullable|integer',
        ];
    }
}
