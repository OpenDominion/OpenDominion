<?php

namespace OpenDominion\Calculators\Dominion;

use DB;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Unit;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\QueueService;

class MilitaryCalculator
{
    /**
     * @var float Number of boats protected per dock
     */
    protected const BOATS_PROTECTED_PER_DOCK = 2.25;

    /**
     * @var int Number of units each boat carries
     */
    protected const UNITS_PER_BOAT = 30;

    /**
     * @var float Land loss multiplier compared to DC formula
     */
    protected const LAND_LOSS_MULTIPLIER = 0.75;

    /**
     * @var float Amount of generated land relative to land lost
     */
    public const LAND_GEN_RATIO = 1.00;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var GovernmentService */
    protected $governmentService;

    /** @var HeroCalculator */
    protected $heroCalculator;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var SpellHelper */
    protected $spellHelper;

    /** @var bool */
    protected $forTick = false;

    /**
     * MilitaryCalculator constructor.
     *
     * @param BuildingCalculator $buildingCalculator
     * @param GovernmentService $governmentService
     * @param HeroCalculator $heroCalculator
     * @param ImprovementCalculator $improvementCalculator
     * @param LandCalculator $landCalculator
     * @param QueueService $queueService
     * @param SpellCalculator $spellCalculator
     * @param SpellHelper $spellHelper
     */
    public function __construct(
        BuildingCalculator $buildingCalculator,
        GovernmentService $governmentService,
        HeroCalculator $heroCalculator,
        ImprovementCalculator $improvementCalculator,
        LandCalculator $landCalculator,
        QueueService $queueService,
        SpellCalculator $spellCalculator,
        SpellHelper $spellHelper
    )
    {
        $this->buildingCalculator = $buildingCalculator;
        $this->governmentService = $governmentService;
        $this->heroCalculator = $heroCalculator;
        $this->improvementCalculator = $improvementCalculator;
        $this->landCalculator = $landCalculator;
        $this->queueService = $queueService;
        $this->spellCalculator = $spellCalculator;
        $this->spellHelper = $spellHelper;
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

        // Gryphon Nests
        $multiplier += $this->getOffensivePowerMultiplierFromBuildings($dominion);

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
        $multiplier += $this->getOffensivePowerMultiplierFromImprovements($dominion);

        // Spells
        $multiplier += $this->getOffensivePowerMultiplierFromSpells($dominion);

        // Prestige
        $multiplier += $this->getOffensivePowerMultiplierFromPrestige($dominion);

        // War
        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc['war_bonus'])) {
                $multiplier += ($dominion->calc['war_bonus'] / 100);
            }
        } else {
            if ($target !== null && $dominion->realm !== null) {
                if ($this->governmentService->isMutualWarEscalated($dominion->realm, $target->realm)) {
                    $multiplier += 0.1;
                } elseif ($this->governmentService->isWarEscalated($dominion->realm, $target->realm) || $this->governmentService->isWarEscalated($target->realm, $dominion->realm)) {
                    $multiplier += 0.05;
                }
            }
        }

        // Heroes
        if ($dominion->hero !== null) {
            $multiplier += $dominion->hero->getPerkMultiplier('offense');
        }

        return $multiplier;
    }

    /**
     * Returns the Dominion's offensive power modifier from buildings.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerMultiplierFromBuildings(Dominion $dominion): float
    {
        $multiplier = 0;

        // Values (percentages)
        $opPerGryphonNest = 1.6;
        $gryphonNestMaxOp = 32;

        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc['gryphon_nest_percent'])) {
                $multiplier += min(
                    ($opPerGryphonNest * $dominion->calc['gryphon_nest_percent'] / 100),
                    ($gryphonNestMaxOp / 100)
                );
            }
        } else {
            $multiplier += min(
                ($opPerGryphonNest * $dominion->building_gryphon_nest / $this->landCalculator->getTotalLand($dominion)),
                ($gryphonNestMaxOp / 100)
            );
        }

        return $multiplier;
    }

    /**
     * Returns the Dominion's offensive power modifier from castle improvements.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerMultiplierFromImprovements(Dominion $dominion): float
    {
        $multiplier = 0;

        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc['forges_percent'])) {
                $multiplier += ($dominion->calc['forges_percent'] / 100);
            }
        } else {
            $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'forges');
        }

        return $multiplier;
    }

    /**
     * Returns the Dominion's offensive power modifier from spells.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerMultiplierFromSpells(Dominion $dominion): float
    {
        $multiplier = 0;

        // Values (percentages)
        $spellFavorableTerrain = 1;
        $spellFavorableTerrainCap = 10;

        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            $offenseSpells = $this->spellHelper->getSpellsWithPerk('offense');

            foreach ($offenseSpells->sortByDesc('pivot.value') as $spell) {
                if (isset($dominion->calc[$spell->key])) {
                    $multiplier += ($spell->getPerkValue('offense') / 100);
                    break;
                }
            }

            if (isset($dominion->calc['favorable_terrain'])) {
                $multiplier += min(
                    ($spellFavorableTerrain * $dominion->calc['barren_percent'] / 100),
                    ($spellFavorableTerrainCap / 100)
                );
            }
        } else {
            $multiplier += $dominion->getSpellPerkMultiplier('offense');

            if ($dominion->getSpellPerkValue('offense_from_barren_land')) {
                $multiplier += min(
                    ($spellFavorableTerrain * $this->landCalculator->getTotalBarrenLand($dominion) / $this->landCalculator->getTotalLand($dominion)),
                    ($spellFavorableTerrainCap / 100)
                );
            }
        }

        return $multiplier;
    }

    /**
     * Returns the Dominion's offensive power modifier from prestige.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerMultiplierFromPrestige(Dominion $dominion): float
    {
        return ($dominion->prestige / 10000);
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

        // Guard Towers
        $multiplier += $this->getDefensivePowerMultiplierFromBuildings($dominion);

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('defense');

        // Techs
        // TODO: add to calc if this is implemented
        $multiplier += $dominion->getTechPerkMultiplier('defense');

        // Wonders
        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc['temple_of_the_damned_defender'])) {
                $multiplier += ($dominion->calc['wonder_defense'] / 100);
            }
        } else {
            $multiplier += $dominion->getWonderPerkMultiplier('defense');
        }

        // Improvement: Walls
        $multiplier += $this->getDefensivePowerMultiplierFromImprovements($dominion);

        // Spells
        $multiplier += $this->getDefensivePowerMultiplierFromSpells($dominion);

        // Multiplier reduction when we want to factor in temples from another dominion
        $multiplier = max(($multiplier - $multiplierReduction), 1);

        return $multiplier;
    }

    /**
     * Returns the Dominion's defensive power modifier from buildings.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePowerMultiplierFromBuildings(Dominion $dominion): float
    {
        $multiplier = 0;

        // Values (percentages)
        $dpPerGuardTower = 1.6;
        $guardTowerMaxDp = 32;

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

        return $multiplier;
    }

    /**
     * Returns the Dominion's defensive power modifier from castle improvements.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePowerMultiplierFromImprovements(Dominion $dominion): float
    {
        $multiplier = 0;

        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc['walls_percent'])) {
                $multiplier += ($dominion->calc['walls_percent'] / 100);
            }
        } else {
            $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'walls');
        }

        return $multiplier;
    }

    /**
     * Returns the Dominion's defensive power modifier from spells.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePowerMultiplierFromSpells(Dominion $dominion): float
    {
        $multiplier = 0;

        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            $defenseSpells = $this->spellHelper->getSpellsWithPerk('defense');

            foreach ($defenseSpells->sortByDesc('pivot.value') as $spell) {
                if (isset($dominion->calc[$spell->key])) {
                    $multiplier += ($spell->getPerkValue('defense') / 100);
                    break;
                }
            }
        } else {
            // Spells
            $multiplier += $dominion->getSpellPerkMultiplier('defense');
        }

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
        $dpReductionPerTemple = 1.35;
        $templeMaxDpReduction = 27;
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

        // Wonders
        if ($dominion->calc !== null && !isset($dominion->calc['invasion'])) {
            if (isset($dominion->calc['temple_of_the_damned_attacker'])) {
                $dpMultiplierReduction -= ($dominion->calc['wonder_enemy_defense'] / 100);
            }
        } else {
            $dpMultiplierReduction -= $dominion->getWonderPerkMultiplier('enemy_defense');
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
        $unitPower += $this->getUnitPowerFromSpellPerk($dominion, $landRatio, $unit, $powerType);

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
        $ratio = (float)$landPerkData[1];
        $max = (float)$landPerkData[2];
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
        $ratio = (float)$buildingPerkData[1];
        $max = (float)$buildingPerkData[2];
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
        $max = (float)$wizardRatioPerk[1];

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
        $max = (float)$prestigePerk[1];

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

    protected function getUnitPowerFromSpellPerk(Dominion $dominion, float $landRatio = null, Unit $unit, string $powerType): float
    {
        $powerFromSpellPerk = $dominion->race->getUnitPerkValueForUnitSlot(
            $unit->slot,
            "{$powerType}_from_spell"
        );

        if (!$powerFromSpellPerk) {
            return 0;
        }

        if ($landRatio > 1) {
            return 0;
        }

        $powerFromPerk = 0;
        $spellKey = $powerFromSpellPerk[0];
        $power = (float)$powerFromSpellPerk[1];

        if (isset($dominion->calc['cull_the_weak'])) {
            $powerFromPerk = $power;
        } else {
            if ($this->spellCalculator->isSpellActive($dominion, $spellKey)) {
                $powerFromPerk = $power;
            }
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

        // Special Case for Infernal Command
        if (
            ($powerType == 'offense' && $unit->slot == 1) && (
                $this->spellCalculator->isSpellActive($dominion, 'infernal_command') ||
                ($dominion->calc !== null && !isset($dominion->calc['invasion']) && isset($dominion->calc['infernal_command']))
            )
        ) {
            $pairingPerkData = [4, 1, 1];
        }

        if (!$pairingPerkData) {
            return 0;
        }

        $unitSlot = (int)$pairingPerkData[0];
        $numRequired = (int)$pairingPerkData[1];
        $amount = (float)$pairingPerkData[2];

        $powerFromPerk = 0;
        $numberPaired = 0;
        if ($units === null) {
            $numberPaired = min($dominion->{'military_unit' . $unit->slot}, rfloor((int)$dominion->{'military_unit' . $unitSlot} / $numRequired));
        } elseif (isset($units[$unitSlot]) && ((int)$units[$unitSlot] !== 0)) {
            $numberPaired = min($units[$unit->slot], rfloor((int)$units[$unitSlot] / $numRequired));
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
        $ratio = (float)$versusBuildingPerkData[1];
        $max = (float)$versusBuildingPerkData[2];

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
        return ($this->getSpyRatioRaw($dominion, $type) * $this->getSpyRatioMultiplier($dominion, $type));
    }

    /**
     * Returns the Dominion's raw spy ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyRatioRaw(Dominion $dominion, string $type = 'offense'): float
    {
        $spies = $dominion->military_spies + ($dominion->military_assassins * 2);

        // Add units which count as (partial) spies (Lizardfolk Chameleon)
        foreach ($dominion->race->units as $unit) {
            if ($type === 'offense' && $unit->getPerkValue('counts_as_spy_offense')) {
                $spies += rfloor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_spy_offense'));
            }

            if ($type === 'defense' && $unit->getPerkValue('counts_as_spy_defense')) {
                $spies += rfloor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_spy_defense'));
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
    public function getSpyRatioMultiplier(Dominion $dominion, string $type = 'offense'): float
    {
        $multiplier = 1;

        // Racial bonus
        $multiplier += $dominion->race->getPerkMultiplier('spy_power');

        // Spells
        $multiplier += $this->spellCalculator->resolveSpellPerk($dominion, 'spy_power') / 100;
        if ($type == 'defense') {
            $multiplier += $this->spellCalculator->resolveSpellPerk($dominion, 'spy_power_defense') / 100;
        }

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('spy_power');
        if ($type == 'defense') {
            $multiplier += $dominion->getTechPerkMultiplier('spy_power_defense');
        }

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('spy_power');

        // Heroes
        $multiplier += $this->heroCalculator->getHeroPerkMultiplier($dominion, 'spy_power');
        $multiplier += $this->heroCalculator->getHeroPerkMultiplier($dominion, 'ops_power');

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

        // Techs
        $regen += $dominion->getTechPerkValue('spy_strength_recovery');

        // Mastery
        $maxMasteryBonus = 2;
        $regen += min(1000, $dominion->spy_mastery) / 1000 * $maxMasteryBonus;

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
        return ($this->getWizardRatioRaw($dominion, $type) * $this->getWizardRatioMultiplier($dominion, $type));
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

        // Add units which count as (partial) wizards (Dark Elf Adept)
        foreach ($dominion->race->units as $unit) {
            if ($type === 'offense' && $unit->getPerkValue('counts_as_wizard_offense')) {
                $wizards += rfloor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_wizard_offense'));
            }

            if ($type === 'defense' && $unit->getPerkValue('counts_as_wizard_defense')) {
                $wizards += rfloor($dominion->{"military_unit{$unit->slot}"} * (float) $unit->getPerkValue('counts_as_wizard_defense'));
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
    public function getWizardRatioMultiplier(Dominion $dominion, string $type = 'offense'): float
    {
        $multiplier = 1;

        // Racial bonus
        $multiplier += $dominion->race->getPerkMultiplier('wizard_power');

        // Spells
        $multiplier += $this->spellCalculator->resolveSpellPerk($dominion, 'wizard_power') / 100;
        if ($type == 'defense') {
            $multiplier += $this->spellCalculator->resolveSpellPerk($dominion, 'wizard_power_defense') / 100;
        }

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('wizard_power');

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('wizard_power');

        // Heroes
        $multiplier += $this->heroCalculator->getHeroPerkMultiplier($dominion, 'wizard_power');
        $multiplier += $this->heroCalculator->getHeroPerkMultiplier($dominion, 'ops_power');

        // Improvement: Spires
        if ($type == 'offense') {
            $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'spires');
        }

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

        // Techs
        $regen += $dominion->getTechPerkValue('wizard_strength_recovery');

        // Mastery
        $maxMasteryBonus = 2;
        $regen += min(1000, $dominion->wizard_mastery) / 1000 * $maxMasteryBonus;

        // Resilience bonus when snared
        if ($dominion->wizard_strength < 30) {
            $regen += ($dominion->resilience / 100);
        }

        return $regen;
    }

    /**
     * Returns the troop capacity of a Dominion's boats.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getBoatCapacity(Dominion $dominion): int
    {
        $boatCapacity = static::UNITS_PER_BOAT;

        // Racial Bonus
        $boatCapacity += $dominion->race->getPerkValue('boat_capacity');

        // Techs
        $boatCapacity += $dominion->getTechPerkValue('boat_capacity');

        return $boatCapacity;
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
        $boatsProtected = $dominion->building_dock * (static::BOATS_PROTECTED_PER_DOCK + (0.05 * $dominion->round->daysInRound()));

        // Habor
        $boatsProtected *= (1 + $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'harbor', true));

        return rceil($boatsProtected);
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
     * Gets the total land lost by a dominion when invaded.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @return int
     */
    public function getLandLost(Dominion $dominion, Dominion $target): int
    {
        $multiplier = 1;

        $attackerLand = $this->landCalculator->getTotalLand($dominion);
        $targetLand = $this->landCalculator->getTotalLand($target);
        $landRatio = ($targetLand / $attackerLand);

        // War Bonus
        if ($this->governmentService->isMutualWarEscalated($dominion->realm, $target->realm)) {
            $multiplier = 1.2;
        } elseif ($this->governmentService->isWarEscalated($dominion->realm, $target->realm) || $this->governmentService->isWarEscalated($target->realm, $dominion->realm)) {
            $multiplier = 1.1;
        }

        if ($landRatio < 0.55) {
            $acresLost = (0.304 * ($landRatio ** 2) - 0.227 * $landRatio + 0.048);
        } elseif ($landRatio < 0.75) {
            $acresLost = (0.154 * $landRatio - 0.069);
        } else {
            $acresLost = (0.129 * $landRatio - 0.048);
        }

        $acresLost *= (static::LAND_LOSS_MULTIPLIER * $attackerLand * $multiplier);
        return (int)max(rfloor($acresLost), 10);
    }

    /**
     * Returns the number of times the Dominion was recently invaded.
     *
     * 'Recent' defaults to the past 24 hours.
     *
     * @param Dominion $dominion
     * @param int $hours
     * @param bool $success_only
     * @param Dominion $attacker
     * @return int
     */
    public function getRecentlyInvadedCount(Dominion $dominion, int $hours = 24, bool $success_only = false, Dominion $attacker = null): int
    {
        // todo: this touches the db. should probably be in invasion or military service instead
        $invasionEvents = GameEvent::query()
            ->where('created_at', '>', now()->subHours($hours)->endOfHour())
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

        if ($success_only) {
            $invasionEvents = $invasionEvents->filter(function (GameEvent $event) {
                $successful = $event->data['result']['success'];
                $prestigious = true;
                if (isset($event->data['attacker']['landSize']) && isset($event->data['defender']['landSize'])) {
                    $prestigious = ($event->data['defender']['landSize'] / $event->data['attacker']['landSize']) >= 0.75;
                }
                return $successful && $prestigious;
            });
        }

        if ($attacker !== null) {
            $invasionEvents = $invasionEvents->filter(function (GameEvent $event) use ($attacker) {
                return $event->source_id == $attacker->id && $event->data['result']['success'];
            });
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
     * @return array
     */
    public function getRecentlyInvadedBy(Dominion $dominion, int $hours = 24): array
    {
        // todo: this touches the db. should probably be in invasion or military service instead
        $invasionEvents = GameEvent::query()
            ->where('created_at', '>', now()->subHours($hours)->endOfHour())
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
     * Returns the number of hours since the most recent invasion
     * by a Dominion against a specific Realm.
     *
     * @param Dominion $dominion
     * @param Realm $realm
     * @return int
     */
    public function getRetaliationHours(Dominion $dominion, Realm $realm): ?int
    {
        $dominionIds = $realm->dominions->pluck('id')->all();

        // todo: this touches the db. should probably be in invasion or military service instead
        $mostRecentInvasion = GameEvent::query()
            ->where([
                'source_type' => Dominion::class,
                'source_id' => $dominion->id,
                'type' => 'invasion',
                'target_type' => Dominion::class,
            ])
            ->whereIn('target_id', $dominionIds)
            ->max('created_at');

        if ($mostRecentInvasion === null) {
            return null;
        }

        return now()->endOfHour()->diffInHours($mostRecentInvasion);
    }

    /**
     * Returns count of attacks by a Dominion against a specific target.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @return bool
     */
    public function getHabitualInvasionCount(Dominion $dominion, Dominion $target): int
    {
        // todo: this touches the db. should probably be in invasion or military service instead
        $invasionEvents = GameEvent::query()
            ->where([
                'source_type' => Dominion::class,
                'source_id' => $dominion->id,
                'type' => 'invasion',
            ])
            ->get();

        $invasionEvents = $invasionEvents->filter(function (GameEvent $event) {
            if (!isset($event->data['result']['range'])) {
                return false;
            }
            return $event->data['result']['success'] && $event->data['result']['range'] >= 75;
        });

        return $invasionEvents->where('target.user_id', $target->user_id)->count();
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
            ->where('created_at', '>', now()->subDay(1))
            ->whereIn('event', ['destroy', 'release', 'rezone'])
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

        return max(750, 10 * $landSize - 3300);
    }
}
