<?php

namespace OpenDominion\Http\Requests\Staff\Administrator;

use Illuminate\Validation\Rule;
use OpenDominion\Http\Requests\AbstractRequest;

class PerformOriginLookupRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            'ip_address' => [
                'required',
                'ip',
                Rule::exists('user_origins', 'ip_address')->where('user_id', $this->route('user')->id),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function messages(): array
    {
        return [
            'ip_address.required' => 'An IP address is required to perform a lookup.',
            'ip_address.ip' => 'The IP address is not valid.',
            'ip_address.exists' => 'That IP address has not been recorded for this user.',
        ];
    }
}
