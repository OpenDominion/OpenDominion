<?php

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
