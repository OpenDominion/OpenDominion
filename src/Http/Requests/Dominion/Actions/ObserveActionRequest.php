<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class ObserveActionRequest extends AbstractDominionRequest
{
    public function all($keys = null)
    {
        $data = parent::all($keys);
        $data['target'] = $this->route('target');
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            'target' => 'required|integer|exists:dominions,id'
        ];
    }
}
