<?php

namespace OpenDominion\Calculators\Dominion;

use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\ProtectionService;

class RangeCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /**
     * RangeCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     * @param ProtectionService $protectionService
     */
    public function __construct(LandCalculator $landCalculator, ProtectionService $protectionService)
    {
        $this->landCalculator = $landCalculator;
        $this->protectionService = $protectionService;
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
//        $targetModifier = $this->getRangeModifier($target);

        return (
            ($targetLand >= ($selfLand * $selfModifier)) &&
            ($targetLand <= ($selfLand / $selfModifier))
            // todo: selfland .. targetLand * targetModifier
        );
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

        if ($range >= 133) {
            return 'text-red';
        }

        if ($range >= 120) {
            return 'text-orange';
        }

        if ($range >= 75) {
            return 'text-yellow';
        }

        if ($range >= 66) {
            return 'text-green';
        }

        return 'text-muted';
    }

    /**
     * Get the dominion range modifier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getRangeModifier(Dominion $dominion): float
    {
        // todo: if EG then $modifier = 0.75, else if RG then $modifier = 0.6, else $modifier = 0.4
        return 0.6;
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
//                    $this->isInRange($dominion, $self) && // todo: needed?
                    !$this->protectionService->isUnderProtection($dominion)
                );
            })
            ->sortByDesc(function ($dominion) {
                return $this->landCalculator->getTotalLand($dominion);
            })
            ->values();
    }
}
