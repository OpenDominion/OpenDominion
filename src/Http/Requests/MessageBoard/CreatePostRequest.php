<?php

namespace OpenDominion\Http\Requests\MessageBoard;

use OpenDominion\Http\Requests\AbstractRequest;

class CreatePostRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            'body' => 'required|string|max:20000',
        ];
    }
}
