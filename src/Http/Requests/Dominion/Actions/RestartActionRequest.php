<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class RestartActionRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            'race' => 'required|integer|exists:races,id',
        ];
    }
}
