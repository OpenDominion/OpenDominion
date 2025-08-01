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
            'unit.*.integer' => 'Unit amounts must be integers.',
            'unit.*.min' => 'Unit amounts must be non-negative.',
        ];
    }
}
