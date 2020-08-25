<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class WonderActionRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            'action' => 'required|string',
            'target_wonder' => 'required|integer|exists:round_wonders,id'
        ];

        for ($i = 1; $i <= 4; $i++) {
            $rules['unit.' . $i] = 'integer|nullable|min:0';
        }

        return $rules;
    }
}
