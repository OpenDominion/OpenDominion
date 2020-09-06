<?php

namespace OpenDominion\Calculators\Dominion;

use DB;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Unit;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\QueueService;

class MilitaryCalculator
{
    /**
     * @var float Number of boats protected per dock
     */
    protected const BOATS_PROTECTED_PER_DOCK = 2.5;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var GovernmentService */
    protected $governmentService;

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

    /** @var bool */
    protected $forTick = false;

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
        GovernmentService $governmentService,
        ImprovementCalculator $improvementCalculator,
        LandCalculator $landCalculator,
        PrestigeCalculator $prestigeCalculator,
        QueueService $queueService,
        SpellCalculator $spellCalculator
    )
    {
        $this->buildingCalculator = $buildingCalculator;
        $this->governmentService = $governmentService;
        $this->improvementCalculator = $improvementCalculator;
        $this->landCalculator = $landCalculator;
        $this->prestigeCalculator = $prestigeCalculator;
        $this->queueService = $queueService;
        $this->spellCalculator = $spellCalculator;
    }

    /**
     * Toggle if this calculator should include the following hour's resources.
     */
    public function setForTick(bool $value)
    {
        $this->forTick = $value;
        $this->queueService->setForTick($value);
    }

    /**
     * Returns the Dominion's offensive power.
     *
     * @param Dominion $dominion
     * @param Dominion|null $target
     * @param float|null $landRatio
     * @param array|null $units
     * @return float
     */
    public function getOffensivePower(
        Dominion $dominion,
        Dominion $target = null,
        float $landRatio = null,
        array $units = null
    ): float
    {
        $op = $this->getOffensivePowerRaw($dominion, $target, $landRatio, $units);
        $op *= $this->getOffensivePowerMultiplier($dominion, $target);
        $op *= $this->getMoraleMultiplier($dominion);

        return $op;
    }

    /**
     * Returns the Dominion's raw offensive power.
     *
     * @param Dominion $dominion
     * @param Dominion|null $target
     * @param float|null $landRatio
     * @param array|null $units
     * @return float
     */
    public function getOffensivePowerRaw(
        Dominion $dominion,
        Dominion $target = null,
        float $landRatio = null,
        array $units = null
    ): float
    {
        $op = 0;

        foreach ($dominion->race->units as $unit) {
            $powerOffense = $this->getUnitPowerWithPerks($dominion, $target, $landRatio, $unit, 'offense');
            $numberOfUnits = 0;

            if ($units === null) {
                $numberOfUnits = (int)$dominion->{'military_unit' . $unit->slot};
            } elseif (isset($units[$unit->slot]) && ((int)$units[$unit->slot] !== 0)) {
                $numberOfUnits = (int)$units[$unit->slot];
            }

            if ($numberOfUnits !== 0) {
                $bonusOffense = $this->getBonusPowerFromPairingPerk($dominion, $unit, 'offense', $units);
                $powerOffense += $bonusOffense / $numberOfUnits;
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
    public function getOffensivePowerMultiplier(Dominion $dominion, Dominion $target = null): float
    {
        $multiplier = 1;

        // Values (percentages)
        $opPerGryphonNest = 1.75;
        $gryphonNestMaxOp = 35;
        $spellBloodrage = 10;
        $spellCrusade = 5;
        $spellHowling = 10;
        $spellKillingRage = 10;
        $spellNightfall = 5;
        $spellWarsong = 10;

        // Gryphon Nests
        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc['gryphon_nest_percent'])) {
                $multiplier += min(
                    (($opPerGryphonNest * $dominion->calc['gryphon_nest_percent']) / 100),
                    ($gryphonNestMaxOp / 100)
                );
            }
        } else {
            $multiplier += min(
                (($opPerGryphonNest * $dominion->building_gryphon_nest) / $this->landCalculator->getTotalLand($dominion)),
                ($gryphonNestMaxOp / 100)
            );
        }

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('offense');

        // Techs
        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc['tech_offense'])) {
                $multiplier += ($dominion->calc['tech_offense'] / 100);
            }
        } else {
            $multiplier += $dominion->getTechPerkMultiplier('offense');
        }

        // Wonders
        // TODO: add to calc if this is implemented
        $multiplier += $dominion->getWonderPerkMultiplier('offense');

        // Improvement: Forges
        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc['forges_percent'])) {
                $multiplier += ($dominion->calc['forges_percent'] / 100);
            }
        } else {
            $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'forges');
        }

        // Racial Spell
        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc['bloodrage'])) {
                $multiplier += ($spellBloodrage / 100);
            }

            if (isset($dominion->calc['crusade'])) {
                $multiplier += ($spellCrusade / 100);
            }

            if (isset($dominion->calc['howling'])) {
                $multiplier += ($spellHowling / 100);
            }

            if (isset($dominion->calc['killing_rage'])) {
                $multiplier += ($spellKillingRage / 100);
            }

            if (isset($dominion->calc['nightfall'])) {
                $multiplier += ($spellNightfall / 100);
            }

            if (isset($dominion->calc['warsong'])) {
                $multiplier += ($spellWarsong / 100);
            }
        } else {
            $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, [
                'bloodrage' => $spellBloodrage,
                'crusade' => $spellCrusade,
                'howling' => $spellHowling,
                'killing_rage' => $spellKillingRage,
                'nightfall' => $spellNightfall,
                'warsong' => $spellWarsong,
            ]);
        }

        // Prestige
        $multiplier += $this->prestigeCalculator->getPrestigeMultiplier($dominion);

        // War
        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc['war_bonus'])) {
                $multiplier += ($dominion->calc['war_bonus'] / 100);
            }
        } else {
            if ($target !== null && $dominion->realm !== null) {
                if ($this->governmentService->isAtMutualWarWithRealm($dominion->realm, $target->realm)) {
                    $multiplier += 0.1;
                } elseif ($this->governmentService->isAtWarWithRealm($dominion->realm, $target->realm)) {
                    $multiplier += 0.05;
                }
            }
        }

        return $multiplier;
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
     * @param Dominion|null $target
     * @param float|null $landRatio
     * @param array|null $units
     * @param float $multiplierReduction
     * @param bool $ignoreDraftees
     * @return float
     */
    public function getDefensivePower(
        Dominion $dominion,
        Dominion $target = null,
        float $landRatio = null,
        array $units = null,
        float $multiplierReduction = 0,
        bool $ignoreDraftees = false,
        bool $ignoreMinDefense = false
    ): float
    {
        $dp = $this->getDefensivePowerRaw($dominion, $target, $landRatio, $units, $ignoreDraftees);
        $dp *= $this->getDefensivePowerMultiplier($dominion, $multiplierReduction);
        $dp *= $this->getMoraleMultiplier($dominion);

        // Attacking Forces skip land-based defenses
        if ($units !== null || $ignoreMinDefense) {
            return $dp;
        }

        return max($dp, $this->getMinimumDefense($dominion));
    }

    /**
     * Returns the Dominion's raw defensive power.
     *
     * @param Dominion $dominion
     * @param Dominion|null $target
     * @param float|null $landRatio
     * @param array|null $units
     * @param bool $ignoreDraftees
     * @return float
     */
    public function getDefensivePowerRaw(
        Dominion $dominion,
        Dominion $target = null,
        float $landRatio = null,
        array $units = null,
        bool $ignoreDraftees = false
    ): float
    {
        $dp = 0;

        // Values
        $dpPerDraftee = 1;

        // Military
        foreach ($dominion->race->units as $unit) {
            $powerDefense = $this->getUnitPowerWithPerks($dominion, $target, $landRatio, $unit, 'defense');

            $numberOfUnits = 0;

            if ($units === null) {
                $numberOfUnits = (int)$dominion->{'military_unit' . $unit->slot};
            } elseif (isset($units[$unit->slot]) && ((int)$units[$unit->slot] !== 0)) {
                $numberOfUnits = (int)$units[$unit->slot];
            }

            if ($numberOfUnits !== 0) {
                $bonusDefense = $this->getBonusPowerFromPairingPerk($dominion, $unit, 'defense', $units);
                $powerDefense += $bonusDefense / $numberOfUnits;
            }

            $dp += ($powerDefense * $numberOfUnits);
        }

        // Draftees
        if (!$ignoreDraftees) {
            if ($units !== null && isset($units[0])) {
                $dp += ((int)$units[0] * $dpPerDraftee);
            } else {
                $dp += ($dominion->military_draftees * $dpPerDraftee);
            }
        }

        return $dp;
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
        $multiplier = 1;

        // Values (percentages)
        $dpPerGuardTower = 1.75;
        $guardTowerMaxDp = 35;
        $spellAresCall = 10;
        $spellBlizzard = 15;
        $spellFrenzy = 20;
        $spellHowling = 10;

        // Guard Towers
        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc['guard_tower_percent'])) {
                $multiplier += min(
                    (($dpPerGuardTower * $dominion->calc['guard_tower_percent']) / 100),
                    ($guardTowerMaxDp / 100)
                );
            }
        } else {
            $multiplier += min(
                (($dpPerGuardTower * $dominion->building_guard_tower) / $this->landCalculator->getTotalLand($dominion)),
                ($guardTowerMaxDp / 100)
            );
        }

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('defense');

        // Techs
        // TODO: add to calc if this is implemented
        $multiplier += $dominion->getTechPerkMultiplier('defense');

        // Wonders
        // TODO: add to calc if this is implemented
        $multiplier += $dominion->getWonderPerkMultiplier('defense');

        // Improvement: Walls
        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc['walls_percent'])) {
                $multiplier += ($dominion->calc['walls_percent'] / 100);
            }
        } else {
            $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'walls');
        }

        // Racial Spell
        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc['howling'])) {
                $multiplier += ($spellHowling / 100);
            }

            if (isset($dominion->calc['blizzard'])) {
                $multiplier += ($spellBlizzard / 100);
            }

            if (isset($dominion->calc['defensive_frenzy'])) {
                $multiplier += ($spellFrenzy / 100);
            }

            if (!isset($dominion->calc['howling']) && !isset($dominion->calc['blizzard']) && !isset($dominion->calc['defensive_frenzy']) && isset($dominion->calc['ares_call'])) {
                $multiplier += ($spellAresCall / 100);
            }
        } else {
            // Spell: Howling (+10%)
            $multiplierFromHowling = $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'howling', $spellHowling);
            $multiplier += $multiplierFromHowling;

            // Spell: Blizzard (+15%)
            $multiplierFromBlizzard = $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'blizzard', $spellBlizzard);
            $multiplier += $multiplierFromBlizzard;

            // Spell: Frenzy (Halfling) (+20%)
            $multiplierFromFrenzy = $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'defensive_frenzy', $spellFrenzy);
            $multiplier += $multiplierFromFrenzy;

            // Spell: Ares' Call (+10%)
            if ($multiplierFromHowling == 0 && $multiplierFromBlizzard == 0 && $multiplierFromFrenzy == 0) {
                $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'ares_call', $spellAresCall);
            }
        }

        // Multiplier reduction when we want to factor in temples from another dominion
        $multiplier = max(($multiplier - $multiplierReduction), 1);

        return $multiplier;
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

    /**
     * Returns the Dominion's modifier reduction from temples.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @return float
     */
    public function getTempleReduction(Dominion $dominion): float
    {
        // Values (percentages)
        $dpReductionPerTemple = 1.5;
        $templeMaxDpReduction = 25;
        $dpMultiplierReduction = 0;

        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc['temple_percent'])) {
                $dpMultiplierReduction = min(
                    (($dpReductionPerTemple * $dominion->calc['temple_percent']) / 100),
                    ($templeMaxDpReduction / 100)
                );
            }
        } else {
            if ($dominion !== null) {
                $dpMultiplierReduction = min(
                    (($dpReductionPerTemple * $dominion->building_temple) / $this->landCalculator->getTotalLand($dominion)),
                    ($templeMaxDpReduction / 100)
                );
            }
        }

        return $dpMultiplierReduction;
    }

    public function getUnitPowerWithPerks(
        Dominion $dominion,
        ?Dominion $target,
        ?float $landRatio,
        Unit $unit,
        string $powerType
    ): float
    {
        $unitPower = $unit->{"power_$powerType"};

        $unitPower += $this->getUnitPowerFromLandBasedPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromBuildingBasedPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromRawWizardRatioPerk($dominion, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromPrestigePerk($dominion, $unit, $powerType);

        if ($landRatio !== null) {
            $unitPower += $this->getUnitPowerFromStaggeredLandRangePerk($dominion, $landRatio, $unit, $powerType);
        }

        $unitPower += $this->getUnitPowerFromVersusRacePerk($dominion, $target, $unit, $powerType);
        $unitPower += $this->getUnitPowerFromVersusBuildingPerk($dominion, $target, $unit, $powerType);

        return $unitPower;
    }

    protected function getUnitPowerFromLandBasedPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $landPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_land", null);

        if (!$landPerkData) {
            return 0;
        }

        $landType = $landPerkData[0];
        $ratio = (int)$landPerkData[1];
        $max = (int)$landPerkData[2];
        $constructedOnly = false;
        //$constructedOnly = $landPerkData[3]; todo: implement for Nox?
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        if (!$constructedOnly)
        {
            $landPercentage = ($dominion->{"land_{$landType}"} / $totalLand) * 100;
        }
        else
        {
            $buildingsForLandType = $this->buildingCalculator->getTotalBuildingsForLandType($dominion, $landType);
            $landPercentage = ($buildingsForLandType / $totalLand) * 100;
        }

        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc["{$landType}_percent"])) {
                $landPercentage = (float) $dominion->calc["{$landType}_percent"];
            }
        }

        $powerFromLand = $landPercentage / $ratio;
        $powerFromPerk = min($powerFromLand, $max);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromBuildingBasedPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $buildingPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_building", null);

        if (!$buildingPerkData) {
            return 0;
        }

        $buildingType = $buildingPerkData[0];
        $ratio = (int)$buildingPerkData[1];
        $max = (int)$buildingPerkData[2];
        $totalLand = $this->landCalculator->getTotalLand($dominion);
        $landPercentage = ($dominion->{"building_{$buildingType}"} / $totalLand) * 100;

        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc["{$buildingType}_percent"])) {
                $landPercentage = (float) $dominion->calc["{$buildingType}_percent"];
            }
        }

        $powerFromBuilding = $landPercentage / $ratio;
        $powerFromPerk = min($powerFromBuilding, $max);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromRawWizardRatioPerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $wizardRatioPerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_raw_wizard_ratio"
        );

        if (!$wizardRatioPerk) {
            return 0;
        }

        $ratio = (float)$wizardRatioPerk[0];
        $max = (int)$wizardRatioPerk[1];

        $wizardRawRatio = $this->getWizardRatioRaw($dominion, 'offense');

        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc['wizard_ratio'])) {
                $wizardRawRatio = (float) $dominion->calc['wizard_ratio'];
            } else {
                $wizardRawRatio = 3;
            }
        }

        $powerFromWizardRatio = $wizardRawRatio * $ratio;
        $powerFromPerk = min($powerFromWizardRatio, $max);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromPrestigePerk(Dominion $dominion, Unit $unit, string $powerType): float
    {
        $prestigePerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_from_prestige"
        );

        if (!$prestigePerk) {
            return 0;
        }

        $amount = (float)$prestigePerk[0];
        $max = (int)$prestigePerk[1];

        $powerFromPerk = min($dominion->prestige / $amount, $max);

        return $powerFromPerk;
    }

    protected function getUnitPowerFromStaggeredLandRangePerk(Dominion $dominion, float $landRatio = null, Unit $unit, string $powerType): float
    {
        $staggeredLandRangePerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_staggered_land_range"
        );

        if (!$staggeredLandRangePerk) {
            return 0;
        }

        if ($landRatio === null) {
            $landRatio = 0;
        }

        $powerFromPerk = 0;

        foreach ($staggeredLandRangePerk as $rangePerk) {
            $range = ((int)$rangePerk[0]) / 100;
            $power = (float)$rangePerk[1];

            if ($range > $landRatio) { // TODO: Check this, might be a bug here
                continue;
            }

            $powerFromPerk = $power;
        }

        return $powerFromPerk;
    }

    protected function getUnitPowerFromVersusRacePerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType): float
    {
        if ($target === null) {
            return 0;
        }

        $raceNameFormatted = strtolower($target->race->name);
        $raceNameFormatted = str_replace(' ', '_', $raceNameFormatted);

        $versusRacePerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_vs_{$raceNameFormatted}"
        );

        return $versusRacePerk;
    }

    protected function getBonusPowerFromPairingPerk(Dominion $dominion, Unit $unit, string $powerType, array $units = null): float
    {
        $pairingPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_from_pairing", null);
        if (!$pairingPerkData) {
            return 0;
        }

        $unitSlot = (int)$pairingPerkData[0];
        $amount = (int)$pairingPerkData[1];
        if (isset($pairingPerkData[2])) {
            $numRequired = (int)$pairingPerkData[2];
        } else {
            $numRequired = 1;
        }

        $powerFromPerk = 0;
        $numberPaired = 0;
        if ($units === null) {
            $numberPaired = min($dominion->{'military_unit' . $unit->slot}, floor((int)$dominion->{'military_unit' . $unitSlot} / $numRequired));
        } elseif (isset($units[$unitSlot]) && ((int)$units[$unitSlot] !== 0)) {
            $numberPaired = min($units[$unit->slot], floor((int)$units[$unitSlot] / $numRequired));
        }
        $powerFromPerk = $numberPaired * $amount;

        return $powerFromPerk;
    }

    protected function getUnitPowerFromVersusBuildingPerk(Dominion $dominion, Dominion $target = null, Unit $unit, string $powerType): float
    {
        if ($target === null && $dominion->calc === null) {
            return 0;
        }

        $versusBuildingPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, "{$powerType}_vs_building", null);
        if (!$versusBuildingPerkData) {
            return 0;
        }

        $buildingType = $versusBuildingPerkData[0];
        $ratio = (int)$versusBuildingPerkData[1];
        $max = (int)$versusBuildingPerkData[2];

        $landPercentage = 0;
        if ($dominion->calc !== null) {
            # Override building percentage for calculators
            if (isset($dominion->calc["target_{$buildingType}_percent"])) {
                $landPercentage = (float) $dominion->calc["target_{$buildingType}_percent"];
            }
        } elseif ($target !== null) {
            $totalLand = $this->landCalculator->getTotalLand($target);
            $landPercentage = ($target->{"building_{$buildingType}"} / $totalLand) * 100;
        }

        $powerFromBuilding = $landPercentage / $ratio;
        if ($max < 0) {
            $powerFromPerk = max(-1 * $powerFromBuilding, $max);
        } else {
            $powerFromPerk = min($powerFromBuilding, $max);
        }

        return $powerFromPerk;
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
        $multiplier = 1;

        // Values (percentages)
        $forestHavenBonus = 2;
        $forestHavenBonusMax = 20;

        // Forest Havens
        $multiplier += min(
            (($dominion->building_forest_haven / $this->landCalculator->getTotalLand($dominion)) * $forestHavenBonus),
            ($forestHavenBonusMax / 100)
        );

        // Racial bonus
        $multiplier += $dominion->race->getPerkMultiplier('spy_strength');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('spy_strength');

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('spy_strength');

        return $multiplier;
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

        // Forest Havens
        $spyStrengthPerForestHaven = 0.1;
        $spyStrengthPerForestHavenMax = 2;

        $regen += min(
            ($dominion->building_forest_haven / $this->landCalculator->getTotalLand($dominion)) * (100 * $spyStrengthPerForestHaven),
            $spyStrengthPerForestHavenMax
        );

        // Techs
        $regen += $dominion->getTechPerkValue('spy_strength_recovery');

        return $regen;
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
        $multiplier = 1;

        if ($dominion->race->name !== 'Dark Elf') {
            // Values (percentages)
            $wizardGuildBonus = 2;
            $wizardGuildBonusMax = 20;

            // Wizard Guilds
            $multiplier += min(
                (($dominion->building_wizard_guild / $this->landCalculator->getTotalLand($dominion)) * $wizardGuildBonus),
                ($wizardGuildBonusMax / 100)
            );
        }

        // Racial bonus
        $multiplier += $dominion->race->getPerkMultiplier('wizard_strength');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('wizard_strength');

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('wizard_strength');

        // Improvement: Towers
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'towers');

        return $multiplier;
    }

    /**
     * Returns the Dominion's wizard strength regeneration.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardStrengthRegen(Dominion $dominion): float
    {
        $regen = 4;

        // Wizard Guilds
        $wizardStrengthPerWizardGuild = 0.1;
        $wizardStrengthPerWizardGuildMax = 2;

        $regen += min(
            ($dominion->building_wizard_guild / $this->landCalculator->getTotalLand($dominion)) * (100 * $wizardStrengthPerWizardGuild),
            $wizardStrengthPerWizardGuildMax
        );

        // Techs
        $regen += $dominion->getTechPerkValue('wizard_strength_recovery');

        if ($dominion->wizard_strength < 25) {
            $regen += 1;
        }

        return $regen;
    }

    /**
     * Returns the number of boats protected by a Dominion's docks and harbor improvements.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getBoatsProtected(Dominion $dominion): float
    {
        // Docks
        $boatsProtected = static::BOATS_PROTECTED_PER_DOCK * $dominion->building_dock * min(2.5, 0.1 * $dominion->round->daysInRound());

        // Habor
        $boatsProtected *= (1 + $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'harbor') * 2);

        return $boatsProtected;
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
     * Returns the number of times the Dominion was recently invaded.
     *
     * 'Recent' defaults to the past 24 hours.
     *
     * @param Dominion $dominion
     * @param int $hours
     * @param Dominion $attacker
     * @return int
     */
    public function getRecentlyInvadedCount(Dominion $dominion, int $hours = 24, Dominion $attacker = null): int
    {
        // todo: this touches the db. should probably be in invasion or military service instead
        $invasionEvents = GameEvent::query()
            ->where('created_at', '>=', now()->subHours($hours)->startOfHour())
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
            return !$event->data['result']['overwhelmed'];
        });

        if ($attacker !== null) {
            return $invasionEvents->filter(function (GameEvent $event) use ($attacker) {
                return $event->source_id == $attacker->id && $event->data['result']['success'];
            })->count();
        }

        return $invasionEvents->count();
    }

    /**
     * Returns ids of attackers that recently invaded a Dominion.
     *
     * 'Recent' defaults to the past 24 hours.
     *
     * @param Dominion $dominion
     * @param int $hours
     * @return bool
     */
    public function getRecentlyInvadedBy(Dominion $dominion, int $hours = 24): array
    {
        // todo: this touches the db. should probably be in invasion or military service instead
        $invasionEvents = GameEvent::query()
            ->where('created_at', '>=', now()->subHours($hours)->startOfHour())
            ->where([
                'target_type' => Dominion::class,
                'target_id' => $dominion->id,
                'type' => 'invasion',
            ])
            ->pluck('source_id')
            ->all();

        return $invasionEvents;
    }

    /**
     * Gets amount of raw DP that Dominion has relased.
     *
     * 'Recent' refers to the past 24 hours.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefenseReducedRecently(Dominion $dominion): float
    {
        $defenseReduced = 0;

        $releaseEvents = DB::table('dominion_history')
            ->where('dominion_id', $dominion->id)
            ->where('event', 'release')
            ->where('created_at', '>=', now()->subDay(1))
            ->get();

        foreach ($releaseEvents as $release) {
            $delta = json_decode($release->delta);
            if (isset($delta->defense_reduced)) {
                $defenseReduced += $delta->defense_reduced;
            }
        }

        return $defenseReduced;
    }

    /**
     * Gets minimum DP for a Dominion based on land size.
     *
     * @param Dominion $dominion
     * @param int $landSize
     * @return float
     */
    public function getMinimumDefense(?Dominion $dominion, int $landSize = 0): float
    {
        if ($dominion !== null) {
            $landSize = $this->landCalculator->getTotalLand($dominion);
        }

        // Values
        $minDefenseMultiplier = 3;

        return max(0, $minDefenseMultiplier * $landSize);
    }
}
