<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\NetworthCalculator;

class DebugController extends AbstractController
{
    public function getIndex()
    {
        if (app()->environment() === 'production') {
            return redirect()->route('dominion.status');
        }

        $buildingCalculator = app()->make(BuildingCalculator::class);
        $landCalculator = app()->make(LandCalculator::class);
        $militaryCalculator = app()->make(MilitaryCalculator::class);
        $populationCalculator = app()->make(PopulationCalculator::class);
        $productionCalculator = app()->make(ProductionCalculator::class);
        $networthCalculator = app()->make(NetworthCalculator::class);

        $networthCalculator->initDependencies();

        return view('pages.dominion.debug', compact(
            'buildingCalculator',
            'landCalculator',
            'militaryCalculator',
            'populationCalculator',
            'productionCalculator',
            'networthCalculator'
        ));
    }

    public static function printMethodValues($class, array $methods) {
        $return = '';

        foreach ($methods as $method) {
            $label = implode(' ', preg_split('/(?=[A-Z])/', ltrim($method, 'get')));
            $value = $class->$method();
            $type = gettype($value);

            $return .= ($label . ' :');

            if (is_scalar($value)) {
                if (is_int($value)) {
                    $value = number_format($value);
                } elseif (is_float($value) || is_double($value)) {

                    if (substr($label, -10) === 'Multiplier') {
                        $value = number_format($value * 100 - 100, 2);
                        $value = ((($value < 0) ? '-' : '+') . $value . '%');
                    } else {
                        $value = number_format($value, 2);
                    }

                    if (substr($label, -10) === 'Percentage') {
                        $value .= '%';
                    }
                }

                $return .= (' <b>' . $value . '</b> (' . $type . ')');

            } elseif (is_array($value)) {
                $return .= ('<pre>' . print_r($value, true) . '</pre>');
            }

            $return .= '<br>';
        }

        return $return;
    }
}
