<?php

namespace OpenDominion\Services\Dominion;

use Illuminate\Support\Collection;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Models\Realm;

class InfoOpService
{
    /** @var EspionageHelper */
    protected $espionageHelper;

    /** @var SpellHelper */
    protected $spellHelper;

    /** @var Collection **/
    protected $infoSpells;

    /**
     * InfoOpService constructor.
     *
     * @param EspionageHelper $espionageHelper
     * @param SpellHelper $spellHelper
     */
    public function __construct(EspionageHelper $espionageHelper, SpellHelper $spellHelper)
    {
        $this->espionageHelper = $espionageHelper;
        $this->spellHelper = $spellHelper;
        $this->infoSpells = $this->spellHelper->getSpells(null, 'info');
    }

    public function hasInfoOps(Realm $sourceRealm, Dominion $targetDominion): bool
    {
        return ($sourceRealm->infoOps->filter(function (InfoOp $infoOp) use ($targetDominion) {
                return (
                    ($infoOp->target_dominion_id === $targetDominion->id)
                );
            })->count() > 0);
    }

    public function hasActiveInfoOp(Realm $sourceRealm, Dominion $targetDominion, string $type): bool
    {
        return ($sourceRealm->infoOps->filter(function (InfoOp $infoOp) use ($targetDominion, $type) {
                return (
                    !$infoOp->isInvalid() &&
                    ($infoOp->target_dominion_id === $targetDominion->id) &&
                    ($infoOp->type === $type)
                );
            })->count() > 0);
    }

    public function getInfoOp(Realm $sourceRealm, Dominion $targetDominion, string $type): ?InfoOp
    {
        return $sourceRealm->infoOps->filter(function (InfoOp $infoOp) use ($targetDominion, $type) {
            return (
                ($infoOp->target_dominion_id === $targetDominion->id) &&
                ($infoOp->type === $type)
            );
        })->sortByDesc('created_at')->first();
    }

    public function getInfoOpForRealm(Realm $sourceRealm, Realm $targetRealm, string $type): ?InfoOp
    {
        return $sourceRealm->infoOps->filter(function (InfoOp $infoOp) use ($targetRealm, $type) {
            return (
                ($infoOp->type === $type) &&
                ($infoOp->target_realm_id == $targetRealm->id)
            );
        })->sortByDesc('created_at')->first();
    }

    public function getOffensivePower(Realm $sourceRealm, Dominion $targetDominion): ?int
    {
        // mag: clear sight (units raw value, racial op bonus & prestige)
        // esp: castle spy (improvements)
        // survery dominion (mod buildings)
        // mag: revelation (active spells)
        // mag: vision (tech)
        // mag: disclosure (wonder)

        return null;
    }

    public function getOffensivePowerString(Realm $sourceRealm, Dominion $targetDominion): string
    {
        $op = $this->getOffensivePower($sourceRealm, $targetDominion);

        if ($op === null) {
            return '???';
        }

        return 'todo';
    }

    public function getDefensivePower(Realm $sourceRealm, Dominion $targetDominion): ?int
    {
        // clear sight (units + draftees + land

        return null;
    }

    public function getDefensivePowerString(Realm $sourceRealm, Dominion $targetDominion): string
    {
        $dp = $this->getDefensivePower($sourceRealm, $targetDominion);

        if ($dp === null) {
            return '???';
        }

        return 'todo';
    }

    public function getInfoOpName(InfoOp $infoOp): string
    {
        if ($infoOp->type == 'clairvoyance') return 'Clairvoyance';

        $spell = $this->infoSpells->get($infoOp->type);
        if ($spell !== null) {
            return $spell->name;
        }

        return $this->espionageHelper->getInfoGatheringOperations()
            ->filter(function ($op) use ($infoOp) {
                return ($op['key'] === $infoOp->type);
            })
            ->first()['name'];
    }

    public function getLastClairvoyance(Realm $sourceRealm, Realm $targetRealm): ?InfoOp
    {
        return $sourceRealm->infoOps->filter(function ($infoOp) use ($targetRealm) {
            return ($infoOp->target_realm_id === $targetRealm->id && $infoOp->type == 'clairvoyance');
        })
            ->sortByDesc('created_at')
            ->first();
    }

    public function getNumberOfActiveInfoOps(Collection $ops): int
    {
        return $ops->filter(function ($op) {
                return !$op->isStale();
            })
            ->count();
    }

    public function getMaxInfoOps(): int
    {
        return $this->infoSpells->count() +
            $this->espionageHelper->getInfoGatheringOperations()->count();
    }
}
