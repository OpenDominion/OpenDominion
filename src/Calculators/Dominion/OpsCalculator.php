<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;

class OpsCalculator
{
    /**
     * @var float Base amount of resilience gained per op
     */
    protected const PEASANT_VULNERABILITY = 50 / 100;
    protected const IMPROVEMENT_VULNERABILITY = 20 / 100;

    /**
     * @var float Base amount of resilience lost each hour
     */
    protected const RESILIENCE_DECAY = -20;
    protected const FIREBALL_METER_DECAY = -4;
    protected const LIGHTNING_BOLT_METER_DECAY = -4;

    /**
     * @var float Base amount of resilience gained per op
     */
    protected const RESILIENCE_GAIN = 10;
    protected const FIREBALL_METER_GAIN = 10;
    protected const LIGHTNING_BOLT_METER_GAIN = 10;

    /** @var GovernmentService */
    protected $governmentService;

    /** @var GuardMembershipService */
    protected $guardMembershipService;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /**
     * OpsCalculator constructor.
     *
     * @param GovernmentService $governmentService
     * @param GuardMembershipService $guardMembershipService
     * @param ImprovementCalculator $improvementCalculator
     * @param LandCalculator $landCalculator
     * @param MilitaryCalculator $militaryCalculator
     * @param PopulationCalculator $populationCalculator
     * @param SpellCalculator $spellCalculator
     */
    public function __construct(
        GovernmentService $governmentService,
        GuardMembershipService $guardMembershipService,
        ImprovementCalculator $improvementCalculator,
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        PopulationCalculator $populationCalculator,
        SpellCalculator $spellCalculator
    )
    {
        $this->governmentService = $governmentService;
        $this->guardMembershipService = $guardMembershipService;
        $this->improvementCalculator = $improvementCalculator;
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->populationCalculator = $populationCalculator;
        $this->spellCalculator = $spellCalculator;
    }

    /**
     * Returns the success modifier based on relative strength.
     *
     * @param float $selfStrength
     * @param float $targetStrength
     * @return float
     */
    public function getSuccessModifier(float $selfStrength, float $targetStrength) {
        return ($selfStrength - $targetStrength) / 1000;
    }

    /**
     * Returns the chance of success for an info operation or spell.
     *
     * @param float $selfRatio
     * @param float $targetRatio
     * @return float
     */
    public function infoOperationSuccessChance(float $selfRatio, float $targetRatio, float $selfStrength, float $targetStrength): float
    {
        if (!$targetRatio) {
            return 1;
        }

        $relativeRatio = $selfRatio / $targetRatio;
        $successChance = 0.8 ** (2 / (($relativeRatio * 1.4) ** 1.2));
        $successChance += $this->getSuccessModifier($selfStrength, $targetStrength);
        return clamp($successChance, 0.01, 0.98);
    }

    /**
     * Returns the chance of success for a theft operation.
     *
     * @param float $selfRatio
     * @param float $targetRatio
     * @return float
     */
    public function theftOperationSuccessChance(float $selfRatio, float $targetRatio, float $selfStrength, float $targetStrength): float
    {
        if (!$targetRatio) {
            return 1;
        }

        $relativeRatio = $selfRatio / $targetRatio;
        $successChance = 0.7 ** (2 / (($relativeRatio * 1.3) ** 1.2));
        $successChance += $this->getSuccessModifier($selfStrength, $targetStrength);
        return clamp($successChance, 0.01, 0.97);
    }

    /**
     * Returns the chance of success for a hostile operation or spell.
     *
     * @param float $selfRatio
     * @param float $targetRatio
     * @return float
     */
    public function blackOperationSuccessChance(float $selfRatio, float $targetRatio, float $selfStrength, float $targetStrength): float
    {
        if (!$targetRatio) {
            return 1;
        }

        $relativeRatio = $selfRatio / $targetRatio;
        $successChance = 0.7 ** (2 / (($relativeRatio * 1.3) ** 1.2));
        $successChance += $this->getSuccessModifier($selfStrength, $targetStrength);
        return clamp($successChance, 0.01, 0.97);
    }

    /**
     * Returns the percentage of spies killed after a failed operation.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return float
     */
    public function getSpyLosses(Dominion $dominion, Dominion $target, string $type): float
    {
        // Values (percentage)
        if ($type == 'info') {
            $spiesKilledBasePercentage = 0.25;
            $min = 0.25;
            $max = 1;
        } elseif ($type == 'theft') {
            $spiesKilledBasePercentage = 1;
            $min = 0.5;
            $max = 1.5;
        } else {
            $spiesKilledBasePercentage = 1;
            $min = 0.5;
            $max = 1.5;
        }

        $selfRatio = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
        $targetRatio = $this->militaryCalculator->getSpyRatio($target, 'defense');

        $spyLossSpaRatio = ($targetRatio / $selfRatio);
        $spiesKilledPercentage = clamp($spiesKilledBasePercentage * $spyLossSpaRatio, $min, $max);
        $spiesKilledMultiplier = 1;

        // Spells
        $spiesKilledMultiplier += $dominion->getSpellPerkMultiplier('spy_losses');

        // Techs
        $spiesKilledMultiplier += $dominion->getTechPerkMultiplier('spy_losses');

        // Heroes
        if ($target->hero !== null && $target->hero->getPerkValue('enemy_spy_losses')) {
            $spiesKilledMultiplier += $target->hero->getPerkMultiplier('enemy_spy_losses');
        }

        // Mastery
        $maxMasteryBonus = -50;
        $spiesKilledMultiplier += min(1000, $dominion->spy_mastery) / 1000 * $maxMasteryBonus / 100;

        // Mutual War
        if ($this->governmentService->isAtMutualWar($dominion->realm, $target->realm)) {
            $spiesKilledMultiplier *= 0.8;
        }

        // Cap at -80%
        $spiesKilledMultiplier = max(0.2, $spiesKilledMultiplier);

        return ($spiesKilledPercentage / 100) * $spiesKilledMultiplier;
    }

    /**
     * Returns the percentage of assassins killed after a failed operation.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return float
     */
    public function getAssassinLosses(Dominion $dominion, Dominion $target, string $type): float
    {
        return $this->getSpyLosses($dominion, $target, $type);
    }

    /**
     * Returns the percentage of wizards killed after a failed spell.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return float
     */
    public function getWizardLosses(Dominion $dominion, Dominion $target, string $type): float
    {
        // Values (percentage)
        if ($type == 'hostile') {
            $wizardsKilledBasePercentage = 1;
            $min = 0.5;
            $max = 1.5;
        } else {
            return 0;
        }

        $selfRatio = $this->militaryCalculator->getWizardRatio($dominion, 'offense');
        $targetRatio = $this->militaryCalculator->getWizardRatio($target, 'defense');

        $wizardLossSpaRatio = ($targetRatio / $selfRatio);
        $wizardsKilledPercentage = clamp($wizardsKilledBasePercentage * $wizardLossSpaRatio, $min, $max);
        $wizardsKilledMultiplier = 1;

        // Mutual War
        if ($this->governmentService->isAtMutualWar($dominion->realm, $target->realm)) {
            $wizardsKilledMultiplier *= 0.8;
        }

        return ($wizardsKilledPercentage / 100) * $wizardsKilledMultiplier;
    }

    /**
     * Returns the percentage of archmages killed after a failed spell.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return float
     */
    public function getArchmageLosses(Dominion $dominion, Dominion $target, string $type): float
    {
        return $this->getWizardLosses($dominion, $target, $type) / 10;
    }

    /**
     * Returns the amount of resilience gained by a Dominion.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getResilienceGain(Dominion $dominion): int
    {
        $resilience = static::RESILIENCE_GAIN;
        if ($dominion->resilience + $resilience > 2000) {
            return 2000 - $dominion->resilience;
        }
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
        $resilience = $dominion->resilience;
        return max($decay, -$resilience);
    }

    /**
     * Returns the amount of spell meter gained by a Dominion.
     *
     * @param Dominion $dominion
     * @param string $type
     * @return int
     */
    public function getSpellMeterGain(Dominion $dominion, string $type): int
    {
        if ($type == 'fireball') {
            $gained = static::FIREBALL_METER_GAIN;
            if ($dominion->fireball_meter + $gained > 200) {
                return 200 - $dominion->fireball_meter;
            }
        } elseif ($type == 'lightning_bolt') {
            $gained = static::LIGHTNING_BOLT_METER_GAIN;
            if ($dominion->lightning_bolt_meter + $gained > 200) {
                return 200 - $dominion->lightning_bolt_meter;
            }
        } else {
            return 0;
        }

        return $gained;
    }

    /**
     * Returns the Dominion's hourly spell meter decay.
     *
     * @param Dominion $dominion
     * @param string $type
     * @return int
     */
    public function getSpellMeterDecay(Dominion $dominion, string $type): int
    {
        if ($type == 'fireball') {
            $decay = static::FIREBALL_METER_DECAY;
            $meter = $dominion->fireball_meter;
        } elseif ($type == 'lightning_bolt') {
            $decay = static::LIGHTNING_BOLT_METER_DECAY;
            $meter = $dominion->lightning_bolt_meter;
        } else {
            return 0;
        }

        if ($this->spellCalculator->isSpellActive($dominion, 'rejuvenation')) {
            $decay *= 2;
        }

        return max($decay, -$meter);
    }

    /**
     * Returns the change in mastery between two Dominions.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return int
     */
    public function getMasteryChange(Dominion $dominion, Dominion $target, string $type, bool $loss = false): int
    {
        if ($type == 'spy') {
            $selfMastery = $dominion->spy_mastery;
            $targetMastery = $target->spy_mastery;
            $targetRatio = $this->militaryCalculator->getSpyRatio($target, 'defense');
        } elseif ($type == 'wizard') {
            $selfMastery = $dominion->wizard_mastery;
            $targetMastery = $target->wizard_mastery;
            $targetRatio = $this->militaryCalculator->getWizardRatio($target, 'defense');
        } else {
            return 0;
        }

        $masteryDifference = clamp($targetMastery - $selfMastery, -500, 500);
        if ($masteryDifference == -500) {
            $masteryDifference -= 1;
        }

        // Amount based on relative mastery (from 0 to 6, 3 when equal)
        $masteryChange = 3 + ($masteryDifference / 200);

        // Halved for mastery loss (from 0 to 3)
        if ($loss) {
            $masteryChange = $masteryChange / 2;
            if ($selfMastery < 100) {
                $masteryChange = 0;
            }
        }

        // Gain up to 4 more based on target's ratio (if within 500 pts)
        if (!$loss && $masteryDifference > -500) {
            $masteryChange += min(4, $targetRatio * 4);
        }

        return round($masteryChange);
    }

    /*
     * Returns the spell damage multiplier
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @return float
     */
    public function getSpellDamageMultiplier(Dominion $target, string $spellKey = '', Dominion $dominion = null): float
    {
        $modifier = 1;

        if ($spellKey == 'lightning_bolt') {
            // Guilds
            $wizardGuildReduction = 10;
            $wizardGuildReductionMax = 50;
            $modifier -= min(
                (($target->building_wizard_guild / $this->landCalculator->getTotalLand($target)) * $wizardGuildReduction),
                ($wizardGuildReductionMax / 100)
            );
        }

        if ($spellKey !== 'fireball') {
            // Spires
            $modifier -= $this->improvementCalculator->getImprovementMultiplierBonus($target, 'spires', true);
        }

        // Spells
        $modifier += $target->getSpellPerkValue('enemy_spell_damage', ['self', 'friendly', 'hostile', 'war']) / 100;
        if ($this->spellCalculator->isSpellActive($target, 'energy_mirror')) {
            if ($target->hero !== null && $target->hero->getPerkValue('improved_energy_mirror')) {
                $modifier -= $target->hero->getPerkMultiplier('improved_energy_mirror');
            }
        }

        // Techs
        $modifier += $target->getTechPerkMultiplier("enemy_{$spellKey}_damage");

        // Wonders
        $modifier += $target->getWonderPerkMultiplier('enemy_spell_damage');

        // Heroes
        if ($target->hero !== null && $target->hero->getPerkValue("enemy_{$spellKey}_damage")) {
            $modifier += $target->hero->getPerkMultiplier("enemy_{$spellKey}_damage");
        }
        if ($dominion !== null && $dominion->hero !== null && $dominion->hero->getPerkValue("{$spellKey}_damage")) {
            $modifier += $dominion->hero->getPerkMultiplier("{$spellKey}_damage");
        }

        // Status Effects & Chaos League (multiplicative)
        $spellModifier = 1;

        $spellModifier += $target->getSpellPerkValue('enemy_spell_damage', ['effect']) / 100;
        $spellModifier += $target->getSpellPerkValue("enemy_{$spellKey}_damage", ['effect']) / 100;

        // Capped at 80% reduction
        return max(0.2, $modifier * $spellModifier);
    }

    /*
     * Returns the final percentage of peasants that are vulnerable to fireball
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPeasantVulnerablilityModifier(Dominion $dominion): float
    {
        $modifier = 1;

        // Spires
        $modifier -= $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'spires', true);

        return $modifier * static::PEASANT_VULNERABILITY;
    }

    /*
     * Returns the raw number of peasants that are protected by wizards and guilds
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPeasantWizardProtection(Dominion $dominion): int
    {
        $protected = 0;

        // Values
        $peasantsPerWizard = 5;
        $peasantsPerWizardGuild = 15;
        $wizardsPerGuild = 6;

        // Wizard Protection
        $wizardRatio = $this->militaryCalculator->getWizardRatioRaw($dominion, 'defense');
        $rawWizards = $wizardRatio * $this->landCalculator->getTotalLand($dominion);
        $protected += $rawWizards * $peasantsPerWizard;
        $protected += min(
            ($dominion->building_wizard_guild * $wizardsPerGuild),
            $rawWizards
        ) * $peasantsPerWizardGuild;

        return $protected;
    }

    /*
     * Returns the raw number of max peasants that are protected from fireball damage
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPeasantsProtected(Dominion $dominion): int
    {
        // Base Vulnerability
        $vulnerabilityModifier = $this->getPeasantVulnerablilityModifier($dominion);
        $maxPeasants = max(0, $this->populationCalculator->getMaxPeasantPopulation($dominion));
        $totalProtected = round($maxPeasants * (1 - $vulnerabilityModifier));
        $totalProtected += $this->getPeasantWizardProtection($dominion);

        return min($totalProtected, $maxPeasants * 0.8);
    }

    /*
     * Returns the raw number of peasants that are not protected from fireball damage
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPeasantsUnprotected(Dominion $dominion): int
    {
        $protectedPeasants = $this->getPeasantsProtected($dominion);

        return max(0, $dominion->peasants - $protectedPeasants);
    }

    /*
     * Returns the raw number of peasants that can be killed by fireball
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPeasantsVulnerable(Dominion $dominion): int
    {
        $maxPeasants = max(0, $this->populationCalculator->getMaxPeasantPopulation($dominion));

        return max(0, $maxPeasants - $this->getPeasantsProtected($dominion));
    }

    /*
     * Returns the raw amount of current improvements that can be destroyed by lightning damage
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getImprovementsVulnerable(Dominion $dominion): int
    {
        $vulnerableInvestments = max(0, $dominion->stat_total_investment - $dominion->improvement_spires - $dominion->improvement_harbor);
        $protectedImprovements = round($vulnerableInvestments * (1 - static::IMPROVEMENT_VULNERABILITY));

        $currentImprovements = $this->improvementCalculator->getImprovementTotal($dominion);
        $destroyableImprovements = $currentImprovements - $dominion->improvement_spires - $dominion->improvement_harbor;

        return max(0, $destroyableImprovements - $protectedImprovements);
    }

    public function getChaosChange(Dominion $dominion, bool $success): float
    {
        $realmies = $dominion->realm->dominions()
            ->where('black_guard_active_at', '<', now())
            ->where(function ($query) {
                $query->where('black_guard_inactive_at', null)
                    ->orWhere('black_guard_inactive_at', '>', now());
            })
            ->count();

        if ($success) {
            // Gain between 20 and 50
            return min(50, (5 * $realmies) + 15);
        }

        // Lose between 10 and 100
        return max(10, 110 - (10 * $realmies));
    }
}
