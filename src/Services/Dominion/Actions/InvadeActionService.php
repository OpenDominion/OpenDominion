<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;
use Throwable;

class InvadeActionService
{
    use DominionGuardsTrait;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var RangeCalculator */
    protected $rangeCalculator;

    /**
     * InvadeActionService constructor.
     *
     * @param ProtectionService $protectionService
     * @param RangeCalculator $rangeCalculator
     */
    public function __construct(ProtectionService $protectionService, RangeCalculator $rangeCalculator)
    {
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

            if (!$this->hasUnitsAtHome($dominion, $units)) {
                throw new RuntimeException('You don\'t have enough units at home to send this amount');
            }

            if ($dominion->morale < 70) {
                throw new RuntimeException('You do not have enough morale to invade others');
            }

            // check if we have enough boats
            // 33% rule
            // 5:4 rule


            // VARIABLES

            $totalRawDP = 0;
            $totalNetOP = 0;

            $targetNetDP = 0; // including temples

            $invasionSuccessful = ($totalNetOP > $targetNetDP);


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
    protected function hasUnitsAtHome(Dominion $dominion, array $units): bool
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
}
