<?php

namespace OpenDominion\Http\Requests\Dominion\Council;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class CreateThreadRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            'title' => 'required|string|max:80',
            'body' => 'required|string|max:3000',
        ];
    }
}
