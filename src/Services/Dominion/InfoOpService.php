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

    public function getLand(Realm $sourceRealm, Dominion $targetDominion): ?int
    {
        if (!$this->hasActiveInfoOp($sourceRealm, $targetDominion, 'clear_sight')) {
            return null;
        }

        $clearSight = $this->getInfoOp($sourceRealm, $targetDominion, 'clear_sight');

        return $clearSight->data['land'];
    }

    public function getLandString(Realm $sourceRealm, Dominion $targetDominion): string
    {
        $land = $this->getLand($sourceRealm, $targetDominion);

        if ($land === null) {
            return '???';
        }

        $clearSight = $this->getInfoOp($sourceRealm, $targetDominion, 'clear_sight');

        $return = number_format($clearSight->data['land']);

        if ($clearSight->isStale()) {
            $return .= '?';
        }

        return $return;
    }

    public function getNetworth(Realm $sourceRealm, Dominion $targetDominion): ?int
    {
        if (!$this->hasActiveInfoOp($sourceRealm, $targetDominion, 'clear_sight')) {
            return null;
        }

        $clearSight = $this->getInfoOp($sourceRealm, $targetDominion, 'clear_sight');

        return $clearSight->data['networth'];
    }

    public function getNetworthString(Realm $sourceRealm, Dominion $targetDominion): string
    {
        $networth = $this->getNetworth($sourceRealm, $targetDominion);

        if ($networth === null) {
            return '???';
        }

        $clearSight = $this->getInfoOp($sourceRealm, $targetDominion, 'clear_sight');

        $return = number_format($clearSight->data['networth']);

        if ($clearSight->isStale()) {
            $return .= '?';
        }

        return $return;
    }

    public function getInfoOpName(InfoOp $infoOp): string
    {
        return $this->espionageHelper->getInfoGatheringOperations()
            ->merge($this->spellHelper->getInfoOpSpells())
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

    public function getNumberOfActiveInfoOps(Realm $sourceRealm, Dominion $targetDominion): int
    {
        return $this->espionageHelper->getInfoGatheringOperations()
            ->merge($this->spellHelper->getInfoOpSpells())
            ->filter(function ($op) use ($sourceRealm, $targetDominion) {
                if ($op['key'] !== 'clairvoyance') { // refactor: Removes Clairvoyance from count
                    return $this->hasActiveInfoOp($sourceRealm, $targetDominion, $op['key']);
                }
                return null;
            })
            ->count();
    }

    public function getMaxInfoOps(): int
    {
        return $this->espionageHelper->getInfoGatheringOperations()
                ->merge($this->spellHelper->getInfoOpSpells())
                ->count() - 1; // refactor: Removes Clairvoyance from count
    }
}
