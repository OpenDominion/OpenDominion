<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;
use Throwable;

class InvadeActionService
{
    use DominionGuardsTrait;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var RangeCalculator */
    protected $rangeCalculator;

    /**
     * InvadeActionService constructor.
     *
     * @param MilitaryCalculator $militaryCalculator
     * @param ProtectionService $protectionService
     * @param RangeCalculator $rangeCalculator
     */
    public function __construct(MilitaryCalculator $militaryCalculator, ProtectionService $protectionService, RangeCalculator $rangeCalculator)
    {
        $this->militaryCalculator = $militaryCalculator;
        $this->protectionService = $protectionService;
        $this->rangeCalculator = $rangeCalculator;
    }

    /**
     * Invades dominion $target from $dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @return array
     * @throws Throwable
     */
    public function invade(Dominion $dominion, Dominion $target, array $units): array
    {
        DB::transaction(function () use ($dominion, $target, $units) {

            // CHECKS

            $this->guardLockedDominion($dominion);

            if ($this->protectionService->isUnderProtection($dominion)) {
                throw new RuntimeException('You cannot invade while under protection');
            }

            if ($this->protectionService->isUnderProtection($target)) {
                throw new RuntimeException('You cannot invade dominions which are under protection');
            }

            if (!$this->rangeCalculator->isInRange($dominion, $target)) {
                throw new RuntimeException('You cannot invade dominions outside of your range');
            }

            if ($dominion->round->id !== $target->round->id) {
                throw new RuntimeException('Nice try, but you cannot invade cross-round');
            }

            if ($dominion->realm->id === $target->realm->id) {
                throw new RuntimeException('Nice try, but you cannot invade your realmies');
            }

            if (!$this->allUnitsHaveOP($dominion, $units)) {
                throw new RuntimeException('You cannot send units that have no OP');
            }

            if (!$this->hasEnoughUnitsAtHome($dominion, $units)) {
                throw new RuntimeException('You don\'t have enough units at home to send this many units');
            }

            if ($dominion->morale < 70) {
                throw new RuntimeException('You do not have enough morale to invade others');
            }

            if (!$this->hasEnoughBoats($dominion, $units)) {
                throw new RuntimeException('You do not have enough boats to send this many units');
            }

            $netOP = $this->getNetOP($dominion, $units);
            $totalNetDP = $this->militaryCalculator->getDefensivePower($dominion);
            $totalNetDPWithoutAttackingUnits = ($totalNetDP - $this->getNetDP($dominion, $units));

            // 33% rule
            // todo: test
            $DPNeededToLeaveAtHome = (int)floor($netOP / 3);
            if ($totalNetDPWithoutAttackingUnits < $DPNeededToLeaveAtHome) {
                throw new RuntimeException('You need to leave more defensive units at home (33% rule)');
            }

            // 5:4 rule
            // todo: test
            $allowedMaxOP = (int)floor($totalNetDP * 1.25);
            if ($netOP > $allowedMaxOP) {
                throw new RuntimeException('You need to leave more offensive units at home (5:4 rule)');
            }

            $targetNetDP = $this->militaryCalculator->getDefensivePower($target);

            $invasionSuccessful = ($netOP > $targetNetDP);

            dd([
                'net op' => $netOP,
                'net dp' => $totalNetDP,
                'net dp w/o attackers' => $totalNetDPWithoutAttackingUnits,
                'target net dp' => $targetNetDP,
                'success?' => $invasionSuccessful,
            ]);


            // PRESTIGE

            // if range < 66
                // $prestigeLoss = 5% (needs confirmation)
            // else if range >= 75 && range < 120
                // if !$invasionSuccesful
                    // if 1 - $totalNetOP / $targetNetDP >= 0.15 (fail by 15%, aka raze)
                        // $prestigeLoss = 5% (needs confirmation)
                // else
                    // $prestigeGain = 5% target->prestige + 20
                    // todo: in tech ruleset, multiply base prestige gain (i.e. the 5%) by shrines bonus
                    // if $target was successfully invaded recently (within 24 hrs), multiply $prestigeGain by: (needs confirmation)
                        // 1 time: 75%
                        // 2 times: 50%
                        // 3 times: 25%
                        // 4 times: -25% (i.e. losing prestige)
                        // 5+ times: -50%
                    // todo: if at war, increase $prestigeGain by +15%
                    // $targetPrestigeLoss = 5% target->prestige


            // CASUALTIES

            $offensiveCasualties = 0; // 8.5% needed to break the target, *2 if !$invasionSuccessful
            // offensive casualty modifiers (cleric/shaman, shrines), capped at -80% casualties (needs confirmation)

            $targetDefensiveCasualties = 0; // 6.6% at 1.0 land size ratio (see issue #151)
            // defensive casualty modifiers (reduction based on recent invasion: 100%, 80%, 60%, 55%, 45%, 35%)
            // (note: defensive casualties are spread out in ratio between all units that help def (have DP), including draftees)


            // LAND GAINS/LOSSES

            // if $invasionSuccessful
                // calculate total conquered acres; 10% of target total land
                // calculate target barren land losses (array), based on ratio of what the target has
                // calculate land conquers (array) (= target land loss)
                // calculate target buildings destroyed (array), only if target does not have enough barren land buffer, in ratio of buildings constructed per land type
                // calculate extra land generated (array) (always 50% of conquered land, even ratio across all 7 land types) (needs confirmation)


            // MORALE

            // calc morale loss (5%) (see issue #151)
            // calc target morale loss (?%) (only on $invasionSuccessful?) (needs confirmation)


            // MISC

            // if $invasionSuccessful
                // hobbos and other special units that trigger something upon invading
                // later: converts


            // insert queues for returning units, incoming land and incoming prestige
            // send notification to $target
            // todo: post to both TCs


            // shit for elsewhere:

            // todo: show message in Clear Sight at the bottom for dominions that have been invaded too much recently:
                // 1-2 times: "This dominion has been invaded in recent times"
                // 3-4 times: "This dominion has been invaded heavily in recent times"
                // 5+ times: "This dominion has been invaded extremely heavily in recent times"

            // todo: add battle reports table/mechanic
            // todo: add a table for incoming prestige to the database
            // todo: add 'boats needed'/'boats total' on invade page

        });

        dd([
            'units' => $units,
        ]);

        return [];
    }

    /**
     * Check if all units being sent have positive OP.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function allUnitsHaveOP(Dominion $dominion, array $units): bool
    {
        foreach ($dominion->race->units as $unit) {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0)) {
                continue;
            }

            if ($unit->power_offense === 0.0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if dominion has enough units at home to send out.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function hasEnoughUnitsAtHome(Dominion $dominion, array $units): bool
    {
        foreach ($dominion->race->units as $unit) {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0)) {
                continue;
            }

            if ($units[$unit->slot] > $dominion->{'military_unit' . $unit->slot}) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if dominion has enough boats to send units out.
     *
     * @param Dominion $dominion
     * @param array $units
     * @return bool
     */
    protected function hasEnoughBoats(Dominion $dominion, array $units): bool
    {
        $unitsPerBoat = 30;
        $unitsThatNeedBoats = 0;

        foreach ($dominion->race->units as $unit) {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0)) {
                continue;
            }

            if ($unit->need_boat) {
                $unitsThatNeedBoats += (int)$units[$unit->slot];
            }
        }

        return ($dominion->resource_boats >= ceil($unitsThatNeedBoats / $unitsPerBoat));
    }

    protected function getRawOP(Dominion $dominion, array $units): float
    {
        $op = 0;

        foreach ($dominion->race->units as $unit) {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0)) {
                continue;
            }

            $op += ($unit->power_offense * (int)$units[$unit->slot]);
        }

        return $op;
    }

    protected function getNetOP(Dominion $dominion, array $units): float
    {
        return ($this->getRawOP($dominion, $units) * $this->militaryCalculator->getOffensivePowerMultiplier($dominion));
    }

    protected function getRawDP(Dominion $dominion, array $units): float
    {
        $op = 0;

        foreach ($dominion->race->units as $unit) {
            if (!isset($units[$unit->slot]) || ((int)$units[$unit->slot] === 0)) {
                continue;
            }

            $op += ($unit->power_defense * (int)$units[$unit->slot]);
        }

        return $op;
    }

    protected function getNetDP(Dominion $dominion, array $units): float
    {
        return ($this->getRawDP($dominion, $units) * $this->militaryCalculator->getDefensivePowerMultiplier($dominion));
    }
}
