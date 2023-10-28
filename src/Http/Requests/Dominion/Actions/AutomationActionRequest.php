<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class AutomationActionRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            'tick' => 'required|integer',
            'action' => 'required|in:train,spell',
            'key' => 'required',
            'amount' => 'required_unless:action,spell|nullable|integer',
        ];
    }
}
