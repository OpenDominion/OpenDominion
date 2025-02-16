<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;

class TrainingCalculator
{
    /** @var HeroCalculator */
    protected $heroCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var UnitHelper */
    protected $unitHelper;

    /**
     * TrainingCalculator constructor.
     */
    public function __construct()
    {
        $this->heroCalculator = app(HeroCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->unitHelper = app(UnitHelper::class);
    }

    /**
     * Returns the Dominion's training costs per unit.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getTrainingCostsPerUnit(Dominion $dominion): array
    {
        $costsPerUnit = [];
        $spyBaseCost = 500;
        $assassinBaseCost = 1000;
        $assassinBaseCost += $dominion->race->getPerkValue('assassin_cost');
        $wizardBaseCost = 500;
        $archmageBaseCost = 1000;
        $archmageBaseCost += $dominion->race->getPerkValue('archmage_cost');

        $spyCostMultiplier = $this->getSpyCostMultiplier($dominion);
        $wizardCostMultiplier = $this->getWizardCostMultiplier($dominion);

        // Values
        $spyPlatinumCost = (int)rceil($spyBaseCost * $spyCostMultiplier);
        $assassinPlatinumCost = (int)rceil($assassinBaseCost * $spyCostMultiplier);
        $wizardPlatinumCost = (int)rceil($wizardBaseCost * $wizardCostMultiplier);
        $archmagePlatinumCost = (int)rceil($archmageBaseCost * $wizardCostMultiplier);

        $units = $dominion->race->units;

        foreach ($this->unitHelper->getUnitTypes() as $unitType) {
            $cost = [];

            switch ($unitType) {
                case 'spies':
                    $cost['draftees'] = 1;
                    $cost['platinum'] = $spyPlatinumCost;
                    break;

                case 'assassins':
                    $cost['platinum'] = $assassinPlatinumCost;
                    $cost['spies'] = 1;
                    break;

                case 'wizards':
                    $cost['draftees'] = 1;
                    $cost['platinum'] = $wizardPlatinumCost;
                    break;

                case 'archmages':
                    $cost['platinum'] = $archmagePlatinumCost;
                    $cost['wizards'] = 1;
                    break;

                default:
                    $unitSlot = (((int)str_replace('unit', '', $unitType)) - 1);

                    $platinum = $units[$unitSlot]->cost_platinum;
                    $ore = $units[$unitSlot]->cost_ore;
                    $mana = $units[$unitSlot]->cost_mana;
                    $lumber = $units[$unitSlot]->cost_lumber;
                    $gems = $units[$unitSlot]->cost_gems;
                    list($type, $proficiency) = explode('_', $units[$unitSlot]->type);

                    if ($platinum > 0) {
                        $cost['platinum'] = (int)rceil($platinum * $this->getSpecialistEliteCostMultiplier($dominion, $proficiency));
                    }

                    if ($ore > 0) {
                        $cost['ore'] = $ore;

                        if ($dominion->race->key !== 'gnome') {
                            $cost['ore'] = (int)rceil($ore * $this->getSpecialistEliteCostMultiplier($dominion, $proficiency));
                        }
                    }

                    if ($mana > 0) {
                        $cost['mana'] = (int)rceil($mana * $this->getSpecialistEliteCostMultiplier($dominion, $proficiency));
                    }

                    if ($lumber > 0) {
                        $cost['lumber'] = $lumber;

                        if ($dominion->race->key !== 'wood-elf-rework') {
                            $cost['lumber'] = (int)rceil($lumber * $this->getSpecialistEliteCostMultiplier($dominion, $proficiency));
                        }
                    }

                    if ($gems > 0) {
                        $cost['gems'] = (int)rceil($gems * $this->getSpecialistEliteCostMultiplier($dominion, $proficiency));
                    }

                    $cost['draftees'] = 1;

                    break;
            }

            $costsPerUnit[$unitType] = $cost;
        }

        return $costsPerUnit;
    }

    /**
     * Returns the Dominion's max military trainable population.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getMaxTrainable(Dominion $dominion): array
    {
        $trainable = [];

        $fieldMapping = [
            'platinum' => 'resource_platinum',
            'ore' => 'resource_ore',
            'mana' => 'resource_mana',
            'lumber' => 'resource_lumber',
            'gems' => 'resource_gems',
            'draftees' => 'military_draftees',
            'spies' => 'military_spies',
            'wizards' => 'military_wizards',
        ];

        $costsPerUnit = $this->getTrainingCostsPerUnit($dominion);

        foreach ($costsPerUnit as $unitType => $costs) {
            $trainableByCost = [];

            foreach ($costs as $type => $value) {
                $trainableByCost[$type] = (int)rfloor($dominion->{$fieldMapping[$type]} / $value);
            }

            $trainable[$unitType] = min($trainableByCost);
        }

        return $trainable;
    }

    /**
     * Returns the Dominion's training cost multiplier.
     *
     * @param Dominion $dominion
     * @param string $proficiency
     * @return float
     */
    public function getSpecialistEliteCostMultiplier(Dominion $dominion, string $proficiency = 'elite'): float
    {
        $multiplier = 1;

        // Values (percentages)
        $smithiesReduction = 2;
        $smithiesReductionMax = 36;

        // Smithies
        $multiplier -= min(
            (($dominion->building_smithy / $this->landCalculator->getTotalLand($dominion)) * $smithiesReduction),
            ($smithiesReductionMax / 100)
        );

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('military_cost');

        // Heroes
        $multiplier += $this->heroCalculator->getHeroPerkMultiplier($dominion, 'military_cost');

        // Spells
        if ($proficiency === 'elite') {
            $multiplier += $this->spellCalculator->resolveSpellPerk($dominion, 'military_cost_elite') / 100;
        }

        return $multiplier;
    }

    /**
     * Returns the Dominion's training platinum cost multiplier for spies and assassins.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyCostMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('spy_cost');

        // Spells
        $multiplier += $this->spellCalculator->resolveSpellPerk($dominion, 'spy_cost') / 100;
        $martyrdomPerk = $this->spellCalculator->resolveSpellPerk($dominion, 'martyrdom');
        if ($martyrdomPerk) {
            // Special case for Martyrdom, cap at 50% reduction
            $prestigeMultiplier = 1 / $martyrdomPerk / 100;
            $multiplier -= min(0.5, $prestigeMultiplier * $dominion->prestige);
        }

        return $multiplier;
    }

    /**
     * Returns the Dominion's training platinum cost multiplier for wizards and archmages.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardCostMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('wizard_cost');

        // Spells
        $multiplier += $this->spellCalculator->resolveSpellPerk($dominion, 'wizard_cost') / 100;
        $martyrdomPerk = $this->spellCalculator->resolveSpellPerk($dominion, 'martyrdom');
        if ($martyrdomPerk) {
            // Special case for Martyrdom, cap at 50% reduction
            $prestigeMultiplier = 1 / $martyrdomPerk / 100;
            $multiplier -= min(0.5, $prestigeMultiplier * $dominion->prestige);
        }

        return $multiplier;
    }
}
