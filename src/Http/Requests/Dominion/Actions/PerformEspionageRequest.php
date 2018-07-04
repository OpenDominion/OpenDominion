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
            'target' => 'integer|exists:dominions', // todo: not in own realm && not cross round?
        ];
    }
}
