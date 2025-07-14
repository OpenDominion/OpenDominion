<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class RaidActionRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [];
        $tactic = $this->route('tactic');

        if ($tactic->type !== 'hero' && $tactic->type !== 'invasion') {
            $rules['option'] = 'required|string';
        }

        // invasion
        for ($i = 1; $i <= 4; $i++) {
            $rules['unit.' . $i] = 'integer|nullable|min:0';
        }

        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function messages()
    {
        return [
            'option.required' => 'You must select an action to perform.',
            'unit.*.integer' => 'Unit amounts must be integers.',
            'unit.*.min' => 'Unit amounts must be non-negative.',
        ];
    }
}
