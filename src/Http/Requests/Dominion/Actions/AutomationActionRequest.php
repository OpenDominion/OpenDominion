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
            'action' => 'required|in:construct,daily_bonus,draft_rate,explore,rezone,spell,train',
            'key' => 'required_unless:action,draft_rate',
            'key2' => 'required_if:action,rezone',
            'amount' => 'required_unless:action,spell,action,daily_bonus|nullable|integer|max:99999',
        ];
    }

    public function messages(): array
    {
        return [
            'key.required_unless' => 'A selection is required for this action.',
            'key2.required_if' => 'A target selection is required for this action.',
            'amount.required_unless' => 'An amount is required for this action.',
        ];
    }
}
