<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class InvadeActionRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = ['target_dominion' => 'required|integer'];

        for ($i = 1; $i <= 4; $i++) {
            $rules['unit.' . $i] = 'integer|nullable|min:0';
        }

        return $rules;
    }
}
