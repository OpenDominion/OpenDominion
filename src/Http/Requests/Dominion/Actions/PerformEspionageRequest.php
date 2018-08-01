<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class PerformEspionageRequest extends AbstractDominionRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            'operation' => 'required',
            'target_dominion' => 'required|integer|exists:dominions,id', // todo: not in own realm && not cross round?
        ];
    }
}
