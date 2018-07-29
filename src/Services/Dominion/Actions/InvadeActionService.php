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

            $result = null;

            // check if we actually have all the $units _at home_
            // check morale (min 70%)
            // check if we have enough boats
            // 33% rule
            // 5:4 rule

            $totalRawDP = 0;
            $totalNetOP = 0;

            $targetNetDP = 0; // including temples

            $invasionSuccessful = ($totalNetOP > $targetNetDP);

            $eligibleForPrestigeGain = false; // min 75% range and $target not invaded 3+ times within 24 hrs
            $prestigeGain = 0; // 5% $target->prestige + 20
            $targetPrestigeLoss = 0; // 5%

            $offensiveCasualties = 0; // 8.5% needed to break the target, *2 if !$invasionSuccessful
            $targetDefensiveCasualties = 0; // 6.6% at 1.0 land size ratio (see issue #151)

            // casualty modifiers step 1 (reductions like cleric/shaman, shrine for offensive). Capped at 80% reduction?
            // casualty modifiers, step 2 (reduction based recent invasions in 24hrs: 100% 80% 60% 55% 45% 35%)

            // (note: defensive casualties are spread out in ratio between all units that help def (have DP), including draftees)

            // calculate total conquered, 10% of their total land
            // calculate land gain (array)
            // calculate extra land generated (array) (always 50% %landConquered)
            // calculate their barren land losses (array)
            // calculate their buildings destroyed (array)

            // calc morale loss (5%) (see issue #151)
            // calc target morale loss (needed?)

            // todo: converts

        });

        dd([
            'units' => $units,
        ]);

        return [];
    }
}
