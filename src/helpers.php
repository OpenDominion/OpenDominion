<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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

if (!function_exists('clamp')) {
    /**
     * Clamps $current number between $min and $max.
     *
     * (tfw no generics)
     *
     * @param int|float $current
     * @param int|float $min
     * @param int|float $max
     * @return int|float
     */
    function clamp($current, $min, $max) {
        return max($min, min($max, $current));
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
        string $lastDelimiter = ', and '
    ): string {
        return str_replace_last($delimiter, $lastDelimiter, implode($delimiter, $stringParts));
    }
}

if (!function_exists('dominion_attr_display')) {
    /**
     * Returns a string suitable for display with prefix removed.
     *
     * @param string $attribute
     * @param float $value
     * @return string
     */
    function dominion_attr_display(string $attribute, float $value = 1): string {
        $pluralAttributeDisplay = [
            'prestige' => 'prestige',
            'morale' => 'morale',
            'spy_strength' => 'percent spy strength',
            'wizard_strength' => 'percent wizard strength',
            'resource_platinum' => 'platinum',
            'resource_food' => 'food',
            'resource_lumber' => 'lumber',
            'resource_mana' => 'mana',
            'resource_ore' => 'ore',
            'resource_tech' => 'research points',
            'land_water' => 'water',
        ];

        if (isset($pluralAttributeDisplay[$attribute])) {
            return $pluralAttributeDisplay[$attribute];
        } else {
            if (strpos($attribute, '_') !== false) {
                $stringParts = explode('_', $attribute);
                array_shift($stringParts);
                return str_plural(str_singular(implode(' ', $stringParts)), $value);
            } else {
                return str_plural(str_singular($attribute), $value);
            }
        }
    }
}

if (!function_exists('dominion_attr_sentence_from_array')) {
    /**
     * Generates a string from a multidimensional array.
     *
     * @param array $attrs
     * @return string
     */
    function dominion_attr_sentence_from_array(array $attrs, bool $simLog = true): string {
        $stringParts = [];
        foreach ($attrs as $key => $value) {
            $capitalize = false;
            $forcePlural = false;
            $forceSingular = false;
            if ($simLog) {
                if (Str::startsWith($key, 'military_unit_')) {
                    $forceSingular = true;
                    $key = str_replace('unit_', '', $key);
                } else {
                    $forcePlural = true;
                }
                if (!Str::startsWith($key, 'resource_')) {
                    $capitalize = true;
                }
            }
            $attributeDisplay = dominion_attr_display($key, $forcePlural ? 2 : ($forceSingular ? 1 : $value));
            $stringParts[] = sprintf('%s %s', $value, $capitalize ? ucwords($attributeDisplay) : $attributeDisplay);
        }
        return generate_sentence_from_array($stringParts, ', ', ', ');
    }
}

if (!function_exists('hours_until_next_action')) {
    /**
     * Calculate the time until the next action by tick number.
     *
     * @param array $aiConfig
     * @param int $tick
     * @return int
     */
    function hours_until_next_action(
        array $aiConfig,
        int $tick
    ): int {
        $hours = 24;
        foreach ($aiConfig as $key => $actions) {
            if ($hours == 24 || $tick - intval($key) > $hours) {
                $hours = intval($key) - $tick;
            }
        }
        return $hours;
    }
}

if (!function_exists('bonus_display')) {
    /**
     * Returns a string suitable for displaying a color-coded bonus as positive or negative.
     *
     * @param float $value
     * @param bool $positive
     * @return string
     */
    function bonus_display(float $value, bool $positive = true): string {
        $color = '';
        if ($positive) {
            if ($value < 0) {
                $color = 'text-red';
            } elseif ($value > 0) {
                $color = 'text-green';
            }
        } else {
            if ($value < 0) {
                $color = 'text-green';
            } elseif ($value > 0) {
                $color = 'text-red';
            }
        }
        return sprintf("<span class='{$color}'>%+.3f%%</span>", $value);
    }
}

if (!function_exists('class_method_display')) {
    /**
     * Returns an array with display information from a class method.
     *
     * @param class $class
     * @param string $method
     * @param Dominion $dominion
     * @param array $optionalArgs
     * @return array
     */
    function class_method_display($class, $method, $dominion, $optionalArgs = []): array {
        $parts = preg_split('/(?=[A-Z])/', str_replace('get', '', $method));
        $label = ucwords(implode(' ', $parts));
        $value = $class->$method($dominion, ...$optionalArgs);
        if (in_array('Modifier', $parts) || in_array('Multiplier', $parts)) {
            $value = (float) $value * 100;
            $display = number_format($value, 2) . '%';
        } elseif (in_array('Percentage', $parts)) {
            $display = number_format($value, 2) . '%';
        } else {
            $display = number_format($value);
        }
        return ['label' => $label, 'value' => $display];
    }
}

if (!function_exists('random_chance')) {
    $mockRandomChance = false;
    /**
     * Returns whether a random chance check succeeds.
     *
     * Used for the very few RNG checks in OpenDominion.
     *
     * @param float $chance Floating-point number between 0.0 and 1.0, representing 0% and 100%, respectively
     * @return bool
     * @throws Exception
     */
    function random_chance(float $chance): bool
    {
        global $mockRandomChance;
        if ($mockRandomChance === true || $mockRandomChance === false) {
            return $mockRandomChance;
        }

        return ((random_int(0, mt_getrandmax()) / mt_getrandmax()) <= $chance);
    }
}

if (!function_exists('random_distribution')) {
    /**
     * Returns a random value from a normal probability distribution.
     *
     * Uses the Box-Muller Transform method.
     *
     * @param float $mean
     * @param float $standard_deviation
     * @return float
     * @throws Exception
     */
    function random_distribution(float $mean, float $standard_deviation): float
    {
        $x = mt_rand()/mt_getrandmax();
        $y = mt_rand()/mt_getrandmax();
        return sqrt(-2 * log($x)) * cos(2 * pi() * $y) * $standard_deviation + $mean;
    }
}

if (!function_exists('skewed_distribution')) {
    /**
     * Returns a random value between min/max from a right-skewed probability distribution.
     *
     * @param float $min
     * @param float $max
     * @return float
     * @throws Exception
     */
    function skewed_distribution(float $min, float $max): float
    {
        $x = mt_rand()/mt_getrandmax();
        $y = mt_rand()/mt_getrandmax();
        return floor(abs($x - $y) * ($max - $min) + $min);
    }
}

if (!function_exists('random_choice_weighted')) {
    /**
     * Returns a random weighted selection from an array.
     *
     * @param array $choices Array with string keys and integer values for weighting
     * @return string
     * @throws Exception
     */
    function random_choice_weighted(array $choices): string
    {
        $totalWeight = array_sum($choices);
        $randomInt = random_int(0, $totalWeight);

        foreach ($choices as $choice => $weight) {
            $randomInt -= $weight;
            if ($randomInt < 0) {
                return $choice;
            }
        }

        return $choice;
    }
}

if (!function_exists('root_mean_square')) {
    /**
     * Returns the average of a list of elements weighed by squaring each value and rooting the result.
     *
     * @param array $values List of elements
     * @return float
     * @throws Exception
     */
    function root_mean_square(array $values): float
    {
        if (count($values) == 0) {
            return 0;
        }

        $sum = 0;
        foreach ($values as $value) {
            $sum += $value ** 2;
        }
        return sqrt($sum / count($values));
    }
}

if (!function_exists('error_function')) {
    /**
     * Gaussian error function
     *
     * https://github.com/tdebatty/php-stats/blob/master/src/webd/stats/Erf.php
     *
     * @param float $x
     * @return float
     * @throws Exception
     */
    function error_function(float $x): float
    {
        $t =1 / (1 + 0.5 * abs($x));
        $tau = $t * exp(
            - $x * $x
            - 1.26551223
            + 1.00002368 * $t
            + 0.37409196 * $t * $t
            + 0.09678418 * $t * $t * $t
            - 0.18628806 * $t * $t * $t * $t
            + 0.27886807 * $t * $t * $t * $t * $t
            - 1.13520398 * $t * $t * $t * $t * $t * $t
            + 1.48851587 * $t * $t * $t * $t * $t * $t * $t
            - 0.82215223 * $t * $t * $t * $t * $t * $t * $t * $t
            + 0.17087277 * $t * $t * $t * $t * $t * $t * $t * $t * $t
        );
        if ($x >= 0) {
            return 1 - $tau;
        } else {
            return $tau - 1;
        }
    }
}

if (!function_exists('number_string')) {
    /**
     * Generates a string from a number with number_format, and optionally an
     * explicit + sign prefix.
     *
     * @param int|float $number
     * @param int $numDecimals
     * @param bool $explicitPlusSign
     * @return string
     */
    function number_string($number, int $numDecimals = 0, bool $explicitPlusSign = false): string {
        $string = number_format($number, $numDecimals);

        if ($explicitPlusSign && $number > 0) {
            $string = "+{$string}";
        }

        return $string;
    }
}

if (!function_exists('format_percentage')) {
    /**
     * Format a non-zero value with a sibling containing the percentage of total.
     *
     * @param int|float $number
     * @param int|float $total
     * @return string
     */
    function format_percentage($number, $total = 0) {
        if ($number > 0 && $total > 0 && $number != $total) {
            return sprintf(
                '%s <small class="text-muted">(%s%%)</small>',
                number_format($number),
                number_format($number / $total * 100, 2)
            );
        }
        return number_format($number);
    }
}

if (!function_exists('format_string')) {
    /**
     * Format a string by replacing underscores with spaces and capitalizg each word.
     */
    function format_string($str) {
        return ucwords(str_replace('_', ' ', $str));
    }
}

if (!function_exists('rceil')) {
    /**
     * Floating-point safe version of ceil
     * Rounds to the nearest 10th digit before performing ceil
     *
     * @param float $val
     * @return int
     */
    function rceil(float $val): int {
        return ceil(round($val, 10));
    }
}

if (!function_exists('rfloor')) {
    /**
     * Floating-point safe version of floor
     * Rounds to the nearest 10th digit before performing floor
     *
     * @param float $val
     * @return int
     */
    function rfloor(float $val): int {
        return floor(round($val, 10));
    }
}
