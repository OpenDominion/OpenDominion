<?php

/**
 * Generates a string with conjuction from an array of strings
 *
 * @param array $stringParts
 * @return string
 */
function generate_sentence_from_array($stringParts):string {
    $delimiter = ', ';
    $lastDelimiter = ' and ';
    
    $string = implode($delimiter, $stringParts);
    $string = str_replace_last( $delimiter, $lastDelimiter, $string);
    
    return $string;
}
