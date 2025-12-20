<?php

namespace OpenDominion\Http\Requests;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Http;

class TurnstileRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!config('turnstile.enabled')) {
            return true;
        }

        if (empty($value)) {
            return false;
        }

        $response = Http::asForm()->post(config('turnstile.verify_url'), [
            'secret' => config('turnstile.secret_key'),
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        if (!$response->successful()) {
            return false;
        }

        $result = $response->json();

        return $result['success'] ?? false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Please complete the CAPTCHA verification.';
    }
}
