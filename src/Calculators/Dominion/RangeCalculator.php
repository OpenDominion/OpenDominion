<?php

namespace OpenDominion\Calculators\Dominion;

use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;

class RangeCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * RangeCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     */
    public function __construct(LandCalculator $landCalculator)
    {
        $this->landCalculator = $landCalculator;
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
            ($targetLand <= ($selfLand / $selfModifier))
            // todo: selfland .. targetLand * targetModifier
        );
    }

    /**
     * Returns the $target dominion range compared to $self dominion.
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
            ->with('realm')
            ->get()
            ->filter(function ($dominion) use ($self) {
                return (
                    ($dominion->realm->id !== $self->realm->id) &&
                    $this->isInRange($self, $dominion)
                );
            })
            ->sortByDesc(function ($dominion) {
                return $this->landCalculator->getTotalLand($dominion);
            })
            ->values();
    }
}
