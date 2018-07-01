<?php

use Illuminate\Support\Carbon;

if (!function_exists('carbon')) {
    /**
     * Carbon helper function.
     *
     * @see https://github.com/laravel/framework/pull/21660#issuecomment-338359149
     *
     * @param mixed ...$params
     * @return Carbon
     */
    function carbon(...$params)
    {
        if (!$params) {
            return now();
        }

        if ($params[0] instanceof DateTime) {
            return Carbon::instance($params[0]);
        }

        if (is_numeric($params[0]) && ((string)(int)$params[0] === (string)$params[0])) {
            return Carbon::createFromTimestamp(...$params);
        }

        return Carbon::parse(...$params);
    }
}

if (!function_exists('generate_sentence_from_array')) {
    /**
     * Generates a string with conjunction from an array of strings.
     *
     * @param array $stringParts
     * @param string $delimiter
     * @param string $lastDelimiter
     * @return string
     */
    function generate_sentence_from_array(
        array $stringParts,
        string $delimiter = ', ',
        string $lastDelimiter = ' and '
    ): string {
        return str_replace_last($delimiter, $lastDelimiter, implode($delimiter, $stringParts));
    }
}

if (!function_exists('random_chance')) {
    /**
     * Returns whether a random chance check succeeds.
     *
     * Used for the very few RNG checks in OpenDominion.
     *
     * @param float $chance Floating-point number between 0.0 and 1.0, representing 0% and 100%, respectively
     * @return bool
     */
    function random_chance(float $chance): bool
    {
        return ((random_int(0, mt_getrandmax()) / mt_getrandmax()) <= $chance);
    }
}
