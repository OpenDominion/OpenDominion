<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class OpsCalculator
{
    /**
     * @var float Base amount of infamy lost each hour
     */
    protected const INFAMY_DECAY = -25;

    /**
     * @var float Base amount of resilience lost each hour
     */
    protected const RESILIENCE_DECAY = -8;

    /**
     * @var float Base amount of resilience gained per op
     */
    protected const RESILIENCE_GAIN = 11;

    /**
     * OpsCalculator constructor.
     *
     * @param MilitaryCalculator $militaryCalculator
     * @param RangeCalculator $rangeCalculator
     */
    public function __construct(
        MilitaryCalculator $militaryCalculator,
        RangeCalculator $rangeCalculator
    )
    {
        $this->militaryCalculator = $militaryCalculator;
        $this->rangeCalculator = $rangeCalculator;
    }

    /**
     * Returns the amount of infamy gained by a Dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return int
     */
    public function getInfamyGain(Dominion $dominion, Dominion $target, string $type): int
    {
        if ($type == 'spy') {
            $infamy = 5;
            $selfRatio = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
            $targetRatio = $this->militaryCalculator->getSpyRatio($target, 'defense');
        } elseif ($type == 'wizard') {
            $infamy = 10;
            $selfRatio = $this->militaryCalculator->getWizardRatio($dominion, 'offense');
            $targetRatio = $this->militaryCalculator->getWizardRatio($target, 'defense');
        } else {
            return 0;
        }

        $relativeRatio = ($targetRatio / $selfRatio);
        if ($relativeRatio >= 0.75 && $relativeRatio < 1.0) {
            $infamy += 5;
        } elseif ($relativeRatio >= 1.0 && $relativeRatio < 1.25) {
            $infamy += 10;
        } elseif ($relativeRatio >= 1.25) {
            $infamy += 15;
        }

        $range = $this->rangeCalculator->getDominionRange($dominion, $target);
        // TODO: Placeholder for tech perk
        if ($range >= 75 && $range <= (10000 / 75)) {
            $infamy += 5;
        }

        return $infamy;
    }

    /**
     * Returns the Dominion's hourly infamy decay.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getInfamyDecay(Dominion $dominion): int
    {
        $decay = static::INFAMY_DECAY;

        // TODO: Placeholder for tech perk

        return $decay;
    }

    /**
     * Returns the amount of infamy gained by a Dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @return int
     */
    public function getResilienceGain(Dominion $dominion): int
    {
        $resilience = static::RESILIENCE_GAIN;

        // TODO: Placeholder for tech perk

        return $resilience;
    }

    /**
     * Returns the Dominion's hourly resilience decay.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getResilienceDecay(Dominion $dominion): int
    {
        $decay = static::RESILIENCE_DECAY;

        // TODO: Placeholder for tech perk

        return $decay;
    }

    /**
     * Returns the Dominion's spy damage reduction from resilience.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyResilienceBonus(Dominion $dominion): float
    {
        if ($dominion->spy_resilience == 0) {
            return 0;
        }

        return (1 + error_function(0.00226 * ($dominion->spy_resilience - 770))) / 2;
    }

    /**
     * Returns the Dominion's wizard damage reduction from resilience.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardResilienceBonus(Dominion $dominion): float
    {
        if ($dominion->wizard_resilience == 0) {
            return 0;
        }

        return (1 + error_function(0.00226 * ($dominion->wizard_resilience - 770))) / 2;
    }

    /**
     * Returns the amount of mastery gained by a Dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return int
     */
    public function getMasteryGain(Dominion $dominion, Dominion $target, string $type): int
    {
        if ($type == 'spy') {
            $mastery = 1;
            $selfMastery = $dominion->spy_mastery;
            $targetMastery = $target->spy_mastery;
            $selfRatio = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
            $targetRatio = $this->militaryCalculator->getSpyRatio($target, 'defense');
        } elseif ($type == 'wizard') {
            $mastery = 1;
            $selfMastery = $dominion->wizard_mastery;
            $targetMastery = $target->wizard_mastery;
            $selfRatio = $this->militaryCalculator->getWizardRatio($dominion, 'offense');
            $targetRatio = $this->militaryCalculator->getWizardRatio($target, 'defense');
        } else {
            return 0;
        }

        $masteryDifference = $selfMastery - $targetMastery;
        if ($masteryDifference > 500) {
            return $mastery;
        }
        if ($masteryDifference > 300) {
            $mastery += 5;
        }
        if (abs($masteryDifference) < 100) {
            $mastery += 3;
        }

        $relativeRatio = ($targetRatio / $selfRatio);
        if ($relativeRatio >= 0.75 && $relativeRatio < 1.0) {
            $mastery += 1;
        } elseif ($relativeRatio >= 1.0 && $relativeRatio < 1.25) {
            $mastery += 2;
        } elseif ($relativeRatio >= 1.25) {
            $mastery += 3;
        }

        $range = $this->rangeCalculator->getDominionRange($dominion, $target);
        if ($range >= 75 && $range <= (10000 / 75)) {
            $mastery += 1;
        }

        return $mastery;
    }
}
