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
