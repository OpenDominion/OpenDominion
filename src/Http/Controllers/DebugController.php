<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Http\Controllers\Dominion\AbstractDominionController;
use OpenDominion\Services\Dominion\GuardMembershipService;

class DebugController extends AbstractDominionController
{
    protected static $selectedDominion;

    public function getIndex()
    {
        static::$selectedDominion = $this->getSelectedDominion();

        return view('pages.dominion.debug', [
            'networthCalculator' => app(NetworthCalculator::class),
            'buildingCalculator' => app(BuildingCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'populationCalculator' => app(PopulationCalculator::class),
            'productionCalculator' => app(ProductionCalculator::class),
//            'bankingCalculator' => app(BankingCalculator::class),
            'constructionCalculator' => app(ConstructionCalculator::class),
            'explorationCalculator' => app(ExplorationCalculator::class),
            'rezoningCalculator'  => app(RezoningCalculator::class),
            'trainingCalculator'  => app(TrainingCalculator::class),
            'guardMembershipService' => app(GuardMembershipService::class),
        ]);
    }

    public function getDump()
    {
        echo 'When making an issue that affects your dominion in its current state, please copy and paste the following block into the issue (including the trailing ```):<br><br>';
        echo '<div style="font-family: monospace; word-wrap: break-word; background-color: #eee;">';
        echo "Context:<br>\n";
        echo "```<br>\n";
        echo encrypt($this->getSelectedDominion()->toJson()) . "<br>\n";
        echo "```\n";
        echo '</div>';
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

            } elseif (in_array($reflectionMethod->getName(), ['getOffensivePower', 'getOffensivePowerRaw', 'getOffensivePowerMultiplier', 'getDefensivePower', 'getDefensivePowerRaw', 'getDefensivePowerMultiplier', 'getSpyRatio', 'getSpyRatioRaw', 'getWizardRatio', 'getWizardRatioRaw', 'getRecentlyInvadedCount'])) {
                // Exception for methods which have more than 1 arguments
                $value = $class->$method(static::$selectedDominion);

            } else {
                throw new \Exception("Error with method: {$method}");
            }

            $type = gettype($value);

            $return .= ($label . ' :');

            if (is_scalar($value)) {
                if (is_int($value)) {
                    $value = number_format($value);
                } elseif (is_float($value) || is_double($value)) {

                    if (substr($label, -10) === 'Multiplier') {
                        $value = number_format($value * 100 - 100, 2);
                        $value = ((($value < 0) ? '' : '+') . $value . '%');
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
