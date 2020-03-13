<?php

namespace OpenDominion\Http\Requests\Dominion\Forum;

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
            'body' => 'required|string|max:20000',
        ];
    }
}
