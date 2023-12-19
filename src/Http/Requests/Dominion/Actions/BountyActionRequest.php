<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;

class BountyActionRequest extends AbstractDominionRequest
{
    public function all($keys = null)
    {
        $data = parent::all($keys);
        $data['target'] = $this->route('target');
        $data['type'] = $this->route('type');
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        // TODO: Move to helper class
        $infoOps = [
            'clear_sight',
            'revelation',
            'castle_spy',
            'barracks_spy',
            'survey_dominion',
            'land_spy',
            'vision',
            'disclosure'
        ];
        $infoOpsString = join(',', $infoOps);

        return [
            'target' => 'required|integer|exists:dominions,id',
            'type' => "required|string|in:{$infoOpsString}"
        ];
    }
}
