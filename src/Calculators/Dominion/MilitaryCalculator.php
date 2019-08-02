<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Unit;
use OpenDominion\Services\Dominion\QueueService;

class MilitaryCalculator
{
    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var PrestigeCalculator */
    private $prestigeCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /**
     * MilitaryCalculator constructor.
     *
     * @param BuildingCalculator $buildingCalculator
     * @param ImprovementCalculator $improvementCalculator
     * @param LandCalculator $landCalculator
     * @param PrestigeCalculator $prestigeCalculator
     * @param QueueService $queueService
     * @param SpellCalculator $spellCalculator
     */
    public function __construct(
        BuildingCalculator $buildingCalculator,
        ImprovementCalculator $improvementCalculator,
        LandCalculator $landCalculator,
        PrestigeCalculator $prestigeCalculator,
        QueueService $queueService,
        SpellCalculator $spellCalculator)
    {
        $this->buildingCalculator = $buildingCalculator;
        $this->improvementCalculator = $improvementCalculator;
        $this->landCalculator = $landCalculator;
        $this->prestigeCalculator = $prestigeCalculator;
        $this->queueService = $queueService;
        $this->spellCalculator = $spellCalculator;
    }

    /**
     * Returns the Dominion's offensive power.
     *
     * @param Dominion $dominion
     * @param string|null $opposingForceRaceName
     * @param float|null $landRatio
     * @param array|null $units
     * @return float
     */
    public function getOffensivePower(
        Dominion $dominion,
        string $opposingForceRaceName = null,
        float $landRatio = null,
        array $units = null): float
    {
        $op = ($this->getOffensivePowerRaw($dominion, $opposingForceRaceName, $landRatio, $units) * $this->getOffensivePowerMultiplier($dominion));

        return ($op * $this->getMoraleMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw offensive power.
     *
     * @param Dominion $dominion
     * @param string|null $opposingForceRaceName
     * @param float|null $landRatio
     * @param array|null $units
     * @return float
     */
    public function getOffensivePowerRaw(
        Dominion $dominion,
        string $opposingForceRaceName = null,
        float $landRatio = null,
        array $units = null): float
    {
        $op = 0;

        foreach ($dominion->race->units as $unit) {
            $powerOffense = $this->getUnitPowerWithPerks($dominion, $opposingForceRaceName, $landRatio, $unit, 'offense');

            $numberOfUnits = 0;

            if($units === null) {
                $numberOfUnits = (int)$dominion->{'military_unit' . $unit->slot};
            } elseif (isset($units[$unit->slot]) && ((int)$units[$unit->slot] !== 0)) {
                $numberOfUnits = (int)$units[$unit->slot];
            }

            $op += ($powerOffense * $numberOfUnits);
        }

        return $op;
    }

    /**
     * Returns the Dominion's offensive power multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Values (percentages)
        $opPerGryphonNest = 1.75;
        $gryphonNestMaxOp = 35;
        $spellCrusade = 5;
        $spellKillingRage = 10;
        $spellWarsong = 10;

        // Gryphon Nests
        $multiplier += min(
            (($opPerGryphonNest * $dominion->building_gryphon_nest) / $this->landCalculator->getTotalLand($dominion)),
            ($gryphonNestMaxOp / 100)
        );

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('offense');

        // Improvement: Forges
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'forges');

        // Racial Spell
        // todo
        // Spell: Howling (+10%)
        // Spell: Nightfall (+5%)
        $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, [
            'crusade' => $spellCrusade,
            'killing_rage' => $spellKillingRage,
            'warsong' => $spellWarsong,
        ]);

        // Prestige
        $multiplier += $this->prestigeCalculator->getPrestigeMultiplier($dominion);

        // Tech: Military (+5%)
        // Tech: Magical Weaponry (+10%)
        // todo

        return (1 + $multiplier);
    }

    /**
     * Returns the Dominion's offensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerRatio(Dominion $dominion): float
    {
        return ($this->getOffensivePower($dominion) / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's raw offensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerRatioRaw(Dominion $dominion): float
    {
        return ($this->getOffensivePowerRaw($dominion) / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's defensive power.
     *
     * @param Dominion $dominion
     * @param string|null $opposingForceRaceName
     * @param float|null $landRatio
     * @param array|null $units
     * @param float $multiplierReduction
     * @param bool $ignoreDraftees
     * @return float
     */
    public function getDefensivePower(
        Dominion $dominion,
        string $opposingForceRaceName = null,
        float $landRatio = null,
        array $units = null,
        float $multiplierReduction = 0,
        bool $ignoreDraftees = false): float
    {
        $dp = $this->getDefensivePowerRaw($dominion, $opposingForceRaceName, $landRatio, $units, $ignoreDraftees);
        $dp *= $this->getDefensivePowerMultiplier($dominion, $multiplierReduction);

        return ($dp * $this->getMoraleMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw defensive power.
     *
     * @param Dominion $dominion
     * @param string|null $opposingForceRaceName
     * @param float|null $landRatio
     * @param array|null $units
     * @param bool $ignoreDraftees
     * @return float
     */
    public function getDefensivePowerRaw(
        Dominion $dominion,
        string $opposingForceRaceName = null,
        float $landRatio = null,
        array $units = null,
        bool $ignoreDraftees = false): float
    {
        $dp = 0;

        // Values
        $minDPPerAcre = 1.5;
        $dpPerDraftee = 1;
        $forestHavenDpPerPeasant = 0.75;
        $peasantsPerForestHaven = 20;

        // Military
        foreach ($dominion->race->units as $unit) {
            $powerDefense = $this->getUnitPowerWithPerks($dominion, $opposingForceRaceName, $landRatio, $unit, 'defense');

            $numberOfUnits = 0;

            if($units === null) {
                $numberOfUnits = (int)$dominion->{'military_unit' . $unit->slot};
            } elseif (isset($units[$unit->slot]) && ((int)$units[$unit->slot] !== 0)) {
                $numberOfUnits = (int)$units[$unit->slot];
            }

            $dp += ($powerDefense * $numberOfUnits);
        }

        // Attacking Forces skip draftees and land-based defenses
        if ($units !== null)
            return $dp;

        // Draftees
        if(!$ignoreDraftees) {
            $dp += ($dominion->military_draftees * $dpPerDraftee);
        }

        // Forest Havens
        $dp += min(
            ($dominion->peasants * $forestHavenDpPerPeasant),
            ($dominion->building_forest_haven * $forestHavenDpPerPeasant * $peasantsPerForestHaven)
        ); // todo: recheck this

        return max(
            $dp,
            ($minDPPerAcre * $this->landCalculator->getTotalLand($dominion))
        );
    }

    /**
     * Returns the Dominion's defensive power multiplier.
     *
     * @param Dominion $dominion
     * @param float $multiplierReduction
     * @return float
     */
    public function getDefensivePowerMultiplier(Dominion $dominion, float $multiplierReduction = 0): float
    {
        $multiplier = 0;

        // Values (percentages)
        $dpPerGuardTower = 1.75;
        $guardTowerMaxDp = 35;
        $spellAresCall = 10;
        $spellBlizzard = 15;
        $spellFrenzy = 20;

        // Guard Towers
        $multiplier += min(
            (($dpPerGuardTower * $dominion->building_guard_tower) / $this->landCalculator->getTotalLand($dominion)),
            ($guardTowerMaxDp / 100)
        );

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('defense');

        // Improvement: Walls
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'walls');

        // Spell: Howling (+10%)
        // todo

        // Spell: Blizzard (+15%)
        $multiplierFromBlizzard = $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'blizzard', $spellBlizzard);
        $multiplier += $multiplierFromBlizzard;

        // Spell: Frenzy (Halfling) (+20%)
        $multiplierFromFrenzy = $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'defensive_frenzy', $spellFrenzy);
        $multiplier += $multiplierFromFrenzy;

        // Spell: Ares' Call (+10%)
        if($multiplierFromBlizzard == 0 && $multiplierFromFrenzy == 0) {
            $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'ares_call',
                $spellAresCall);
        }

        // Multiplier reduction when we want to factor in temples from another
        // dominion
        $multiplier = max(($multiplier - $multiplierReduction), 0);

        return (1 + $multiplier);
    }

    /**
     * Returns the Dominion's defensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePowerRatio(Dominion $dominion): float
    {
        return ($this->getDefensivePower($dominion) / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's raw defensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePowerRatioRaw(Dominion $dominion): float
    {
        return ($this->getDefensivePowerRaw($dominion) / $this->landCalculator->getTotalLand($dominion));
    }

    public function getUnitPowerWithPerks(
        Dominion $dominion,
        ?string $opposingForceRaceName,
        ?float $landRatio,
        Unit $unit,
        string $powerType
    ): float
    {
        $unitPower = $unit->{"power_$powerType"};

        $unitPower += $this->getUnitPowerFromLandBasedPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromBuildingBasedPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromRawWizardRatioPerk($dominion, $unit, $powerType);

        if ($landRatio !== null) {
            $unitPower += $this->getUnitPowerFromStaggeredLandRangePerk($dominion, $landRatio, $unit, $powerType);
        }

        if ($opposingForceRaceName !== null) {
            $unitPower += $this->getUnitPowerFromVersusRacePerk($dominion, $opposingForceRaceName, $unit, $powerType);
        }

        return $unitPower;
    }

    protected function getUnitPowerFromLandBasedPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $landPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_land", null);

        if(!$landPerkData) {
            return 0;
        }

        $landType = $landPerkData[0];
        $ratio = (int)$landPerkData[1];
        $max = (int)$landPerkData[2];
        $constructedOnly = false;
        //$constructedOnly = $landPerkData[3]; todo: implement for Nox?
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        if(!$constructedOnly)
        {
            $landPercentage = ($dominion->{"land_{$landType}"} / $totalLand) * 100;
        }
        else
        {
            $buildingsForLandType = $this->buildingCalculator->getTotalBuildingsForLandType($dominion, $landType);

            $landPercentage = ($buildingsForLandType / $totalLand) * 100;
        }

        $powerFromLand = $landPercentage / $ratio;
        $powerFromPerk = min($powerFromLand, $max);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromBuildingBasedPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $buildingPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_building", null);

        if(!$buildingPerkData) {
            return 0;
        }

        $buildingType = $buildingPerkData[0];
        $ratio = (int)$buildingPerkData[1];
        $max = (int)$buildingPerkData[2];
        $totalLand = $this->landCalculator->getTotalLand($dominion);
        $landPercentage = ($dominion->{"building_{$buildingType}"} / $totalLand) * 100;

        $powerFromBuilding = $landPercentage / $ratio;
        $powerFromPerk = min($powerFromBuilding, $max);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromRawWizardRatioPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $wizardRatioPerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_raw_wizard_ratio");

        if(!$wizardRatioPerk) {
            return 0;
        }

        $ratio = (float)$wizardRatioPerk[0];
        $max = (int)$wizardRatioPerk[1];

        $wizardRawRatio = $this->getWizardRatioRaw($dominion, 'offense');
        $powerFromWizardRatio = $wizardRawRatio * $ratio;
        $powerFromPerk = min($powerFromWizardRatio, $max);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromStaggeredLandRangePerk(Dominion $dominion, float $landRatio = null, Unit $unit, string $powerType): float
    {
        $staggeredLandRangePerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_staggered_land_range");

        if(!$staggeredLandRangePerk) {
            return 0;
        }

        if($landRatio === null) {
            $landRatio = 0;
        }

        $powerFromPerk = 0;

        foreach ($staggeredLandRangePerk as $rangePerk) {
            $range = ((int)$rangePerk[0]) / 100;
            $power = (float)$rangePerk[1];

            if($range > $landRatio) { // TODO: Check this, might be a bug here
                continue;
            }

            $powerFromPerk = $power;
        }

        return $powerFromPerk;
    }

    protected function getUnitPowerFromVersusRacePerk(Dominion $dominion, string $opposingForceRaceName = null, Unit $unit, string $powerType): float
    {
        $raceNameFormatted = strtolower($opposingForceRaceName);
        $raceNameFormatted = str_replace(' ', '_', $raceNameFormatted);

        $versusRacePerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_vs_{$raceNameFormatted}");

        return $versusRacePerk;
    }

    /**
     * Returns the Dominion's morale modifier for OP/DP.
     *
     * Net OP/DP gets lowered linearly by up to -10% at 0% morale.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getMoraleMultiplier(Dominion $dominion): float
    {
        return clamp((0.9 + ($dominion->morale / 1000)), 0.9, 1.0);
    }

    /**
     * Returns the Dominion's spy ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyRatio(Dominion $dominion, string $type = 'offense'): float
    {
        return ($this->getSpyRatioRaw($dominion, $type) * $this->getSpyRatioMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw spy ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyRatioRaw(Dominion $dominion, string $type = 'offense'): float
    {
        $spies = $dominion->military_spies;

        // Add units which count as (partial) spies (Lizardfolk Chameleon)
        foreach ($dominion->race->units as $unit) {
            if ($type === 'offense' && $unit->getPerkValue('counts_as_spy_offense')) {
                $spies += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_spy_offense'));
            }

            if ($type === 'defense' && $unit->getPerkValue('counts_as_spy_defense')) {
                $spies += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_spy_defense'));
            }
        }

        return ($spies / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's spy ratio multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyRatioMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Racial bonus
        $multiplier += $dominion->race->getPerkMultiplier('spy_strength');

        // Wonder: Great Oracle (+30%)
        // todo

        return (1 + $multiplier);
    }

    /**
     * Returns the Dominion's spy strength regeneration.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyStrengthRegen(Dominion $dominion): float
    {
        $regen = 4;

        // todo: Spy Master / Dark Artistry tech

        return (float)$regen;
    }

    /**
     * Returns the Dominion's wizard ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardRatio(Dominion $dominion, string $type = 'offense'): float
    {
        return ($this->getWizardRatioRaw($dominion, $type) * $this->getWizardRatioMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw wizard ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardRatioRaw(Dominion $dominion, string $type = 'offense'): float
    {
        $wizards = $dominion->military_wizards + ($dominion->military_archmages * 2);

        // Add units which count as (partial) spies (Dark Elf Adept)
        foreach ($dominion->race->units as $unit) {
            if ($type === 'offense' && $unit->getPerkValue('counts_as_wizard_offense')) {
                $wizards += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_wizard_offense'));
            }

            if ($type === 'defense' && $unit->getPerkValue('counts_as_wizard_defense')) {
                $wizards += floor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_wizard_defense'));
            }
        }

        return ($wizards / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's wizard ratio multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardRatioMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Racial bonus
        $multiplier += $dominion->race->getPerkMultiplier('wizard_strength');

        // Improvement: Towers
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'towers');

        // Tech: Magical Weaponry  (+15%)
        // todo

        return (1 + $multiplier);
    }

    /**
     * Returns the Dominion's wizard strength regeneration.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardStrengthRegen(Dominion $dominion): float
    {
        $regen = 5;

        // todo: Master of Magi / Dark Artistry tech
        // todo: check if this needs to be a float

        return (float)$regen;
    }

    /**
     * Gets the total amount of living specialist/elite units for a Dominion.
     *
     * Total amount includes units at home and units returning from battle.
     *
     * @param Dominion $dominion
     * @param int $slot
     * @return int
     */
    public function getTotalUnitsForSlot(Dominion $dominion, int $slot): int
    {
        return (
            $dominion->{'military_unit' . $slot} +
            $this->queueService->getInvasionQueueTotalByResource($dominion, "military_unit{$slot}")
        );
    }

    /**
     * Returns the number of time the Dominion was recently invaded.
     *
     * 'Recent' refers to the past 24 hours.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getRecentlyInvadedCount(Dominion $dominion): int
    {
        // todo: this touches the db. should probably be in invasion or military service instead
        $invasionEvents = GameEvent::query()
            ->where('created_at', '>=', now()->subDay(1))
            ->where([
                'target_type' => Dominion::class,
                'target_id' => $dominion->id,
                'type' => 'invasion',
            ])
            ->get();

        if ($invasionEvents->isEmpty()) {
            return 0;
        }

        $invasionEvents = $invasionEvents->filter(function (GameEvent $event) {
            return $event->data['result']['success'];
        });

        return $invasionEvents->count();
    }
}
