<?php

namespace OpenDominion\Http\Requests\Dominion\Council;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class CreatePostRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            'body' => 'required|string|max:3000',
        ];
    }
}
