<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class GovernmentActionRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            'appointee' => 'required|integer|exists:dominions,id',
            'role' => 'required|string'
        ];

        return $rules;
    }
}
