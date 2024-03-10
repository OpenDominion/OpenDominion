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
            'action' => 'required|in:construct,draft_rate,explore,spell,train',
            'key' => 'required_unless:action,draft_rate',
            'amount' => 'required_unless:action,spell|nullable|integer|max:99999',
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => 'A selection is required for this action.',
            'amount.required_unless' => 'An amount is required for this action.',
        ];
    }
}
