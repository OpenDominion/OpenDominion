<?php

namespace OpenDominion\Calculators\Dominion;

use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\ProtectionService;

class RangeCalculator
{
    public const MINIMUM_RANGE = 0.4;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var GuardMembershipService */
    protected $guardMembershipService;

    /**
     * RangeCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     * @param ProtectionService $protectionService
     */
    public function __construct(
        LandCalculator $landCalculator,
        ProtectionService $protectionService,
        GuardMembershipService $guardMembershipService)
    {
        $this->landCalculator = $landCalculator;
        $this->protectionService = $protectionService;
        $this->guardMembershipService = $guardMembershipService;
    }

    /**
     * Checks whether dominion $target is in range of dominion $self.
     *
     * @param Dominion $self
     * @param Dominion $target
     * @return bool
     */
    public function isInRange(Dominion $self, Dominion $target): bool
    {
        $selfLand = $this->landCalculator->getTotalLand($self);
        $targetLand = $this->landCalculator->getTotalLand($target);

        $selfModifier = $this->getRangeModifier($self);
        $targetModifier = $this->getRangeModifier($target);

        return (
            ($targetLand >= ($selfLand * $selfModifier)) &&
            ($targetLand <= ($selfLand / $selfModifier)) &&
            ($selfLand >= ($targetLand * $targetModifier)) &&
            ($selfLand <= ($targetLand / $targetModifier))
        );
    }

    /**
     * Resets guard application status of $self dominion if $target dominion is out of guard range.
     *
     * @param Dominion $self
     * @param Dominion $target
     */
    public function checkGuardApplications(Dominion $self, Dominion $target)
    {
        $isRoyalGuardApplicant = $this->guardMembershipService->isRoyalGuardApplicant($self);
        $isEliteGuardApplicant = $this->guardMembershipService->isEliteGuardApplicant($self);

        if ($isRoyalGuardApplicant || $isEliteGuardApplicant) {
            $selfLand = $this->landCalculator->getTotalLand($self);
            $targetLand = $this->landCalculator->getTotalLand($target);
            // Reset Royal Guard application if out of range
            if($isRoyalGuardApplicant) {
                $guardModifier = $this->guardMembershipService::ROYAL_GUARD_RANGE;
                if(($targetLand < ($selfLand * $guardModifier)) || ($targetLand > ($selfLand / $guardModifier))) {
                    $this->guardMembershipService->joinRoyalGuard($self);
                }
            }
            // Reset Elite Guard application if out of range
            if($isEliteGuardApplicant) {
                $guardModifier = $this->guardMembershipService::ELITE_GUARD_RANGE;
                if(($targetLand < ($selfLand * $guardModifier)) || ($targetLand > ($selfLand / $guardModifier))) {
                    $this->guardMembershipService->joinEliteGuard($self);
                }
            }
        }
    }

    /**
     * Returns the $target dominion range compared to $self dominion.
     *
     * Return value is a percentage (eg 114.28~) used for displaying. For calculation purposes, divide this by 100.
     *
     * @todo: should probably change this (and all its usages) to return without *100
     *
     * @param Dominion $self
     * @param Dominion $target
     * @return float
     */
    public function getDominionRange(Dominion $self, Dominion $target): float
    {
        $selfLand = $this->landCalculator->getTotalLand($self);
        $targetLand = $this->landCalculator->getTotalLand($target);

        return (($targetLand / $selfLand) * 100);
    }

    /**
     * Helper function to return a colored <span> class for a $target dominion range.
     *
     * @param Dominion $self
     * @param Dominion $target
     * @return string
     */
    public function getDominionRangeSpanClass(Dominion $self, Dominion $target): string
    {
        $range = $this->getDominionRange($self, $target);

        if ($range >= 120) {
            return 'text-red';
        }

        if ($range >= 75) {
            return 'text-green';
        }

        if ($range >= 66) {
            return 'text-muted';
        }

        return 'text-gray';
    }

    /**
     * Get the dominion range modifier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getRangeModifier(Dominion $dominion): float
    {
        if ($this->guardMembershipService->isEliteGuardMember($dominion)) {
            return $this->guardMembershipService::ELITE_GUARD_RANGE;
        } elseif ($this->guardMembershipService->isRoyalGuardMember($dominion)) {
            return $this->guardMembershipService::ROYAL_GUARD_RANGE;
        } else {
            return self::MINIMUM_RANGE;
        }
    }

    /**
     * Returns all dominions in range of a dominion.
     *
     * @param Dominion $self
     * @return Collection
     */
    public function getDominionsInRange(Dominion $self): Collection
    {
        // todo: this doesn't belong here since it touches the db. Move to RangeService?
        return $self->round->dominions()
            ->with(['realm', 'round'])
            ->get()
            ->filter(function ($dominion) use ($self) {
                return (
                    ($dominion->realm->id !== $self->realm->id) &&
                    $this->isInRange($self, $dominion) &&
                    !$this->protectionService->isUnderProtection($dominion)
                );
            })
            ->sortByDesc(function ($dominion) {
                return $this->landCalculator->getTotalLand($dominion);
            })
            ->values();
    }
}
