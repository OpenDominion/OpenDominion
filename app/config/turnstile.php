<?php

return [
    /*
     * Cloudflare Turnstile site key
     * Get yours at: https://dash.cloudflare.com/
     */
    'site_key' => env('TURNSTILE_SITE_KEY', ''),

    /*
     * Cloudflare Turnstile secret key
     */
    'secret_key' => env('TURNSTILE_SECRET_KEY', ''),

    /*
     * Enable or disable Turnstile verification
     */
    'enabled' => env('TURNSTILE_ENABLED', true),

    /*
     * Verification endpoint
     */
    'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
];
