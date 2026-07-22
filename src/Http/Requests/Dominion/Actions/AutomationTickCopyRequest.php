<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;
use OpenDominion\Services\Dominion\AutomationService;

class AutomationTickCopyRequest extends AbstractDominionRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'source_tick' => 'required|integer',
            'target_ticks' => 'required|array|min:1|max:' . AutomationService::MAX_SCHEDULE_HOURS,
            'target_ticks.*' => 'required|integer|distinct',
        ];
    }
}
