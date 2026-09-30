<?php

namespace OpenDominion\Http\Requests\Staff\Administrator;

use OpenDominion\Http\Requests\AbstractRequest;
use OpenDominion\Services\Activity\OriginLookupSelectionService;

class PerformRoundOriginLookupsRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            'round' => 'required|integer|exists:rounds,id',
            'tier' => 'required|string|in:' . implode(',', OriginLookupSelectionService::TIERS),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function messages(): array
    {
        return [
            'round.required' => 'A round is required to perform lookups.',
            'round.exists' => 'The selected round does not exist.',
            'tier.required' => 'Select a tier to look up.',
            'tier.in' => 'The selected tier is not valid.',
        ];
    }
}
