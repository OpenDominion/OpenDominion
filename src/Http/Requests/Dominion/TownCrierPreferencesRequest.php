<?php

namespace OpenDominion\Http\Requests\Dominion;

use Illuminate\Validation\Rule;
use OpenDominion\Services\GameEventService;

class TownCrierPreferencesRequest extends AbstractDominionRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_keys(GameEventService::TOWN_CRIER_TYPES))],
            'dominion' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Select a default Town Crier event type.',
            'type.string' => 'Select a valid Town Crier event type.',
            'type.in' => 'Select a valid Town Crier event type.',
            'dominion.integer' => 'Select a valid dominion.',
            'dominion.min' => 'Select a valid dominion.',
        ];
    }
}
