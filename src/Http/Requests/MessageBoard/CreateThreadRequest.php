<?php

namespace OpenDominion\Http\Requests\MessageBoard;

use OpenDominion\Http\Requests\AbstractRequest;

class CreateThreadRequest extends AbstractRequest
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
