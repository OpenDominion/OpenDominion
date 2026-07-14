<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;
use OpenDominion\Services\Dominion\AutomationService;

class AutomationReorderRequest extends AbstractDominionRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'tick' => 'required|integer',
            'key' => 'required|integer|min:0|max:' . (AutomationService::MAX_ACTIONS_PER_TICK - 1),
            'target_key' => 'required|integer|min:0|max:' . (AutomationService::MAX_ACTIONS_PER_TICK - 1),
        ];
    }
}
