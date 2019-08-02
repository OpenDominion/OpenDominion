<?php

namespace OpenDominion\Http\Requests\Dominion\API;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class InvadeCalculationRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            'target_dominion' => 'nullable|integer',
            'unit' => 'nullable|array',
        ];
    }
}
