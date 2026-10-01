<?php

namespace OpenDominion\Http\Requests\Staff\Administrator;

use Illuminate\Validation\Rule;
use OpenDominion\Helpers\RoundPerkHelper;
use OpenDominion\Http\Requests\AbstractRequest;

class SaveRoundPerkRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        $roundPerkHelper = app(RoundPerkHelper::class);

        return [
            'key' => ['required', 'string', Rule::in(array_keys($roundPerkHelper->getPerkTypes()))],
            'value' => ['required', 'string', 'max:191'],
            'alignment' => ['nullable', 'string', Rule::in($roundPerkHelper->getAlignments())],
            'from_day' => ['nullable', 'integer', 'min:1'],
            'until_day' => ['nullable', 'integer', 'min:1', Rule::when($this->filled('from_day'), 'gte:from_day')],
            'name' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:191'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function messages(): array
    {
        return [
            'key.in' => 'That perk type is not supported.',
            'alignment.in' => 'Alignment must be good, evil, or blank for everyone.',
            'until_day.gte' => 'The until day must be on or after the from day.',
        ];
    }
}
