<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Http\Controllers\Dominion\AbstractDominionController;

class DebugController extends AbstractDominionController
{
    protected static $selectedDominion;

    public function getIndex()
    {
        if (app()->environment() === 'production') {
            return redirect()->route('dominion.status');
        }

        static::$selectedDominion = $this->getSelectedDominion();

        return view('pages.dominion.debug', [
            'buildingCalculator' => app(BuildingCalculator::class),
            'constructionCalculator' => app(ConstructionCalculator::class),
            'explorationCalculator' => app(ExplorationCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'populationCalculator' => app(PopulationCalculator::class),
            'productionCalculator' => app(ProductionCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
        ]);
    }

    public static function printMethodValues($class, array $methods) {
        $return = '';

        foreach ($methods as $method) {
            $reflectionMethod = new \ReflectionMethod($class, $method);

            $label = implode(' ', preg_split('/(?=[A-Z])/', ltrim($method, 'get')));

            if ($reflectionMethod->getNumberOfParameters() === 1) {
                $value = $class->$method(static::$selectedDominion);

            } elseif ($reflectionMethod->getNumberOfParameters() === 0) {
                $value = $class->$method();
                $label = ('[REFACTOR] ' . $label);

            } else {
                throw new \Exception('welp');
            }

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
