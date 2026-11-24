<?php

namespace OpenDominion\Http\Requests\Staff\Administrator;

use OpenDominion\Http\Requests\AbstractRequest;

class SaveStorylineRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:10000'],
            'post_at' => ['nullable', 'date'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function messages(): array
    {
        return [
            'text.required' => 'Write the storyline text to post.',
            'post_at.date' => 'The post time must be a valid date and time.',
        ];
    }
}
