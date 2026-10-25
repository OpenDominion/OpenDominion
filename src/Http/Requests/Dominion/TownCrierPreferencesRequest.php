<?php

namespace OpenDominion\Http\Requests\Dominion;

use Illuminate\Validation\Rule;

class TownCrierPreferencesRequest extends AbstractDominionRequest
{
    public function rules(): array
    {
        return [
            'show_bot_attacks' => ['required', 'boolean'],
            'type' => ['nullable', Rule::in(['all', 'invasions', 'wars', 'wonders', 'raids'])],
            'dominion' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'show_bot_attacks.required' => 'Choose whether to show bot attacks.',
            'show_bot_attacks.boolean' => 'Choose whether to show bot attacks.',
            'type.in' => 'Select a valid Town Crier event type.',
            'dominion.integer' => 'Select a valid dominion.',
            'dominion.min' => 'Select a valid dominion.',
        ];
    }
}
