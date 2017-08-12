<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class BankActionRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            'source' => 'required|different:target',
            'target' => 'required',
            'amount' => 'required|integer|min:1'
        ];
    }
}
