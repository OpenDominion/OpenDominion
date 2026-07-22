<?php

namespace OpenDominion\Http\Requests\Dominion\Actions;

use OpenDominion\Http\Requests\Dominion\AbstractDominionRequest;
use OpenDominion\Services\Dominion\AutomationService;

class AutomationTemplateRequest extends AbstractDominionRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'operation' => 'required|in:save,load,delete',
            'slot' => 'required|integer|min:0|max:' . (AutomationService::MAX_TEMPLATE_SLOTS - 1),
            'name' => 'required_if:operation,save|nullable|string|max:32',
            'mode' => 'required_if:operation,load|nullable|in:replace,open',
        ];
    }
}
